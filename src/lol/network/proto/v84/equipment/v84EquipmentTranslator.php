<?php

declare(strict_types=1);

namespace lol\network\proto\v84\equipment;

use lol\network\NetworkSession;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\packets\outgoing\v84OutgoingMobArmorEquipmentPacket;
use lol\network\proto\v84\packets\outgoing\v84OutgoingMobEquipmentPacket;
use lol\network\proto\v84\structure\v84ActorIds;
use lol\network\proto\v84\structure\v84ActorLookup;
use pocketmine\entity\Human;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;

final class v84EquipmentTranslator
{

	private const ARMOR_SLOT_COUNT = 4;

	private function __construct()
	{
	}

	public static function heldItem(NetworkSession $session, MobEquipmentPacket $packet, v84ItemEncoder $items) : ?v84OutgoingMobEquipmentPacket
	{
		$entity = v84ActorLookup::find($session, $packet->actorRuntimeId);
		if (!$entity instanceof Human) {
			return null;
		}

		$inventory = $entity->getInventory();
		$index = $inventory->getHeldItemIndex();

		return v84OutgoingMobEquipmentPacket::fromParts(
			v84ActorIds::resolve($session, $packet->actorRuntimeId),
			$items->slot($inventory->getItemInHand()),
			$index,
			$index
		);
	}

	public static function armor(NetworkSession $session, MobArmorEquipmentPacket $packet, v84ItemEncoder $items) : ?v84OutgoingMobArmorEquipmentPacket
	{
		$actorId = v84ActorIds::resolve($session, $packet->actorRuntimeId);
		if ($actorId === v84ActorIds::LOCAL_PLAYER) {
			return null;
		}

		$entity = v84ActorLookup::find($session, $packet->actorRuntimeId);
		if (!$entity instanceof Human) {
			return null;
		}

		$armor = $entity->getArmorInventory();
		$slots = [];
		for ($index = 0; $index < self::ARMOR_SLOT_COUNT; ++$index) {
			$slots[] = $items->slot($armor->getItem($index));
		}

		return v84OutgoingMobArmorEquipmentPacket::fromParts($actorId, $slots);
	}
}
