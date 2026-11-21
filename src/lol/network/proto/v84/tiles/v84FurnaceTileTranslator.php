<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;

final readonly class v84FurnaceTileTranslator implements v84TileTranslator
{

	public function translate(CompoundTag $modern) : CompoundTag
	{
		$furnace = v84TileFields::header(v84TileId::FURNACE, $modern)
			->setShort(v84TileTag::BURN_TIME, v84TileFields::intOf($modern, v84TileTag::BURN_TIME, 0))
			->setShort(v84TileTag::COOK_TIME, v84TileFields::intOf($modern, v84TileTag::COOK_TIME, 0));

		return v84TileFields::copyCustomName($furnace, $modern);
	}
}
