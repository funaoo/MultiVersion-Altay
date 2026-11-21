<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;

final readonly class v84NamedTileTranslator implements v84TileTranslator
{

	public function __construct(
		private string $legacyId
	)
	{
	}

	public function translate(CompoundTag $modern) : CompoundTag
	{
		return v84TileFields::copyCustomName(v84TileFields::header($this->legacyId, $modern), $modern);
	}
}
