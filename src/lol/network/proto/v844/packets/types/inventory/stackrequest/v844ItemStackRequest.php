<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\stackrequest;

use lol\network\proto\v844\structure\v844Debug;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\BeaconPaymentStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingConsumeInputStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingCreateSpecificResultStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeAutoStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeOptionalStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CreativeCreateStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingNonImplementedStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingResultsStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DestroyStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DropStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\GrindstoneStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestActionType;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestNetworkItemInstanceDescriptor;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LabTableCombineStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LoomStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\MineBlockStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\SwapStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use function count;

final class v844ItemStackRequest
{

	private const LEGACY_PLACE_INTO_BUNDLE = 7;
	private const LEGACY_TAKE_FROM_BUNDLE = 8;

	private function __construct()
	{

	}

	private static function fromLegacyTypeId(int $legacyTypeId) : int
	{
		return match ($legacyTypeId) {
			0 => ItemStackRequestActionType::TAKE,
			1 => ItemStackRequestActionType::PLACE,
			2 => ItemStackRequestActionType::SWAP,
			3 => ItemStackRequestActionType::DROP,
			4 => ItemStackRequestActionType::DESTROY,
			5 => ItemStackRequestActionType::CRAFTING_CONSUME_INPUT,
			6 => ItemStackRequestActionType::CRAFTING_CREATE_SPECIFIC_RESULT,
			9 => ItemStackRequestActionType::LAB_TABLE_COMBINE,
			10 => ItemStackRequestActionType::BEACON_PAYMENT,
			11 => ItemStackRequestActionType::MINE_BLOCK,
			12 => ItemStackRequestActionType::CRAFTING_RECIPE,
			13 => ItemStackRequestActionType::CRAFTING_RECIPE_AUTO,
			14 => ItemStackRequestActionType::CREATIVE_CREATE,
			15 => ItemStackRequestActionType::CRAFTING_RECIPE_OPTIONAL,
			16 => ItemStackRequestActionType::CRAFTING_GRINDSTONE,
			17 => ItemStackRequestActionType::CRAFTING_LOOM,
			18 => ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING,
			19 => ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING,
			default => throw new PacketDecodeException("Unknown item stack request action legacy type $legacyTypeId"),
		};
	}

	private static function toLegacyTypeId(int $typeId) : int
	{
		return match ($typeId) {
			ItemStackRequestActionType::TAKE => 0,
			ItemStackRequestActionType::PLACE => 1,
			ItemStackRequestActionType::SWAP => 2,
			ItemStackRequestActionType::DROP => 3,
			ItemStackRequestActionType::DESTROY => 4,
			ItemStackRequestActionType::CRAFTING_CONSUME_INPUT => 5,
			ItemStackRequestActionType::CRAFTING_CREATE_SPECIFIC_RESULT => 6,
			ItemStackRequestActionType::LAB_TABLE_COMBINE => 9,
			ItemStackRequestActionType::BEACON_PAYMENT => 10,
			ItemStackRequestActionType::MINE_BLOCK => 11,
			ItemStackRequestActionType::CRAFTING_RECIPE => 12,
			ItemStackRequestActionType::CRAFTING_RECIPE_AUTO => 13,
			ItemStackRequestActionType::CREATIVE_CREATE => 14,
			ItemStackRequestActionType::CRAFTING_RECIPE_OPTIONAL => 15,
			ItemStackRequestActionType::CRAFTING_GRINDSTONE => 16,
			ItemStackRequestActionType::CRAFTING_LOOM => 17,
			ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING => 18,
			ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING => 19,
			default => throw new \InvalidArgumentException("Item stack request action type $typeId has no equivalent in this protocol"),
		};
	}

	public static function read(ByteBufferReader $in) : ItemStackRequest
	{
		$requestId = CommonTypes::readItemStackRequestId($in);

		$actions = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$legacyTypeId = Byte::readUnsigned($in);

			if ($legacyTypeId === self::LEGACY_PLACE_INTO_BUNDLE || $legacyTypeId === self::LEGACY_TAKE_FROM_BUNDLE) {
				Byte::readUnsigned($in);
				v844ItemStackRequestSlotInfo::read($in);
				v844ItemStackRequestSlotInfo::read($in);
				v844Debug::logOnce(
					"stackrequest:bundle:" . $legacyTypeId,
					"ItemStackRequest",
					"dropping bundle action (legacy id " . $legacyTypeId . "): removed in 1.26.40, no equivalent exists in the 2193 types Altay is built against"
				);
				continue;
			}

			$actions[] = self::readAction($in, self::fromLegacyTypeId($legacyTypeId), $legacyTypeId);
		}

		$filterStrings = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$filterStrings[] = CommonTypes::getString($in);
		}
		$filterStringCause = LE::readSignedInt($in);

		return new ItemStackRequest($requestId, $actions, $filterStrings, $filterStringCause);
	}

	private static function readAction(ByteBufferReader $in, int $typeId, int $legacyTypeId) : ItemStackRequestAction
	{
		switch ($typeId) {
			case ItemStackRequestActionType::TAKE:
				[$count, $source, $destination] = self::readTakeOrPlace($in);
				return new TakeStackRequestAction($count, $source, $destination);
			case ItemStackRequestActionType::PLACE:
				[$count, $source, $destination] = self::readTakeOrPlace($in);
				return new PlaceStackRequestAction($count, $source, $destination);
			case ItemStackRequestActionType::SWAP:
				$slot1 = v844ItemStackRequestSlotInfo::read($in);
				$slot2 = v844ItemStackRequestSlotInfo::read($in);
				return new SwapStackRequestAction($slot1, $slot2);
			case ItemStackRequestActionType::DROP:
				$count = Byte::readUnsigned($in);
				$source = v844ItemStackRequestSlotInfo::read($in);
				$randomly = CommonTypes::getBool($in);
				return new DropStackRequestAction($count, $source, $randomly);
			case ItemStackRequestActionType::DESTROY:
				[$count, $source] = self::readDisappear($in);
				return new DestroyStackRequestAction($count, $source);
			case ItemStackRequestActionType::CRAFTING_CONSUME_INPUT:
				[$count, $source] = self::readDisappear($in);
				return new CraftingConsumeInputStackRequestAction($count, $source);
			case ItemStackRequestActionType::CRAFTING_CREATE_SPECIFIC_RESULT:
				return new CraftingCreateSpecificResultStackRequestAction(Byte::readUnsigned($in));
			case ItemStackRequestActionType::LAB_TABLE_COMBINE:
				return new LabTableCombineStackRequestAction();
			case ItemStackRequestActionType::BEACON_PAYMENT:
				$primary = VarInt::readSignedInt($in);
				$secondary = VarInt::readSignedInt($in);
				return new BeaconPaymentStackRequestAction($primary, $secondary);
			case ItemStackRequestActionType::MINE_BLOCK:
				$hotbarSlot = VarInt::readSignedInt($in);
				$predictedDurability = VarInt::readSignedInt($in);
				$stackId = VarInt::readSignedInt($in);
				return new MineBlockStackRequestAction($hotbarSlot, $predictedDurability, $stackId);
			case ItemStackRequestActionType::CRAFTING_RECIPE:
				$recipeId = VarInt::readUnsignedInt($in);
				$repetitions = Byte::readUnsigned($in);
				return new CraftRecipeStackRequestAction($recipeId, $repetitions);
			case ItemStackRequestActionType::CRAFTING_RECIPE_AUTO:
				$recipeId = VarInt::readUnsignedInt($in);
				$repetitions = Byte::readUnsigned($in);
				Byte::readUnsigned($in);
				$ingredients = [];
				for ($i = 0, $ingredientCount = Byte::readUnsigned($in); $i < $ingredientCount; ++$i) {
					$ingredients[] = v844StackRequestIngredient::read($in);
				}
				return new CraftRecipeAutoStackRequestAction($recipeId, $repetitions, $ingredients);
			case ItemStackRequestActionType::CREATIVE_CREATE:
				$creativeItemId = CommonTypes::readCreativeItemNetId($in);
				$repetitions = Byte::readUnsigned($in);
				return new CreativeCreateStackRequestAction($creativeItemId, $repetitions);
			case ItemStackRequestActionType::CRAFTING_RECIPE_OPTIONAL:
				$recipeId = VarInt::readUnsignedInt($in);
				$filterStringIndex = LE::readSignedInt($in);
				return new CraftRecipeOptionalStackRequestAction($recipeId, $filterStringIndex);
			case ItemStackRequestActionType::CRAFTING_GRINDSTONE:
				$recipeId = VarInt::readUnsignedInt($in);
				$repairCost = VarInt::readSignedInt($in);
				$repetitions = Byte::readUnsigned($in);
				return new GrindstoneStackRequestAction($recipeId, $repairCost, $repetitions);
			case ItemStackRequestActionType::CRAFTING_LOOM:
				$patternId = CommonTypes::getString($in);
				$repetitions = Byte::readUnsigned($in);
				return new LoomStackRequestAction($patternId, $repetitions);
			case ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING:
				return new DeprecatedCraftingNonImplementedStackRequestAction();
			case ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING:
				$results = [];
				for ($i = 0, $resultCount = VarInt::readUnsignedInt($in); $i < $resultCount; ++$i) {
					$itemStack = v844ItemStackSerializer::getItemStackWithoutStackId($in);
					$results[] = new ItemStackRequestNetworkItemInstanceDescriptor(
						new RecipeIngredient(null, $itemStack->getCount()),
						$itemStack->getBlockRuntimeId(),
						$itemStack->getRawExtraData()
					);
				}
				$iterations = Byte::readUnsigned($in);
				return new DeprecatedCraftingResultsStackRequestAction($results, $iterations);
			default:
				throw new PacketDecodeException("Unhandled item stack request action type $typeId (844 legacy id $legacyTypeId)");
		}
	}

	private static function readTakeOrPlace(ByteBufferReader $in) : array
	{
		$count = Byte::readUnsigned($in);
		$source = v844ItemStackRequestSlotInfo::read($in);
		$destination = v844ItemStackRequestSlotInfo::read($in);
		return [$count, $source, $destination];
	}

	private static function readDisappear(ByteBufferReader $in) : array
	{
		$count = Byte::readUnsigned($in);
		$source = v844ItemStackRequestSlotInfo::read($in);
		return [$count, $source];
	}

	public static function write(ByteBufferWriter $out, ItemStackRequest $request) : void
	{
		CommonTypes::writeItemStackRequestId($out, $request->getRequestId());

		$actions = $request->getActions();
		VarInt::writeUnsignedInt($out, count($actions));
		foreach ($actions as $action) {
			Byte::writeUnsigned($out, self::toLegacyTypeId($action->getTypeId()));
			self::writeAction($out, $action);
		}

		$filterStrings = $request->getFilterStrings();
		VarInt::writeUnsignedInt($out, count($filterStrings));
		foreach ($filterStrings as $filterString) {
			CommonTypes::putString($out, $filterString);
		}
		LE::writeSignedInt($out, $request->getFilterStringCause());
	}

	private static function writeAction(ByteBufferWriter $out, ItemStackRequestAction $action) : void
	{
		if ($action instanceof TakeStackRequestAction || $action instanceof PlaceStackRequestAction) {
			Byte::writeUnsigned($out, $action->getCount());
			v844ItemStackRequestSlotInfo::write($out, $action->getSource());
			v844ItemStackRequestSlotInfo::write($out, $action->getDestination());
		} elseif ($action instanceof SwapStackRequestAction) {
			v844ItemStackRequestSlotInfo::write($out, $action->getSlot1());
			v844ItemStackRequestSlotInfo::write($out, $action->getSlot2());
		} elseif ($action instanceof DropStackRequestAction) {
			Byte::writeUnsigned($out, $action->getCount());
			v844ItemStackRequestSlotInfo::write($out, $action->getSource());
			CommonTypes::putBool($out, $action->isRandomly());
		} elseif ($action instanceof DestroyStackRequestAction || $action instanceof CraftingConsumeInputStackRequestAction) {
			Byte::writeUnsigned($out, $action->getCount());
			v844ItemStackRequestSlotInfo::write($out, $action->getSource());
		} elseif ($action instanceof CraftingCreateSpecificResultStackRequestAction) {
			Byte::writeUnsigned($out, $action->getResultIndex());
		} elseif ($action instanceof LabTableCombineStackRequestAction || $action instanceof DeprecatedCraftingNonImplementedStackRequestAction) {
		} elseif ($action instanceof BeaconPaymentStackRequestAction) {
			VarInt::writeSignedInt($out, $action->getPrimaryEffectId());
			VarInt::writeSignedInt($out, $action->getSecondaryEffectId());
		} elseif ($action instanceof MineBlockStackRequestAction) {
			VarInt::writeSignedInt($out, $action->getHotbarSlot());
			VarInt::writeSignedInt($out, $action->getPredictedDurability());
			VarInt::writeSignedInt($out, $action->getStackId());
		} elseif ($action instanceof CraftRecipeStackRequestAction) {
			VarInt::writeUnsignedInt($out, $action->getRecipeId());
			Byte::writeUnsigned($out, $action->getRepetitions());
		} elseif ($action instanceof CraftRecipeAutoStackRequestAction) {
			VarInt::writeUnsignedInt($out, $action->getRecipeId());
			Byte::writeUnsigned($out, $action->getRepetitions());
			Byte::writeUnsigned($out, $action->getRepetitions());
			$ingredients = $action->getIngredients();
			Byte::writeUnsigned($out, count($ingredients));
			foreach ($ingredients as $ingredient) {
				v844StackRequestIngredient::write($out, $ingredient);
			}
		} elseif ($action instanceof CreativeCreateStackRequestAction) {
			CommonTypes::writeCreativeItemNetId($out, $action->getCreativeItemId());
			Byte::writeUnsigned($out, $action->getRepetitions());
		} elseif ($action instanceof CraftRecipeOptionalStackRequestAction) {
			VarInt::writeUnsignedInt($out, $action->getRecipeId());
			LE::writeSignedInt($out, $action->getFilterStringIndex());
		} elseif ($action instanceof GrindstoneStackRequestAction) {
			VarInt::writeUnsignedInt($out, $action->getRecipeId());
			VarInt::writeSignedInt($out, $action->getRepairCost());
			Byte::writeUnsigned($out, $action->getRepetitions());
		} elseif ($action instanceof LoomStackRequestAction) {
			CommonTypes::putString($out, $action->getPatternId());
			Byte::writeUnsigned($out, $action->getRepetitions());
		} elseif ($action instanceof DeprecatedCraftingResultsStackRequestAction) {
			VarInt::writeUnsignedInt($out, 0);
			Byte::writeUnsigned($out, $action->getIterations());
		} else {
			throw new \InvalidArgumentException("Unhandled item stack request action " . $action::class);
		}
	}
}
