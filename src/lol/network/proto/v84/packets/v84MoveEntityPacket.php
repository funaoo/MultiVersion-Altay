<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84AngleCodec;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84MoveEntityPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::MOVE_ENTITY_PACKET;

	public int $actorId = 0;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	public float $pitch = 0.0;

	public float $yaw = 0.0;

	public float $headYaw = 0.0;

	static public function fromParts(int $actorId, float $x, float $y, float $z, float $pitch, float $yaw, float $headYaw) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->pitch = $pitch;
		$npk->yaw = $yaw;
		$npk->headYaw = $headYaw;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		$this->pitch = v84AngleCodec::toDegrees(Byte::readUnsigned($in));
		$this->yaw = v84AngleCodec::toDegrees(Byte::readUnsigned($in));
		$this->headYaw = v84AngleCodec::toDegrees(Byte::readUnsigned($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		Byte::writeUnsigned($out, v84AngleCodec::toUnit($this->pitch));
		Byte::writeUnsigned($out, v84AngleCodec::toUnit($this->yaw));
		Byte::writeUnsigned($out, v84AngleCodec::toUnit($this->headYaw));
	}
}
