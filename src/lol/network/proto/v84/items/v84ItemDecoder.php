<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

use GlobalLogger;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\data\bedrock\item\ItemDeserializer;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\data\bedrock\item\upgrade\ItemDataUpgrader;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function max;
use function min;

final class v84ItemDecoder
{

	private array $reported = [];

	public function __construct(
		private readonly v84ItemEncoder $encoder,
		private readonly ItemDataUpgrader $upgrader,
		private readonly ItemDeserializer $deserializer,
		private readonly v84ItemTagDecoder $tags,
		private readonly v84ItemTable $table
	)
	{
	}

	public static function create(v84ItemEncoder $encoder) : self
	{
		return new self(
			$encoder,
			GlobalItemDataHandlers::getUpgrader(),
			GlobalItemDataHandlers::getDeserializer(),
			new v84ItemTagDecoder(EnchantmentIdMap::getInstance()),
			v84ItemTable::load()
		);
	}

	public function decode(v84ItemSlotData $slot, ?Item $known = null) : ?Item
	{
		if ($slot->isAir() || $slot->count === 0) {
			return VanillaItems::AIR();
		}

		if ($known !== null && !$known->isNull() && $this->encoder->data($known)->sameKind($slot)) {
			return $this->withCount(clone $known, $slot->count);
		}

		$item = $this->fromLegacy($slot);
		if ($item === null) {
			return null;
		}

		$this->tags->apply($item, $slot->compoundTag);

		return $this->withCount($item, $slot->count);
	}

	private function fromLegacy(v84ItemSlotData $slot) : ?Item
	{
		$damage = $slot->damage === v84LegacyItem::ANY_DAMAGE ? 0 : $slot->damage;
		try {
			$item = $this->deserializer->deserializeStack($this->stackOf($slot, $damage));
		} catch (SavedDataLoadingException|ItemTypeDeserializeException $e) {
			$this->reportOnce($slot->id . ':' . $damage, 'Item ' . $slot->id . ':' . $damage . ' from a 0.15.10 client has no modern equivalent: ' . $e->getMessage());

			return null;
		}

		return $item->isNull() ? null : $item;
	}

	private function stackOf(v84ItemSlotData $slot, int $damage) : SavedItemStackData
	{
		$name = $this->table->inboundName($slot->id, $damage);
		if ($name === null) {
			return $this->upgrader->upgradeItemTypeDataInt($slot->id, $damage, $slot->count, null);
		}

		return new SavedItemStackData(new SavedItemData($name), $slot->count, null, null, [], []);
	}

	private function withCount(Item $item, int $count) : Item
	{
		return $item->setCount(max(1, min($count, $item->getMaxStackSize())));
	}

	private function reportOnce(string $key, string $message) : void
	{
		if (isset($this->reported[$key])) {
			return;
		}

		$this->reported[$key] = true;
		GlobalLogger::get()->debug($message);
	}
}
