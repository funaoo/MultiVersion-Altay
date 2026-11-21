<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84ContainerClosePacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CONTAINER_CLOSE_PACKET;

	public int $windowId = 0;

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleV84ContainerClose($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
	}
}
