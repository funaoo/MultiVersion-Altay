<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

use GlobalLogger;
use lol\network\proto\v84\blocks\v84BlockResolver;
use lol\network\proto\v84\blocks\v84LegacyBlock;
use pocketmine\data\bedrock\item\ItemSerializer;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\data\bedrock\item\SavedItemData;
use pocketmine\item\Durable;
use pocketmine\item\Item;

final class v84ItemResolver
{

	private array $reported = [];

	public function __construct(
		private readonly v84ItemTable $table,
		private readonly v84BlockResolver $blocks,
		private readonly ItemSerializer $serializer
	)
	{
	}

	public function resolve(Item $item) : int
	{
		if ($item->isNull()) {
			return v84LegacyItem::AIR;
		}

		try {
			$data = $this->serializer->serializeType($item);
		} catch (ItemTypeSerializeException $e) {
			$this->reportOnce('type:' . $item->getName(), 'Item ' . $item->getName() . ' cannot be serialized, it is sent as the neutral item: ' . $e->getMessage());

			return $this->table->neutral();
		}

		$packed = $this->resolveData($data);
		if (!$item instanceof Durable || $packed === $this->table->neutral()) {
			return $packed;
		}

		return v84LegacyItem::pack(v84LegacyItem::id($packed), $item->getDamage());
	}

	public function resolveData(SavedItemData $data) : int
	{
		$packed = $this->table->lookup($data->getName(), $data->getMeta());
		if ($packed !== null) {
			return $packed;
		}

		$block = $data->getBlock();
		if ($block !== null) {
			$blockPacked = $this->blocks->resolveStateData($block);
			if ($blockPacked !== v84LegacyBlock::AIR) {
				return $this->table->blockItem(v84LegacyBlock::id($blockPacked), v84LegacyBlock::meta($blockPacked));
			}
		}

		$packed = $this->table->family($data->getName());
		if ($packed !== null) {
			return $packed;
		}

		$this->reportOnce('name:' . $data->getName(), 'Item ' . $data->getName() . ' has no 0.15.10 equivalent, it is sent as the neutral item');

		return $this->table->neutral();
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
