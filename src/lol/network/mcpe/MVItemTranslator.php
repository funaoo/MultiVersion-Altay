<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use InvalidArgumentException;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\data\bedrock\item\ItemDeserializer;
use pocketmine\data\bedrock\item\ItemSerializer;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class MVItemTranslator
{

	public const NO_BLOCK_RUNTIME_ID = 0;

	public function __construct(
		private ItemTypeDictionary     $itemTypeDictionary,
		private MVBlockStateDictionary $blockStateDictionary,
		private ItemSerializer         $itemSerializer,
		private ItemDeserializer       $itemDeserializer,
		private BlockItemIdMap         $blockItemIdMap,
		private MVItemIdMetaDowngrader $itemDataDowngrader,
	)
	{

	}

	public function toNetworkIdQuiet(Item $item) : ?array
	{
		try {
			return $this->toNetworkId($item);
		} catch (ItemTypeSerializeException) {
			return null;
		}
	}

	public function toNetworkId(Item $item) : array
	{

		$itemData = $this->itemSerializer->serializeType($item);

		[$name, $meta] = $this->itemDataDowngrader->downgrade($itemData->getName(), $itemData->getMeta());
		try {
			$numericId = $this->itemTypeDictionary->fromStringId($name);
		} catch (InvalidArgumentException) {
			throw new ItemTypeSerializeException("Unknown item type $name");
		}

		$blockStateData = $itemData->getBlock();

		if ($blockStateData !== null) {
			$blockRuntimeId = $this->blockStateDictionary->lookupStateIdFromData($blockStateData);
			if ($blockRuntimeId === null) {
				throw new ItemTypeSerializeException("Unmapped blockstate for item $name: " . $blockStateData->toNbt());
			}
		} else {
			$blockRuntimeId = null;
		}

		return [$numericId, $meta ?? $itemData->getMeta(), $blockRuntimeId];
	}

	public function getItemDataDowngrader() : MVItemIdMetaDowngrader
	{
		return $this->itemDataDowngrader;
	}

	public function toNetworkNbt(Item $item) : CompoundTag
	{
		return $this->itemSerializer->serializeStack($item)->toNbt();
	}

	public function fromNetworkId(int $networkId, int $networkMeta, int $networkBlockRuntimeId) : Item
	{
		try {
			$stringId = $this->itemTypeDictionary->fromIntId($networkId);
		} catch (InvalidArgumentException $e) {
			throw TypeConversionException::wrap($e, "Invalid network itemstack ID $networkId");
		}

		[$stringId, $networkMeta] = GlobalItemDataHandlers::getUpgrader()->getIdMetaUpgrader()->upgrade($stringId, $networkMeta);

		$blockStateData = null;
		if ($this->blockItemIdMap->lookupBlockId($stringId) !== null) {
			$blockStateData = $this->blockStateDictionary->generateCurrentDataFromStateId($networkBlockRuntimeId);
			if ($blockStateData === null) {
				throw new TypeConversionException("Blockstate runtimeID $networkBlockRuntimeId does not correspond to any known blockstate");
			}
		} elseif ($networkBlockRuntimeId !== self::NO_BLOCK_RUNTIME_ID) {
			throw new TypeConversionException("Item $stringId is not a blockitem, but runtime ID $networkBlockRuntimeId was provided");
		}

		try {
			return $this->itemDeserializer->deserializeType(new SavedItemData($stringId, $networkMeta, $blockStateData));
		} catch (ItemTypeDeserializeException $e) {
			throw TypeConversionException::wrap($e, "Invalid network itemstack data");
		}
	}
}
