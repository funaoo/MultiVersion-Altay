<?php

declare(strict_types=1);

namespace lol\network\global;

use lol\network\mcpe\MVBlockTranslator;
use lol\network\mcpe\MVItemTranslator;
use lol\network\utils\ReflectionUtils;
use LogicException;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\block\VanillaBlocks;
use pocketmine\crafting\ComplexAliasRecipeIngredient;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\TagWildcardRecipeIngredient;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\NbtException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\SkinAdapter;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\GameMode as ProtocolGameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient as ProtocolRecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\NameItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;
use pocketmine\player\GameMode;
use Throwable;
use function get_class;

class MVTypeConverter extends TypeConverter
{

	private const PM_ID_TAG = "___Id___";

	private const RECIPE_INPUT_WILDCARD_META = 0x7fff;

	private BlockItemIdMap $blockItemIdMap;
	private MVBlockTranslator $blockTranslator;
	private MVItemTranslator $itemTranslator;
	private ItemTypeDictionary $itemTypeDictionary;
	private SkinAdapter $skinAdapter;

	public function __construct(BlockItemIdMap $blockItemIdMap, MVBlockTranslator $blockTranslator, ItemTypeDictionary $itemTypeDictionary, MVItemTranslator $ItemTranslator, SkinAdapter $skinAdapter)
	{
		parent::__construct();
		$this->blockItemIdMap = $blockItemIdMap;

		$this->blockTranslator = $blockTranslator;

		$this->itemTypeDictionary = $itemTypeDictionary;
		ReflectionUtils::setProperty(TypeConverter::class, $this, "shieldRuntimeId", $this->itemTypeDictionary->fromStringId("minecraft:shield"));

		$this->itemTranslator = $ItemTranslator;

		$this->skinAdapter = $skinAdapter;
	}

	public function getMVBlockTranslator() : MVBlockTranslator
	{
		return $this->blockTranslator;
	}

	public function getItemTypeDictionary() : ItemTypeDictionary
	{
		return $this->itemTypeDictionary;
	}

	public function getMVItemTranslator() : MVItemTranslator
	{
		return $this->itemTranslator;
	}

	public function getSkinAdapter() : SkinAdapter
	{
		return $this->skinAdapter;
	}

	public function setSkinAdapter(SkinAdapter $skinAdapter) : void
	{
		$this->skinAdapter = $skinAdapter;
	}

	public function coreGameModeToProtocol(GameMode $gamemode) : int
	{
		return match ($gamemode) {
			GameMode::SURVIVAL => ProtocolGameMode::SURVIVAL,
			GameMode::CREATIVE, GameMode::SPECTATOR => ProtocolGameMode::CREATIVE,
			GameMode::ADVENTURE => ProtocolGameMode::ADVENTURE,
		};
	}

	public function protocolGameModeToCore(int $gameMode) : ?GameMode
	{
		return match ($gameMode) {
			ProtocolGameMode::SURVIVAL => GameMode::SURVIVAL,
			ProtocolGameMode::CREATIVE => GameMode::CREATIVE,
			ProtocolGameMode::ADVENTURE => GameMode::ADVENTURE,
			ProtocolGameMode::CREATIVE_VIEWER, ProtocolGameMode::SURVIVAL_VIEWER => GameMode::SPECTATOR,
			default => null,
		};
	}

	public function coreRecipeIngredientToNet(?RecipeIngredient $ingredient) : ProtocolRecipeIngredient
	{
		if ($ingredient === null) {
			return new ProtocolRecipeIngredient(null, 0);
		}
		if ($ingredient instanceof MetaWildcardRecipeIngredient) {
			$descriptor = new NameItemDescriptor($ingredient->getItemId(), self::RECIPE_INPUT_WILDCARD_META);
		} elseif ($ingredient instanceof ExactRecipeIngredient) {
			$item = $ingredient->getItem();
			[$id, $meta] = $this->itemTranslator->toNetworkId($item);
			$descriptor = new NameItemDescriptor($this->itemTypeDictionary->fromIntId($id), $meta);
		} elseif ($ingredient instanceof TagWildcardRecipeIngredient) {
			$descriptor = new TagItemDescriptor($ingredient->getTagName());
		} elseif ($ingredient instanceof ComplexAliasRecipeIngredient) {
			$descriptor = new NameItemDescriptor($ingredient->getAliasName(), self::RECIPE_INPUT_WILDCARD_META);
		} else {
			throw new LogicException("Unsupported recipe ingredient type " . get_class($ingredient) . ", only " . ExactRecipeIngredient::class . " and " . MetaWildcardRecipeIngredient::class . " are supported");
		}

		return new ProtocolRecipeIngredient($descriptor, 1);
	}

	public function netRecipeIngredientToCore(ProtocolRecipeIngredient $ingredient) : ?RecipeIngredient
	{
		$descriptor = $ingredient->getDescriptor();
		if ($descriptor === null) {
			return null;
		}

		if ($descriptor instanceof TagItemDescriptor) {
			return new TagWildcardRecipeIngredient($descriptor->getTag());
		}

		if ($descriptor instanceof NameItemDescriptor) {
			$stringId = $descriptor->getName();
			$meta = $descriptor->getAuxValue();
		} else {
			throw new LogicException("Unsupported conversion of recipe ingredient to core item stack");
		}

		if ($meta === self::RECIPE_INPUT_WILDCARD_META) {
			return new MetaWildcardRecipeIngredient($stringId);
		}

		$blockRuntimeId = null;
		if (($blockId = $this->blockItemIdMap->lookupBlockId($stringId)) !== null) {
			$blockRuntimeId = $this->blockTranslator->getBlockStateDictionary()->lookupStateIdFromIdMeta($blockId, $meta);
			if ($blockRuntimeId !== null) {
				$meta = 0;
			}
		}
		$result = $this->itemTranslator->fromNetworkId(
			$this->itemTypeDictionary->fromStringId($stringId),
			$meta,
			$blockRuntimeId ?? ItemTranslator::NO_BLOCK_RUNTIME_ID
		);
		return new ExactRecipeIngredient($result);
	}

	public function coreItemStackToNet(Item $itemStack) : ItemStack
	{
		if ($itemStack->isNull()) {
			return ItemStack::null();
		}

		return $this->encodeItemStack($itemStack, $this->itemTranslator->toNetworkIdQuiet($itemStack));
	}

	public function coreItemStackToNetQuiet(Item $itemStack) : ?ItemStack
	{
		if ($itemStack->isNull()) {
			return ItemStack::null();
		}

		$idMeta = $this->itemTranslator->toNetworkIdQuiet($itemStack);
		if ($idMeta === null) {
			return null;
		}

		return $this->encodeItemStack($itemStack, $idMeta);
	}

	public function isUnmappedPlaceholder(ItemStack $itemStack) : bool
	{
		if ($itemStack->getId() === 0) {
			return false;
		}

		try {
			$nbt = $this->deserializeItemStackExtraData($itemStack->getRawExtraData(), $itemStack->getId())->getNbt();
		} catch (Throwable) {
			return false;
		}

		return $nbt !== null && $nbt->getTag(self::PM_ID_TAG) !== null;
	}

	private function encodeItemStack(Item $itemStack, ?array $idMeta) : ItemStack
	{
		$nbt = $itemStack->getNamedTag();

		$nbt = $nbt->count() > 0 ? $this->cleanupUnnecessaryItemNBT($nbt) : null;

		if ($idMeta !== null) {
			[$id, $meta, $blockRuntimeId] = $idMeta;
		} else {
			[$id, $meta, $blockRuntimeId] = $this->itemTranslator->toNetworkId(VanillaBlocks::INFO_UPDATE()->asItem());
			$nbt = $nbt ?? CompoundTag::create();
			$nbt->setLong(self::PM_ID_TAG, $itemStack->getStateId());
		}

		$extraData = $id === $this->getShieldRuntimeId() ?
			new ItemStackExtraDataShield($nbt, canPlaceOn: [], canDestroy: [], blockingTick: 0) :
			new ItemStackExtraData($nbt, canPlaceOn: [], canDestroy: []);
		$extraDataSerializer = new ByteBufferWriter();
		$extraData->write($extraDataSerializer);

		return new ItemStack(
			$id,
			$meta,
			$itemStack->getCount(),
			$blockRuntimeId ?? ItemTranslator::NO_BLOCK_RUNTIME_ID,
			$extraDataSerializer->getData(),
		);
	}

	public function getShieldRuntimeId() : int
	{
		return ReflectionUtils::getProperty(TypeConverter::class, $this, "shieldRuntimeId");
	}

	public function deserializeItemStackExtraData(string $extraData, int $id) : ItemStackExtraData
	{
		$reader = new ByteBufferReader($extraData);
		return $id === $this->getShieldRuntimeId() ?
			ItemStackExtraDataShield::read($reader) :
			ItemStackExtraData::read($reader);
	}

	public function netItemStackToCore(ItemStack $itemStack) : Item
	{
		if ($itemStack->getId() === 0) {
			return VanillaItems::AIR();
		}

		$extraData = $this->deserializeItemStackExtraData($itemStack->getRawExtraData(), $itemStack->getId());
		$compound = $extraData->getNbt();

		$itemResult = $this->itemTranslator->fromNetworkId($itemStack->getId(), $itemStack->getMeta(), $itemStack->getBlockRuntimeId());
		$itemResult->setCount($itemStack->getCount());

		if ($compound !== null) {
			try {
				$itemResult->setNamedTag(clone $compound);
			} catch (NbtException $e) {
				throw TypeConversionException::wrap($e, "Bad itemstack NBT data");
			}
		}

		return $itemResult;
	}
}
