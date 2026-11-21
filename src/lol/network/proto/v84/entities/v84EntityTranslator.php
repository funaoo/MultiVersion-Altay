<?php

declare(strict_types=1);

namespace lol\network\proto\v84\entities;

use lol\network\NetworkSession;
use lol\network\proto\v84\metadata\v84MetadataConverter;
use lol\network\proto\v84\metadata\v84MetadataWriter;
use lol\network\proto\v84\packets\v84AddEntityPacket;
use lol\network\proto\v84\structure\v84ActorFeet;
use lol\network\proto\v84\structure\v84ActorLookup;
use pocketmine\network\mcpe\protocol\AddActorPacket;

final class v84EntityTranslator
{

	private function __construct()
	{
	}

	public static function addEntity(NetworkSession $session, AddActorPacket $packet, v84EntityTypeTable $types, v84MetadataConverter $converter) : ?v84AddEntityPacket
	{
		$type = $types->lookup($packet->type);
		if ($type === null) {
			return null;
		}

		$motion = $packet->motion;
		return v84AddEntityPacket::fromParts(
			$packet->actorRuntimeId,
			$type->id,
			$packet->position->x,
			self::feetY($session, $packet),
			$packet->position->z,
			$motion?->x ?? 0.0,
			$motion?->y ?? 0.0,
			$motion?->z ?? 0.0,
			$packet->yaw,
			$packet->pitch,
			v84MetadataWriter::encode($converter->snapshot($packet->metadata, ''))
		);
	}

	private static function feetY(NetworkSession $session, AddActorPacket $packet) : float
	{
		$entity = v84ActorLookup::find($session, $packet->actorRuntimeId);
		if ($entity === null) {
			return $packet->position->y;
		}

		return v84ActorFeet::y($entity, $packet->position->y);
	}
}
