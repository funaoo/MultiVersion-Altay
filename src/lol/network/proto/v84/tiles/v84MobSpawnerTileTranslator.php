<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;

final readonly class v84MobSpawnerTileTranslator implements v84TileTranslator
{

	private const UNKNOWN_ENTITY = 0;

	public function translate(CompoundTag $modern) : CompoundTag
	{
		return v84TileFields::header(v84TileId::MOB_SPAWNER, $modern)
			->setInt(v84TileTag::ENTITY_ID, self::UNKNOWN_ENTITY);
	}
}
