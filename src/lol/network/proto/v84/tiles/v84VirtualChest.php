<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use lol\network\proto\v84\blocks\v84LegacyBlock;
use lol\network\proto\v84\chunk\v84ChunkSerializer;
use lol\network\proto\v84\packets\v84BlockEntityDataPacket;
use lol\network\proto\v84\packets\v84UpdateBlockPacket;
use lol\network\proto\v84\structure\v84WorldLimits;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

final class v84VirtualChest
{

	public const CHEST_BLOCK_ID = 54;

	private function __construct()
	{
	}

	public static function tile(NetworkSession $session, UpdateBlockPacket $packet, int $packedBlock, v84ChunkSerializer $serializer) : ?v84BlockEntityDataPacket
	{
		$position = $packet->blockPosition;
		if ($packet->dataLayerId !== v84UpdateBlockPacket::NORMAL_DATA_LAYER || v84LegacyBlock::id($packedBlock) !== self::CHEST_BLOCK_ID || !v84WorldLimits::isBlockY($position->getY())) {
			return null;
		}

		$world = $session->getPlayer()?->getWorld();
		if ($world === null || $world->getTileAt($position->getX(), $position->getY(), $position->getZ()) !== null) {
			return null;
		}

		$namedTag = $serializer->serializeBlockEntity(
			CompoundTag::create()
				->setString(v84TileTag::ID, v84TileId::CHEST)
				->setInt(v84TileTag::X, $position->getX())
				->setInt(v84TileTag::Y, $position->getY())
				->setInt(v84TileTag::Z, $position->getZ())
		);
		if ($namedTag === '') {
			return null;
		}

		$npk = new v84BlockEntityDataPacket();
		$npk->x = $position->getX();
		$npk->y = $position->getY();
		$npk->z = $position->getZ();
		$npk->namedTag = $namedTag;

		return $npk;
	}
}
