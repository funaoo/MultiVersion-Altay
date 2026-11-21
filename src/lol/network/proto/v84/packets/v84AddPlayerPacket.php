<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84CommonTypes;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84AddPlayerPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ADD_PLAYER_PACKET;

	private const UUID_LENGTH = 16;

	public string $uuidBytes = '';

	public string $username = '';

	public int $actorId = 0;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	public float $motionX = 0.0;

	public float $motionY = 0.0;

	public float $motionZ = 0.0;

	public float $yaw = 0.0;

	public float $headYaw = 0.0;

	public float $pitch = 0.0;

	public string $slot = v84ItemSlot::AIR;

	public string $metadata = '';

	static public function fromParts(
		string $uuidBytes,
		string $username,
		int    $actorId,
		float  $x,
		float  $y,
		float  $z,
		float  $motionX,
		float  $motionY,
		float  $motionZ,
		float  $yaw,
		float  $headYaw,
		float  $pitch,
		string $slot,
		string $metadata
	) : self
	{
		$npk = new self();
		$npk->uuidBytes = $uuidBytes;
		$npk->username = $username;
		$npk->actorId = $actorId;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->motionX = $motionX;
		$npk->motionY = $motionY;
		$npk->motionZ = $motionZ;
		$npk->yaw = $yaw;
		$npk->headYaw = $headYaw;
		$npk->pitch = $pitch;
		$npk->slot = $slot;
		$npk->metadata = $metadata;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->uuidBytes = $in->readByteArray(self::UUID_LENGTH);
		$this->username = v84CommonTypes::getString($in);
		$this->actorId = BE::readSignedLong($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		$this->motionX = BE::readFloat($in);
		$this->motionY = BE::readFloat($in);
		$this->motionZ = BE::readFloat($in);
		$this->yaw = BE::readFloat($in);
		$this->headYaw = BE::readFloat($in);
		$this->pitch = BE::readFloat($in);
		$this->slot = v84ItemSlot::read($in);
		$this->metadata = $in->readByteArray($in->getUnreadLength());
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$out->writeByteArray($this->uuidBytes);
		v84CommonTypes::putString($out, $this->username);
		BE::writeSignedLong($out, $this->actorId);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		BE::writeFloat($out, $this->motionX);
		BE::writeFloat($out, $this->motionY);
		BE::writeFloat($out, $this->motionZ);
		BE::writeFloat($out, $this->yaw);
		BE::writeFloat($out, $this->headYaw);
		BE::writeFloat($out, $this->pitch);
		$out->writeByteArray($this->slot);
		$out->writeByteArray($this->metadata);
	}
}
