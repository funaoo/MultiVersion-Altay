<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;
use function intdiv;
use function round;

final readonly class v84SkullTileTranslator implements v84TileTranslator
{

	private const TYPE_MAX = 4;

	private const ROTATION_STEPS = 16;

	private const DEGREES_PER_STEP = 22.5;

	private const NO_ROTATION = -1;

	public function translate(CompoundTag $modern) : CompoundTag
	{
		$type = v84TileFields::intOf($modern, v84TileTag::SKULL_TYPE, 0);

		return v84TileFields::header(v84TileId::SKULL, $modern)
			->setByte(v84TileTag::SKULL_TYPE, $type >= 0 && $type <= self::TYPE_MAX ? $type : 0)
			->setByte(v84TileTag::ROT, $this->rotation($modern));
	}

	private function rotation(CompoundTag $modern) : int
	{
		$steps = v84TileFields::intOf($modern, v84TileTag::ROT, self::NO_ROTATION);
		if ($steps >= 0) {
			return $steps % self::ROTATION_STEPS;
		}

		$degrees = v84TileFields::floatOf($modern, v84TileTag::ROTATION, 0.0);

		return (int) round($degrees / self::DEGREES_PER_STEP) % self::ROTATION_STEPS;
	}
}
