<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

use lol\network\NetworkSession;
use lol\network\proto\v84\packets\v84SetEntityDataPacket;
use lol\network\proto\v84\structure\v84ActorIds;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;

final class v84MetadataTranslator
{

	private function __construct()
	{
	}

	public static function entityData(NetworkSession $session, SetActorDataPacket $packet, v84MetadataConverter $converter) : ?v84SetEntityDataPacket
	{
		$entries = $converter->changes($packet->metadata);
		if ($entries === []) {
			return null;
		}

		return v84SetEntityDataPacket::fromParts(
			v84ActorIds::resolve($session, $packet->actorRuntimeId),
			v84MetadataWriter::encode($entries)
		);
	}
}
