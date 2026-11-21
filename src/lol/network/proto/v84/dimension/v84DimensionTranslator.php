<?php

declare(strict_types=1);

namespace lol\network\proto\v84\dimension;

use lol\network\proto\v84\packets\outgoing\v84OutgoingChangeDimensionPacket;
use lol\network\proto\v84\packets\v84PlayStatusPacket;
use lol\network\proto\v84\structure\v84WorldLimits;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

final class v84DimensionTranslator
{

	private function __construct()
	{
	}

	public static function changeDimension(ChangeDimensionPacket $packet) : ?v84OutgoingChangeDimensionPacket
	{
		$dimension = match ($packet->dimension) {
			DimensionIds::OVERWORLD => v84WorldLimits::DIMENSION_OVERWORLD,
			DimensionIds::NETHER => v84WorldLimits::DIMENSION_NETHER,
			default => null
		};
		if ($dimension === null) {
			return null;
		}

		return v84OutgoingChangeDimensionPacket::fromParts($dimension, $packet->position->getX(), v84WorldLimits::clampY($packet->position->getY()), $packet->position->getZ());
	}

	public static function playerSpawn(ChangeDimensionPacket $packet) : ?v84PlayStatusPacket
	{
		if (self::changeDimension($packet) === null) {
			return null;
		}

		return v84PlayStatusPacket::fromLatest(PlayStatusPacket::create(PlayStatusPacket::PLAYER_SPAWN));
	}
}
