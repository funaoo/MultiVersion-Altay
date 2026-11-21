<?php

declare(strict_types=1);

namespace lol\network\proto\v84\links;

use lol\network\NetworkSession;
use lol\network\proto\v84\entities\v84EntityTypeTable;
use lol\network\proto\v84\packets\v84SetEntityLinkPacket;
use lol\network\proto\v84\structure\v84ActorIds;
use lol\network\proto\v84\structure\v84ActorLookup;
use pocketmine\entity\Human;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;

final class v84LinkTranslator
{

	private function __construct()
	{
	}

	public static function link(NetworkSession $session, SetActorLinkPacket $packet, v84EntityTypeTable $types) : ?v84SetEntityLinkPacket
	{
		$type = match ($packet->link->type) {
			EntityLink::TYPE_RIDER => v84LinkType::MOUNT,
			EntityLink::TYPE_REMOVE => v84LinkType::DISMOUNT,
			default => null
		};
		if ($type === null) {
			return null;
		}

		$vehicleId = $packet->link->fromActorUniqueId;
		$riderId = $packet->link->toActorUniqueId;
		if (!self::isKnownToClient($session, $vehicleId, $types) || !self::isKnownToClient($session, $riderId, $types)) {
			return null;
		}

		return v84SetEntityLinkPacket::fromParts(v84ActorIds::resolve($session, $vehicleId), v84ActorIds::resolve($session, $riderId), $type);
	}

	private static function isKnownToClient(NetworkSession $session, int $runtimeId, v84EntityTypeTable $types) : bool
	{
		$entity = v84ActorLookup::find($session, $runtimeId);
		if ($entity === null) {
			return false;
		}

		return $entity instanceof Human || $types->lookup($entity::getNetworkTypeId()) !== null;
	}
}
