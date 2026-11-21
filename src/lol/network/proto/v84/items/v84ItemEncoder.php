<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

use lol\network\proto\v84\blocks\v84BlockResolver;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\Item;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function min;

final class v84ItemEncoder
{

	private const COUNT_MAX = 255;

	public function __construct(
		private readonly v84ItemResolver $resolver,
		private readonly v84ItemTagEncoder $tags
	)
	{
	}

	public static function create() : self
	{
		$resolver = new v84ItemResolver(
			v84ItemTable::load(),
			v84BlockResolver::load(GlobalBlockStateHandlers::getSerializer()),
			GlobalItemDataHandlers::getSerializer()
		);

		return new self($resolver, new v84ItemTagEncoder(EnchantmentIdMap::getInstance()));
	}

	public function data(Item $item) : v84ItemSlotData
	{
		$packed = $this->resolver->resolve($item);
		$id = v84LegacyItem::id($packed);
		if ($id === v84LegacyItem::AIR) {
			return v84ItemSlotData::air();
		}

		return new v84ItemSlotData($id, min($item->getCount(), self::COUNT_MAX), v84LegacyItem::damage($packed), $this->tags->encode($item));
	}

	public function slot(Item $item) : string
	{
		$data = $this->data($item);

		return v84ItemSlot::encode($data->id, $data->count, $data->damage, $data->compoundTag);
	}
}
