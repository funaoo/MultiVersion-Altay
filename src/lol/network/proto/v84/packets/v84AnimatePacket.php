<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84AnimatePacket extends DataPacket implements ClientboundPacket, ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ANIMATE_PACKET;

	public int $action = 0;

	public int $actorId = 0;

	static public function fromParts(int $action, int $actorId) : self
	{
		$npk = new self();
		$npk->action = $action;
		$npk->actorId = $actorId;
		return $npk;
	}

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleSwingAnimation($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->action = Byte::readUnsigned($in);
		$this->actorId = BE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->action);
		BE::writeSignedLong($out, $this->actorId);
	}
}
