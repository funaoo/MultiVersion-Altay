<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingMobEquipmentPacket extends v84ClientboundPacket
{
	private const BYTE_MASK = 0xff;

	public const NETWORK_ID = v84ProtocolInfo::MOB_EQUIPMENT_PACKET;

	public int $actorId = 0;

	public string $item = v84ItemSlot::AIR;

	public int $slot = 0;

	public int $selectedSlot = 0;

	static public function fromParts(int $actorId, string $item, int $slot, int $selectedSlot) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->item = $item;
		$npk->slot = $slot;
		$npk->selectedSlot = $selectedSlot;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->item = v84ItemSlot::read($in);
		$this->slot = Byte::readUnsigned($in);
		$this->selectedSlot = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		$out->writeByteArray($this->item);
		Byte::writeUnsigned($out, $this->slot & self::BYTE_MASK);
		Byte::writeUnsigned($out, $this->selectedSlot & self::BYTE_MASK);
	}
}
