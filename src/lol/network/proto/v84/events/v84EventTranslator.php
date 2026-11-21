<?php

declare(strict_types=1);

namespace lol\network\proto\v84\events;

use lol\network\NetworkSession;
use lol\network\proto\v84\packets\v84AnimatePacket;
use lol\network\proto\v84\packets\v84EntityEventPacket;
use lol\network\proto\v84\structure\v84ActorIds;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

final class v84EventTranslator
{

	private function __construct()
	{
	}

	public static function actorEvent(NetworkSession $session, ActorEventPacket $packet, v84EventTable $events) : ?ClientboundPacket
	{
		$actorId = v84ActorIds::resolve($session, $packet->actorRuntimeId);
		if ($packet->eventId === ActorEvent::ARM_SWING) {
			return self::swing($actorId);
		}

		$event = $events->lookup($packet->eventId);
		if ($event === null || ($event === v84EntityEventId::USE_ITEM && $actorId === v84ActorIds::LOCAL_PLAYER)) {
			return null;
		}

		return v84EntityEventPacket::fromParts($actorId, $event);
	}

	public static function animate(NetworkSession $session, AnimatePacket $packet) : ?v84AnimatePacket
	{
		$actorId = v84ActorIds::resolve($session, $packet->actorRuntimeId);
		return match ($packet->action) {
			AnimatePacket::ACTION_SWING_ARM => self::swing($actorId),
			AnimatePacket::ACTION_STOP_SLEEP => v84AnimatePacket::fromParts(v84AnimateAction::WAKE_UP, $actorId),
			default => null
		};
	}

	private static function swing(int $actorId) : ?v84AnimatePacket
	{
		return $actorId === v84ActorIds::LOCAL_PLAYER ? null : v84AnimatePacket::fromParts(v84AnimateAction::SWING_ARM, $actorId);
	}
}
