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

class v84EntityEventPacket extends DataPacket implements ClientboundPacket, ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ENTITY_EVENT_PACKET;

	private const DATA_LENGTH = 4;

	public int $actorId = 0;

	public int $event = 0;

	public int $data = 0;

	static public function fromParts(int $actorId, int $event) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->event = $event;
		return $npk;
	}

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleEntityEvent($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->event = Byte::readUnsigned($in);
		$this->data = $in->getUnreadLength() >= self::DATA_LENGTH ? BE::readSignedInt($in) : 0;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		Byte::writeUnsigned($out, $this->event);
	}
}
