<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingMobArmorEquipmentPacket extends v84ClientboundPacket
{
	private const SLOT_COUNT = 4;

	public const NETWORK_ID = v84ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET;

	public int $actorId = 0;

	public array $slots = [];

	static public function fromParts(int $actorId, array $slots) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->slots = $slots;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		for ($index = 0; $index < self::SLOT_COUNT; ++$index) {
			$this->slots[] = v84ItemSlot::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		for ($index = 0; $index < self::SLOT_COUNT; ++$index) {
			$out->writeByteArray($this->slots[$index] ?? v84ItemSlot::AIR);
		}
	}
}
