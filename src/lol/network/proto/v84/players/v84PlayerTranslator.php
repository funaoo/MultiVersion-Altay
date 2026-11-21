<?php

declare(strict_types=1);

namespace lol\network\proto\v84\players;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\metadata\v84MetadataConverter;
use lol\network\proto\v84\metadata\v84MetadataWriter;
use lol\network\proto\v84\packets\types\v84PlayerListEntry;
use lol\network\proto\v84\packets\v84AddPlayerPacket;
use lol\network\proto\v84\packets\v84PlayerListPacket;
use lol\network\proto\v84\packets\v84RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;

final class v84PlayerTranslator
{

	private function __construct()
	{
	}

	public static function addPlayer(AddPlayerPacket $packet, v84MetadataConverter $converter, string $heldSlot = v84ItemSlot::AIR) : v84AddPlayerPacket
	{
		$motion = $packet->motion;
		return v84AddPlayerPacket::fromParts(
			$packet->uuid->getBytes(),
			$packet->username,
			$packet->actorRuntimeId,
			$packet->position->x,
			$packet->position->y,
			$packet->position->z,
			$motion?->x ?? 0.0,
			$motion?->y ?? 0.0,
			$motion?->z ?? 0.0,
			$packet->yaw,
			$packet->headYaw,
			$packet->pitch,
			$heldSlot,
			v84MetadataWriter::encode($converter->humanSnapshot($packet->metadata, $packet->username))
		);
	}

	public static function playerList(PlayerListPacket $packet) : v84PlayerListPacket
	{
		return $packet->type === PlayerListPacket::TYPE_ADD ? self::additions($packet) : self::removals($packet);
	}

	public static function removeActor(RemoveActorPacket $packet) : v84RemoveEntityPacket
	{
		return v84RemoveEntityPacket::fromActorId($packet->actorUniqueId);
	}

	private static function additions(PlayerListPacket $packet) : v84PlayerListPacket
	{
		$entries = [];
		foreach ($packet->entries as $entry) {
			if ($entry->skinData === null) {
				continue;
			}

			$skin = v84SkinConverter::fromSkinData($entry->skinData);
			$entries[] = new v84PlayerListEntry($entry->uuid->getBytes(), $entry->actorUniqueId, $entry->username, $skin->id, $skin->data);
		}

		return v84PlayerListPacket::add($entries);
	}

	private static function removals(PlayerListPacket $packet) : v84PlayerListPacket
	{
		$entries = [];
		foreach ($packet->entries as $entry) {
			$entries[] = v84PlayerListEntry::removal($entry->uuid->getBytes());
		}

		return v84PlayerListPacket::remove($entries);
	}
}
