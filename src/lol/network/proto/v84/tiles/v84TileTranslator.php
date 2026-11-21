<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;

interface v84TileTranslator
{

	public function translate(CompoundTag $modern) : CompoundTag;
}
