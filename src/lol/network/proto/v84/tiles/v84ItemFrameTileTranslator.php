<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\block\tile\ItemFrame;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;

final readonly class v84ItemFrameTileTranslator implements v84ItemTileTranslator
{

	private const DEFAULT_ROTATION = 0;

	private const DEFAULT_DROP_CHANCE = 1.0;

	private const ROTATION_COUNT = 8;

	public function __construct(
		private ?v84TileItems $items = null
	)
	{
	}

	public function translate(CompoundTag $modern) : CompoundTag
	{
		return $this->frame($modern, null, self::DEFAULT_ROTATION);
	}

	public function translateTile(Spawnable $tile, CompoundTag $modern) : CompoundTag
	{
		if ($this->items === null || !$tile instanceof ItemFrame) {
			return $this->translate($modern);
		}

		$item = $this->items->compound($tile->getItem(), null);
		if ($item === null) {
			return $this->translate($modern);
		}

		return $this->frame($modern, $item, $tile->getItemRotation() % self::ROTATION_COUNT);
	}

	private function frame(CompoundTag $modern, ?CompoundTag $item, int $rotation) : CompoundTag
	{
		$frame = v84TileFields::header(v84TileId::ITEM_FRAME, $modern);
		if ($item !== null) {
			$frame->setTag(v84TileTag::ITEM_FRAME_ITEM, $item);
		}

		return $frame
			->setByte(v84TileTag::ITEM_ROTATION, $rotation)
			->setFloat(v84TileTag::ITEM_DROP_CHANCE, self::DEFAULT_DROP_CHANCE);
	}
}
