<?php

declare(strict_types=1);

namespace lol\network\proto\v84\explosion;

use lol\network\proto\v84\packets\outgoing\v84OutgoingExplodePacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;

final class v84ExplosionTranslator
{

	public const RADIUS = 4.0;

	private const SEED_EVENT_ID = LevelEvent::ADD_PARTICLE_MASK | ParticleIds::HUGE_EXPLODE_SEED;

	private function __construct()
	{
	}

	public static function translate(LevelEventPacket $packet) : ?v84OutgoingExplodePacket
	{
		if ($packet->eventId !== self::SEED_EVENT_ID || $packet->position === null) {
			return null;
		}

		return v84OutgoingExplodePacket::fromParts($packet->position->getX(), $packet->position->getY(), $packet->position->getZ(), self::RADIUS);
	}
}
