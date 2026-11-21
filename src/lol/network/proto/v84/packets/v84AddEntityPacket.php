<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use function strlen;
use function substr;

class v84AddEntityPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ADD_ENTITY_PACKET;

	private const ANGLE_SCALE = 0.71111;

	private const NO_MODIFIERS = 0;

	private const LINK_COUNT_LENGTH = 2;

	private const NO_LINKS = 0;

	public int $actorId = 0;

	public int $typeId = 0;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	public float $motionX = 0.0;

	public float $motionY = 0.0;

	public float $motionZ = 0.0;

	public float $yaw = 0.0;

	public float $pitch = 0.0;

	public string $metadata = '';

	static public function fromParts(
		int    $actorId,
		int    $typeId,
		float  $x,
		float  $y,
		float  $z,
		float  $motionX,
		float  $motionY,
		float  $motionZ,
		float  $yaw,
		float  $pitch,
		string $metadata
	) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->typeId = $typeId;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->motionX = $motionX;
		$npk->motionY = $motionY;
		$npk->motionZ = $motionZ;
		$npk->yaw = $yaw;
		$npk->pitch = $pitch;
		$npk->metadata = $metadata;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->typeId = BE::readSignedInt($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		$this->motionX = BE::readFloat($in);
		$this->motionY = BE::readFloat($in);
		$this->motionZ = BE::readFloat($in);
		$this->yaw = BE::readFloat($in) / self::ANGLE_SCALE;
		$this->pitch = BE::readFloat($in) / self::ANGLE_SCALE;
		BE::readSignedInt($in);
		$rest = $in->readByteArray($in->getUnreadLength());
		$this->metadata = substr($rest, 0, strlen($rest) - self::LINK_COUNT_LENGTH);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		BE::writeSignedInt($out, $this->typeId);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		BE::writeFloat($out, $this->motionX);
		BE::writeFloat($out, $this->motionY);
		BE::writeFloat($out, $this->motionZ);
		BE::writeFloat($out, $this->yaw * self::ANGLE_SCALE);
		BE::writeFloat($out, $this->pitch * self::ANGLE_SCALE);
		BE::writeSignedInt($out, self::NO_MODIFIERS);
		$out->writeByteArray($this->metadata);
		BE::writeUnsignedShort($out, self::NO_LINKS);
	}
}
