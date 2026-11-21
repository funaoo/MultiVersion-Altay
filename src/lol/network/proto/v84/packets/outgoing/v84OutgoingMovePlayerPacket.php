<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84MovementMode;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingMovePlayerPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::MOVE_PLAYER_PACKET;

	public int $actorId = 0;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	public float $headYaw = 0.0;

	public float $bodyYaw = 0.0;

	public float $pitch = 0.0;

	public int $mode = v84MovementMode::NORMAL;

	public bool $onGround = false;

	static public function fromParts(int $actorId, float $x, float $y, float $z, float $headYaw, float $bodyYaw, float $pitch, int $mode, bool $onGround) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->headYaw = $headYaw;
		$npk->bodyYaw = $bodyYaw;
		$npk->pitch = $pitch;
		$npk->mode = $mode;
		$npk->onGround = $onGround;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		$this->headYaw = BE::readFloat($in);
		$this->bodyYaw = BE::readFloat($in);
		$this->pitch = BE::readFloat($in);
		$this->mode = Byte::readUnsigned($in);
		$this->onGround = Byte::readUnsigned($in) > 0;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		BE::writeFloat($out, $this->headYaw);
		BE::writeFloat($out, $this->bodyYaw);
		BE::writeFloat($out, $this->pitch);
		Byte::writeUnsigned($out, $this->mode);
		Byte::writeUnsigned($out, $this->onGround ? 1 : 0);
	}
}
