<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84AddItemEntityPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ADD_ITEM_ENTITY_PACKET;

	public int $actorId = 0;

	public string $slot = v84ItemSlot::AIR;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	public float $motionX = 0.0;

	public float $motionY = 0.0;

	public float $motionZ = 0.0;

	static public function fromParts(
		int    $actorId,
		string $slot,
		float  $x,
		float  $y,
		float  $z,
		float  $motionX,
		float  $motionY,
		float  $motionZ
	) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->slot = $slot;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->motionX = $motionX;
		$npk->motionY = $motionY;
		$npk->motionZ = $motionZ;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->slot = v84ItemSlot::read($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		$this->motionX = BE::readFloat($in);
		$this->motionY = BE::readFloat($in);
		$this->motionZ = BE::readFloat($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		$out->writeByteArray($this->slot);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		BE::writeFloat($out, $this->motionX);
		BE::writeFloat($out, $this->motionY);
		BE::writeFloat($out, $this->motionZ);
	}
}
