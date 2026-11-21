<?php

declare(strict_types=1);

namespace lol\network\proto\v84\movement;

use lol\network\NetworkSession;
use lol\network\proto\v84\entities\v84EntityTypeTable;
use lol\network\proto\v84\packets\outgoing\v84OutgoingMovePlayerPacket;
use lol\network\proto\v84\packets\v84MoveEntityPacket;
use lol\network\proto\v84\packets\v84SetEntityMotionPacket;
use lol\network\proto\v84\structure\v84ActorFeet;
use lol\network\proto\v84\structure\v84ActorIds;
use lol\network\proto\v84\structure\v84ActorLookup;
use lol\network\proto\v84\structure\v84MovementMode;
use pocketmine\entity\Human;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;

final class v84MovementTranslator
{

	private const PLAYER_EYE_HEIGHT = 1.62;

	private function __construct()
	{
	}

	public static function playerMove(NetworkSession $session, MovePlayerPacket $packet) : ?v84OutgoingMovePlayerPacket
	{
		$entity = v84ActorLookup::find($session, $packet->actorRuntimeId);
		if ($entity === null) {
			return null;
		}

		return v84OutgoingMovePlayerPacket::fromParts(
			v84ActorIds::resolve($session, $packet->actorRuntimeId),
			$packet->position->x,
			v84ActorFeet::y($entity, $packet->position->y) + self::PLAYER_EYE_HEIGHT,
			$packet->position->z,
			$packet->headYaw,
			$packet->yaw,
			$packet->pitch,
			v84MovementMode::fromLatest($packet->mode),
			$packet->onGround
		);
	}

	public static function actorMove(NetworkSession $session, MoveActorAbsolutePacket $packet, v84EntityTypeTable $types) : ?ClientboundPacket
	{
		$entity = v84ActorLookup::find($session, $packet->actorRuntimeId);
		if ($entity === null) {
			return null;
		}

		$actorId = v84ActorIds::resolve($session, $packet->actorRuntimeId);
		$feetY = v84ActorFeet::y($entity, $packet->position->y);
		if ($entity instanceof Human) {
			return v84OutgoingMovePlayerPacket::fromParts(
				$actorId,
				$packet->position->x,
				$feetY + self::PLAYER_EYE_HEIGHT,
				$packet->position->z,
				$packet->headYaw,
				$packet->yaw,
				$packet->pitch,
				v84MovementMode::NORMAL,
				($packet->flags & MoveActorAbsolutePacket::FLAG_GROUND) !== 0
			);
		}

		return v84MoveEntityPacket::fromParts(
			$actorId,
			$packet->position->x,
			$feetY + $types->eyeHeight($entity),
			$packet->position->z,
			$packet->pitch,
			$packet->yaw,
			$packet->headYaw
		);
	}

	public static function motion(NetworkSession $session, SetActorMotionPacket $packet) : v84SetEntityMotionPacket
	{
		return v84SetEntityMotionPacket::fromParts(
			v84ActorIds::resolve($session, $packet->actorRuntimeId),
			$packet->motion->x,
			$packet->motion->y,
			$packet->motion->z
		);
	}
}
