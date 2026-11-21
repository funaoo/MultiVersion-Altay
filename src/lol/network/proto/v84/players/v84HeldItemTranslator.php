<?php

declare(strict_types=1);

namespace lol\network\proto\v84\players;

use lol\network\NetworkSession;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84ActorLookup;
use pocketmine\entity\Human;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;

final class v84HeldItemTranslator
{

	private function __construct()
	{
	}

	public static function slot(NetworkSession $session, AddPlayerPacket $packet, v84ItemEncoder $items) : string
	{
		$entity = v84ActorLookup::find($session, $packet->actorRuntimeId);
		if (!$entity instanceof Human) {
			return v84ItemSlot::AIR;
		}

		return $items->slot($entity->getInventory()->getItemInHand());
	}
}
