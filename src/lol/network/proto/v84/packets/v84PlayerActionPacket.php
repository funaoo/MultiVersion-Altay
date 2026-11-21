<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84DeathPacketHandler;
use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84PlayerActionPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::PLAYER_ACTION_PACKET;

	public int $entityId = 0;

	public int $action = 0;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public int $face = 0;

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return ($handler instanceof v84InGamePacketHandler || $handler instanceof v84DeathPacketHandler) && $handler->handleV84PlayerAction($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->entityId = BE::readSignedLong($in);
		$this->action = BE::readSignedInt($in);
		$this->x = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
		$this->face = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
	}
}
