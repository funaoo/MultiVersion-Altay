<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84SetEntityMotionPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_ENTITY_MOTION_PACKET;

	public int $actorId = 0;

	public float $motionX = 0.0;

	public float $motionY = 0.0;

	public float $motionZ = 0.0;

	static public function fromParts(int $actorId, float $motionX, float $motionY, float $motionZ) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->motionX = $motionX;
		$npk->motionY = $motionY;
		$npk->motionZ = $motionZ;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->motionX = BE::readFloat($in);
		$this->motionY = BE::readFloat($in);
		$this->motionZ = BE::readFloat($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		BE::writeFloat($out, $this->motionX);
		BE::writeFloat($out, $this->motionY);
		BE::writeFloat($out, $this->motionZ);
	}
}
