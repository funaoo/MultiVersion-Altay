<?php

declare(strict_types=1);

namespace lol\network\anvil;

use lol\network\NetworkSession;
use lol\network\utils\ReflectionUtils;
use pocketmine\block\Anvil as AnvilBlock;
use pocketmine\block\inventory\AnvilInventory;
use pocketmine\block\Planks;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\EnchantedBook;
use pocketmine\item\enchantment\AvailableEnchantmentRegistry;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\Rarity;
use pocketmine\item\Item;
use pocketmine\item\TieredTool;
use pocketmine\item\ToolTier;
use pocketmine\item\VanillaArmorMaterials;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackContainerIdTranslator;
use pocketmine\network\mcpe\handler\ItemStackResponseBuilder;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerUIIds;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeOptionalStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseContainerInfo;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseSlotInfo;
use pocketmine\network\mcpe\protocol\types\inventory\UIInventorySlotOffset;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use pocketmine\world\Position;
use pocketmine\world\sound\AnvilBreakSound;
use pocketmine\world\sound\AnvilUseSound;
use function array_search;
use function array_values;
use function count;
use function intdiv;
use function max;
use function mb_check_encoding;
use function min;
use function spl_object_id;

final class MVAnvilRequestInterceptor{

	private const TAG_REPAIR_COST = "RepairCost";
	private const RENAME_COST = 1;
	private const MERGE_BASE_COST = 2;
	private const MAXIMUM_LEVEL_COST = 39;
	private const MAX_REQUESTS_PER_PACKET = 80;
	private const MAX_ACTIONS_PER_REQUEST = 60;
	private const ANVIL_BREAK_CHANCE = 0.12;
	private const MAX_REPAIR_COST = 2147483647;

	private static array $lastCalculatedLevelCosts = [];

	private function __construct(){

	}

	public static function getLastCalculatedLevelCost(NetworkSession $session) : int{
		return self::$lastCalculatedLevelCosts[spl_object_id($session)] ?? 0;
	}

	public static function shouldIntercept(NetworkSession $session, ItemStackRequestPacket $packet) : bool{
		$player = $session->getPlayer();
		if($player === null){
			return false;
		}

		if(!($player->getCurrentWindow() instanceof AnvilInventory)){
			return false;
		}

		foreach($packet->getRequests() as $request){
			if($request instanceof ItemStackRequest && self::isAnvilOperationRequest($request)){
				return true;
			}
		}

		return false;
	}

	public static function intercept(NetworkSession $session, ItemStackRequestPacket $packet) : void{
		if(count($packet->getRequests()) > self::MAX_REQUESTS_PER_PACKET){
			throw new PacketHandlingException("Too many requests in ItemStackRequestPacket");
		}

		$player = $session->getPlayer();
		if($player === null){
			return;
		}

		$currentHandler = $session->getHandler();

		$responses = [];
		foreach($packet->getRequests() as $request){
			$renameAction = self::findAnvilRenameAction($request);
			$takeAction = self::findCreatedOutputTakeAction($request);
			if($renameAction !== null || $takeAction !== null){
				$response = self::handleAnvilRenameRequest($session, $request, $renameAction);
			}elseif($currentHandler instanceof InGamePacketHandler){
				$response = self::delegateToDefaultExecutor($currentHandler, $request);
			}else{
				$response = new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
			}

			$responses[] = $response;
		}

		$session->sendDataPacket(ItemStackResponsePacket::create($responses));
	}

	private static function isAnvilOperationRequest(ItemStackRequest $request) : bool{
		return self::findAnvilRenameAction($request) !== null || self::findCreatedOutputTakeAction($request) !== null;
	}

	private static function findAnvilRenameAction(ItemStackRequest $request) : ?CraftRecipeOptionalStackRequestAction{
		foreach($request->getActions() as $action){
			if($action instanceof CraftRecipeOptionalStackRequestAction){
				return $action;
			}
		}

		return null;
	}

	private static function handleAnvilRenameRequest(NetworkSession $session, ItemStackRequest $request, ?CraftRecipeOptionalStackRequestAction $action) : ItemStackResponse{
		self::$lastCalculatedLevelCosts[spl_object_id($session)] = 0;

		if(count($request->getActions()) > self::MAX_ACTIONS_PER_REQUEST){
			throw new PacketHandlingException("Too many actions in ItemStackRequest");
		}

		$player = $session->getPlayer();
		if($player === null){
			return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}

		$invManager = $session->getInvManager();

		$window = $player->getCurrentWindow();
		if(!($window instanceof AnvilInventory)){
			$invManager?->requestSyncAll();

			return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}

		$filterStrings = $request->getFilterStrings();
		$rawFilterIndex = $action !== null ? $action->getFilterStringIndex() : -1;
		$filterIndex = ($rawFilterIndex >= 0 && isset($filterStrings[$rawFilterIndex])) ? $rawFilterIndex : -1;

		if($filterIndex !== -1 && !mb_check_encoding($filterStrings[$filterIndex], "UTF-8")){
			$invManager?->requestSyncAll();

			return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}

		$takeAction = self::findCreatedOutputTakeAction($request);
		$newName = $filterIndex === -1 ? null : $filterStrings[$filterIndex];

		$operation = self::calculateOperation($player, $window, $newName);
		if($operation === null){
			if($takeAction !== null){
				$invManager?->requestSyncAll();

				return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
			}

			return new ItemStackResponse(ItemStackResponse::RESULT_OK, $request->getRequestId());
		}

		self::$lastCalculatedLevelCosts[spl_object_id($session)] = $operation->levelCost;

		if($takeAction !== null){
			$destination = $takeAction->getDestination();

			return self::completeTake(
				$request->getRequestId(),
				$invManager,
				$window,
				$takeAction->getCount(),
				$destination->getContainerName()->getContainerId(),
				$destination->getSlotId(),
				$destination->getStackId(),
				$operation,
				$player,
				false
			);
		}

		return self::buildRenamePreviewResponse($request, $operation->result);
	}

	public static function calculateOperation(Player $player, AnvilInventory $window, ?string $newName) : ?AnvilOperation{
		$inputItem = $window->getItem(AnvilInventory::SLOT_INPUT);
		if($inputItem->isNull() || self::getRepairCost($inputItem) >= self::MAX_REPAIR_COST){
			return null;
		}

		$renamed = $newName !== null && $newName !== $inputItem->getCustomName();
		$materialItem = $window->getItem(AnvilInventory::SLOT_MATERIAL);

		$repairedDamage = null;
		$materialsUsed = 0;
		$mergedEnchantments = null;
		$operationCost = 0;
		$materialRepairCost = 0;
		$operationHappened = $renamed;
		if(!$materialItem->isNull()){
			$repair = self::calculateAnvilResult($inputItem, $materialItem);
			if($repair === null){
				return null;
			}

			[$repairedDamage, $materialsUsed, $mergedEnchantments, $operationCost] = $repair;
			$materialRepairCost = self::getRepairCost($materialItem);
			$operationHappened = true;
		}

		$resultItem = clone $inputItem;
		if($newName !== null){
			$resultItem->setCustomName($newName);
		}
		if($repairedDamage !== null && $resultItem instanceof Durable){
			$resultItem->setDamage($repairedDamage);
		}
		if($mergedEnchantments !== null){
			$resultItem->removeEnchantments();
			foreach($mergedEnchantments as $enchantmentInstance){
				$resultItem->addEnchantment($enchantmentInstance);
			}
		}

		$levelCost = 0;
		if($operationHappened){
			$inputRepairCost = self::getRepairCost($inputItem);
			$levelCost = $operationCost + ($renamed ? self::RENAME_COST : 0) + $inputRepairCost + $materialRepairCost;

			if($player->hasFiniteResources() && $levelCost > self::MAXIMUM_LEVEL_COST){
				return null;
			}

			self::setRepairCost($resultItem, self::increaseRepairCost(max($inputRepairCost, $materialRepairCost)));
		}

		return new AnvilOperation($resultItem, $materialsUsed, $levelCost);
	}

	private static function calculateAnvilResult(Item $inputItem, Item $materialItem) : ?array{
		if($inputItem instanceof Durable && $materialItem instanceof Durable && $inputItem->equals($materialItem, false, false)){
			return self::calculateItemMerge($inputItem, $materialItem);
		}

		if($materialItem instanceof EnchantedBook){
			return self::calculateBookApplication($inputItem, $materialItem);
		}

		$repair = self::calculateMaterialRepair($inputItem, $materialItem);
		if($repair === null){
			return null;
		}

		[$repairedDamage, $materialsUsed] = $repair;

		return [$repairedDamage, $materialsUsed, null, $materialsUsed];
	}

	private static function calculateItemMerge(Durable $inputItem, Durable $materialItem) : ?array{
		$maxDurability = $inputItem->getMaxDurability();
		$damage = $inputItem->getDamage();

		$remainingInput = $maxDurability - $damage;
		$remainingMaterial = $maxDurability - $materialItem->getDamage();
		$bonus = intdiv($maxDurability * 12, 100);

		$combined = min($maxDurability, $remainingInput + $remainingMaterial + $bonus);
		$newDamage = max(0, $maxDurability - $combined);

		$mergedEnchantments = self::mergeEnchantments($inputItem, $materialItem);
		if($newDamage >= $damage && self::enchantmentsMatch($inputItem->getEnchantments(), $mergedEnchantments)){
			return null;
		}

		$operationCost = self::MERGE_BASE_COST + self::calculateEnchantmentCost($inputItem->getEnchantments(), $mergedEnchantments, false);

		return [$newDamage, 1, $mergedEnchantments, $operationCost];
	}

	private static function calculateBookApplication(Item $inputItem, EnchantedBook $materialItem) : ?array{
		$mergedEnchantments = self::mergeEnchantments($inputItem, $materialItem);
		if(self::enchantmentsMatch($inputItem->getEnchantments(), $mergedEnchantments)){
			return null;
		}

		$operationCost = self::calculateEnchantmentCost($inputItem->getEnchantments(), $mergedEnchantments, true);

		return [null, 1, $mergedEnchantments, $operationCost];
	}

	private static function mergeEnchantments(Item $inputItem, Item $materialItem) : array{
		$registry = AvailableEnchantmentRegistry::getInstance();

		$result = [];
		foreach($inputItem->getEnchantments() as $instance){
			$result[spl_object_id($instance->getType())] = $instance;
		}

		foreach($materialItem->getEnchantments() as $instance){
			$enchantment = $instance->getType();
			$id = spl_object_id($enchantment);

			$existing = $result[$id] ?? null;
			if($existing !== null){
				$level = $existing->getLevel() === $instance->getLevel() ? $existing->getLevel() + 1 : max($existing->getLevel(), $instance->getLevel());
				$result[$id] = new EnchantmentInstance($enchantment, min($level, $enchantment->getMaxLevel()));
				continue;
			}

			if(!$registry->isAvailableForItem($enchantment, $inputItem)){
				continue;
			}

			$incompatible = false;
			foreach($result as $current){
				if(!$enchantment->isCompatibleWith($current->getType())){
					$incompatible = true;
					break;
				}
			}

			if($incompatible){
				continue;
			}

			$result[$id] = $instance;
		}

		return array_values($result);
	}

	private static function enchantmentsMatch(array $current, array $merged) : bool{
		if(count($current) !== count($merged)){
			return false;
		}

		$currentLevels = [];
		foreach($current as $instance){
			$currentLevels[spl_object_id($instance->getType())] = $instance->getLevel();
		}

		foreach($merged as $instance){
			$id = spl_object_id($instance->getType());
			if(!isset($currentLevels[$id]) || $currentLevels[$id] !== $instance->getLevel()){
				return false;
			}
		}

		return true;
	}

	private static function calculateEnchantmentCost(array $before, array $merged, bool $fromBook) : int{
		$beforeLevels = [];
		foreach($before as $instance){
			$beforeLevels[spl_object_id($instance->getType())] = $instance->getLevel();
		}

		$cost = 0;
		foreach($merged as $instance){
			$type = $instance->getType();
			$priorLevel = $beforeLevels[spl_object_id($type)] ?? 0;
			if($instance->getLevel() > $priorLevel){
				$cost += self::enchantmentLevelMultiplier($type, $fromBook) * $instance->getLevel();
			}
		}

		return $cost;
	}

	private static function enchantmentLevelMultiplier(Enchantment $enchantment, bool $fromBook) : int{
		$multiplier = match($enchantment->getRarity()){
			Rarity::COMMON => 1,
			Rarity::UNCOMMON => 2,
			Rarity::RARE => 4,
			Rarity::MYTHIC => 8,
			default => 1
		};

		return $fromBook ? max(1, intdiv($multiplier, 2)) : $multiplier;
	}

	private static function getRepairCost(Item $item) : int{
		return $item->getNamedTag()->getInt(self::TAG_REPAIR_COST, 0);
	}

	private static function setRepairCost(Item $item, int $repairCost) : void{
		if($repairCost > 0){
			$item->getNamedTag()->setInt(self::TAG_REPAIR_COST, $repairCost);
		}else{
			$item->getNamedTag()->removeTag(self::TAG_REPAIR_COST);
		}
	}

	private static function increaseRepairCost(int $repairCost) : int{
		return min(self::MAX_REPAIR_COST, $repairCost * 2 + 1);
	}

	private static function calculateMaterialRepair(Item $inputItem, Item $materialItem) : ?array{
		if(!($inputItem instanceof Durable)){
			return null;
		}

		$damage = $inputItem->getDamage();
		if($damage <= 0){
			return null;
		}

		if(!self::materialMatches($inputItem, $materialItem)){
			return null;
		}

		$repairPerMaterial = max(1, intdiv($inputItem->getMaxDurability(), 4));
		$materialsNeeded = intdiv($damage + $repairPerMaterial - 1, $repairPerMaterial);
		$materialsUsed = min($materialsNeeded, $materialItem->getCount());
		if($materialsUsed <= 0){
			return null;
		}

		return [max(0, $damage - $materialsUsed * $repairPerMaterial), $materialsUsed];
	}

	private static function materialMatches(Item $inputItem, Item $materialItem) : bool{
		if($inputItem instanceof TieredTool){
			$tier = $inputItem->getTier();
			if($tier === ToolTier::WOOD){
				return $materialItem->getBlock() instanceof Planks;
			}

			$expected = match($tier){
				ToolTier::STONE => VanillaBlocks::COBBLESTONE()->asItem(),
				ToolTier::COPPER => VanillaItems::COPPER_INGOT(),
				ToolTier::GOLD => VanillaItems::GOLD_INGOT(),
				ToolTier::IRON => VanillaItems::IRON_INGOT(),
				ToolTier::DIAMOND => VanillaItems::DIAMOND(),
				ToolTier::NETHERITE => VanillaItems::NETHERITE_INGOT()
			};

			return $materialItem->equals($expected, false, false);
		}

		if($inputItem instanceof Armor){
			$material = $inputItem->getMaterial();
			$expected = match(true){
				$material === VanillaArmorMaterials::LEATHER() => VanillaItems::LEATHER(),
				$material === VanillaArmorMaterials::CHAINMAIL() => VanillaItems::IRON_INGOT(),
				$material === VanillaArmorMaterials::COPPER() => VanillaItems::COPPER_INGOT(),
				$material === VanillaArmorMaterials::GOLD() => VanillaItems::GOLD_INGOT(),
				$material === VanillaArmorMaterials::IRON() => VanillaItems::IRON_INGOT(),
				$material === VanillaArmorMaterials::DIAMOND() => VanillaItems::DIAMOND(),
				$material === VanillaArmorMaterials::NETHERITE() => VanillaItems::NETHERITE_INGOT(),
				$material === VanillaArmorMaterials::TURTLE() => VanillaItems::SCUTE(),
				default => null
			};

			return $expected !== null && $materialItem->equals($expected, false, false);
		}

		return false;
	}

	private static function findCreatedOutputTakeAction(ItemStackRequest $request) : TakeStackRequestAction|PlaceStackRequestAction|null{
		foreach($request->getActions() as $action){
			if(!($action instanceof TakeStackRequestAction) && !($action instanceof PlaceStackRequestAction)){
				continue;
			}

			$source = $action->getSource();
			if($source->getContainerName()->getContainerId() === ContainerUIIds::CREATED_OUTPUT && $source->getSlotId() === UIInventorySlotOffset::CREATED_ITEM_OUTPUT){
				return $action;
			}
		}

		return null;
	}

	public static function completeTake(int $requestId, ?InventoryManager $invManager, AnvilInventory $window, int $count, int $destContainerId, int $destSlotId, int $destStackId, AnvilOperation $operation, Player $player, bool $damageAnvil) : ItemStackResponse{
		$error = new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $requestId);
		$resultItem = $operation->result;
		$levelCost = $operation->levelCost;
		$materialsUsed = $operation->materialsUsed;

		if($invManager === null || $count !== $resultItem->getCount()){
			$invManager?->requestSyncAll();

			return $error;
		}

		if($levelCost > 0 && $player->hasFiniteResources() && $player->getXpManager()->getXpLevel() < $levelCost){
			$invManager->requestSyncAll();

			return $error;
		}

		try{
			[$destWindowId, $destSlotIndex] = ItemStackContainerIdTranslator::translate($destContainerId, $invManager->getCurrentWindowId(), $destSlotId);
		}catch(PacketHandlingException){
			$invManager->requestSyncAll();

			return $error;
		}

		$destinationLocation = $invManager->locateWindowAndSlot($destWindowId, $destSlotIndex);
		if($destinationLocation === null){
			$invManager->requestSyncAll();

			return $error;
		}

		[$destinationInventory, $destinationSlot] = $destinationLocation;

		if(
			($destinationInventory === $window && ($destinationSlot === AnvilInventory::SLOT_INPUT || $destinationSlot === AnvilInventory::SLOT_MATERIAL)) ||
			!self::destinationStackIdMatches($invManager, $destinationInventory, $destinationSlot, $destStackId, $requestId)
		){
			$invManager->requestSyncAll();

			return $error;
		}

		$existingDestinationItem = $destinationInventory->getItem($destinationSlot);
		if(!$existingDestinationItem->isNull() && !$existingDestinationItem->canStackWith($resultItem)){
			$invManager->requestSyncAll();

			return $error;
		}

		$newDestinationCount = $existingDestinationItem->getCount() + $resultItem->getCount();
		if($newDestinationCount > min($destinationInventory->getMaxStackSize(), $resultItem->getMaxStackSize())){
			$invManager->requestSyncAll();

			return $error;
		}

		$newDestinationItem = clone $resultItem;
		$newDestinationItem->setCount($newDestinationCount);
		$destinationInventory->setItem($destinationSlot, $newDestinationItem);
		$window->clear(AnvilInventory::SLOT_INPUT);

		if($materialsUsed > 0){
			$materialItem = $window->getItem(AnvilInventory::SLOT_MATERIAL);
			$remainingMaterialCount = $materialItem->getCount() - $materialsUsed;
			if($remainingMaterialCount > 0){
				$window->setItem(AnvilInventory::SLOT_MATERIAL, (clone $materialItem)->setCount($remainingMaterialCount));
			}else{
				$window->clear(AnvilInventory::SLOT_MATERIAL);
			}
		}

		if($levelCost > 0 && $player->hasFiniteResources()){
			$player->getXpManager()->subtractXpLevels($levelCost);
		}

		self::playAnvilUseEffects($window->getHolder(), $damageAnvil);

		$builder = new ItemStackResponseBuilder($requestId, $invManager);
		$builder->addSlot($destContainerId, $destSlotId);

		$inputNetSlot = array_search(AnvilInventory::SLOT_INPUT, UIInventorySlotOffset::ANVIL, true);
		if($inputNetSlot !== false){
			$builder->addSlot(ContainerUIIds::ANVIL_INPUT, $inputNetSlot);
		}

		if($materialsUsed > 0){
			$materialNetSlot = array_search(AnvilInventory::SLOT_MATERIAL, UIInventorySlotOffset::ANVIL, true);
			if($materialNetSlot !== false){
				$builder->addSlot(ContainerUIIds::ANVIL_MATERIAL, $materialNetSlot);
			}
		}

		return $builder->build();
	}

	private static function playAnvilUseEffects(Position $position, bool $damageAnvil) : void{
		$world = $position->getWorld();
		$block = $world->getBlock($position);

		$world->addSound($position, new AnvilUseSound());

		if(!$damageAnvil || !($block instanceof AnvilBlock) || Utils::getRandomFloat() >= self::ANVIL_BREAK_CHANCE){
			return;
		}

		if($block->getDamage() === AnvilBlock::VERY_DAMAGED){
			$world->setBlock($position, VanillaBlocks::AIR());
			$world->addSound($position, new AnvilBreakSound());

			return;
		}

		$world->setBlock($position, $block->setDamage($block->getDamage() + 1));
	}

	private static function destinationStackIdMatches(InventoryManager $invManager, Inventory $inventory, int $slot, int $clientItemStackId, int $requestId) : bool{
		if($clientItemStackId === $requestId){
			return true;
		}

		$info = $invManager->getItemStackInfo($inventory, $slot);
		if($info === null){
			return false;
		}

		return $clientItemStackId < 0 ? $info->getRequestId() === $clientItemStackId : $info->getStackId() === $clientItemStackId;
	}

	private static function buildRenamePreviewResponse(ItemStackRequest $request, Item $resultItem) : ItemStackResponse{
		$slotInfo = new ItemStackResponseSlotInfo(
			0,
			0,
			$resultItem->getCount(),
			null,
			$resultItem->getCustomName(),
			$resultItem->getCustomName(),
			$resultItem instanceof Durable ? $resultItem->getDamage() : 0
		);

		$containerInfo = new ItemStackResponseContainerInfo(new FullContainerName(ContainerUIIds::ANVIL_RESULT_PREVIEW), [$slotInfo]);

		return new ItemStackResponse(ItemStackResponse::RESULT_OK, $request->getRequestId(), [$containerInfo]);
	}

	private static function delegateToDefaultExecutor(InGamePacketHandler $handler, ItemStackRequest $request) : ItemStackResponse{
		$builder = ReflectionUtils::invoke(InGamePacketHandler::class, $handler, "handleSingleItemStackRequest", $request);

		return $builder !== null ? $builder->build() : new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
	}
}
