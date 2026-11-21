<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;

final readonly class v84ChestTileTranslator implements v84TileTranslator
{

	public function translate(CompoundTag $modern) : CompoundTag
	{
		$chest = v84TileFields::header(v84TileId::CHEST, $modern);
		if ($modern->getTag(v84TileTag::PAIR_X) !== null && $modern->getTag(v84TileTag::PAIR_Z) !== null) {
			$chest->setInt(v84TileTag::PAIR_X, v84TileFields::intOf($modern, v84TileTag::PAIR_X, 0));
			$chest->setInt(v84TileTag::PAIR_Z, v84TileFields::intOf($modern, v84TileTag::PAIR_Z, 0));
		}

		return v84TileFields::copyCustomName($chest, $modern);
	}
}
