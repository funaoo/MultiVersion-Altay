<?php

declare(strict_types=1);

namespace lol\network\proto\v84\entities;

use lol\network\NetworkSession;
use lol\network\proto\v84\packets\v84TakeItemEntityPacket;
use lol\network\proto\v84\structure\v84ActorIds;
use pocketmine\network\mcpe\protocol\TakeItemActorPacket;

final class v84PickupTranslator
{

	private function __construct()
	{
	}

	public static function takeItem(NetworkSession $session, TakeItemActorPacket $packet) : v84TakeItemEntityPacket
	{
		return v84TakeItemEntityPacket::fromParts(
			$packet->itemActorRuntimeId,
			v84ActorIds::resolve($session, $packet->takerActorRuntimeId)
		);
	}
}
