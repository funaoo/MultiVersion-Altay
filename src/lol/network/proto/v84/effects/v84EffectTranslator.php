<?php

declare(strict_types=1);

namespace lol\network\proto\v84\effects;

use lol\network\NetworkSession;
use lol\network\proto\v84\packets\v84MobEffectPacket;
use lol\network\proto\v84\structure\v84ActorIds;
use pocketmine\network\mcpe\protocol\MobEffectPacket;

final class v84EffectTranslator
{

	private function __construct()
	{
	}

	public static function mobEffect(NetworkSession $session, MobEffectPacket $packet, v84EffectTable $effects) : ?v84MobEffectPacket
	{
		if ($packet->actorRuntimeId !== $session->getPlayer()?->getId()) {
			return null;
		}

		$effectId = $effects->lookup($packet->effectId);
		if ($effectId === null) {
			return null;
		}

		return match ($packet->eventId) {
			MobEffectPacket::EVENT_ADD => self::applied(v84MobEffectPacket::EVENT_ADD, $effectId, $packet),
			MobEffectPacket::EVENT_MODIFY => self::applied(v84MobEffectPacket::EVENT_MODIFY, $effectId, $packet),
			MobEffectPacket::EVENT_REMOVE => v84MobEffectPacket::fromParts(v84ActorIds::LOCAL_PLAYER, v84MobEffectPacket::EVENT_REMOVE, $effectId, 0, true, 0),
			default => null
		};
	}

	private static function applied(int $event, int $effectId, MobEffectPacket $packet) : v84MobEffectPacket
	{
		return v84MobEffectPacket::fromParts(v84ActorIds::LOCAL_PLAYER, $event, $effectId, $packet->amplifier, $packet->particles, self::duration($packet->duration));
	}

	private static function duration(int $modernDuration) : int
	{
		return $modernDuration < 0 ? v84MobEffectPacket::MAX_DURATION : min($modernDuration, v84MobEffectPacket::MAX_DURATION);
	}
}
