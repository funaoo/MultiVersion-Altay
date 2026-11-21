<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84InteractPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::INTERACT_PACKET;

	public int $action = 0;

	public int $targetId = 0;

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleV84Interact($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->action = Byte::readUnsigned($in);
		$this->targetId = BE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
	}
}
