<?php

declare(strict_types=1);

namespace lol\network\proto\v84\entities;

use lol\network\NetworkSession;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\packets\v84AddItemEntityPacket;
use lol\network\proto\v84\structure\v84ActorLookup;
use pocketmine\entity\object\ItemEntity;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;

final class v84ItemEntityTranslator
{

	private function __construct()
	{
	}

	public static function addItemEntity(NetworkSession $session, AddItemActorPacket $packet, v84ItemEncoder $items) : ?v84AddItemEntityPacket
	{
		$entity = v84ActorLookup::find($session, $packet->actorRuntimeId);
		if (!$entity instanceof ItemEntity) {
			return null;
		}

		$slot = $items->slot($entity->getItem());
		if ($slot === v84ItemSlot::AIR) {
			return null;
		}

		$motion = $packet->motion;
		return v84AddItemEntityPacket::fromParts(
			$packet->actorRuntimeId,
			$slot,
			$packet->position->x,
			$packet->position->y,
			$packet->position->z,
			$motion?->x ?? 0.0,
			$motion?->y ?? 0.0,
			$motion?->z ?? 0.0
		);
	}
}
