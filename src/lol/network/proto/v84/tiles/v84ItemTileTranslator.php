<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;

interface v84ItemTileTranslator extends v84TileTranslator
{

	public function translateTile(Spawnable $tile, CompoundTag $modern) : CompoundTag;
}
