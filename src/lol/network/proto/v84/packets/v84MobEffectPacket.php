<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84MobEffectPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::MOB_EFFECT_PACKET;

	public const EVENT_ADD = 1;

	public const EVENT_MODIFY = 2;

	public const EVENT_REMOVE = 3;

	public const MAX_DURATION = 0x7fffffff;

	private const BYTE_MASK = 0xff;

	public int $actorId = 0;

	public int $eventId = 0;

	public int $effectId = 0;

	public int $amplifier = 0;

	public bool $particles = true;

	public int $duration = 0;

	static public function fromParts(int $actorId, int $eventId, int $effectId, int $amplifier, bool $particles, int $duration) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->eventId = $eventId;
		$npk->effectId = $effectId;
		$npk->amplifier = $amplifier;
		$npk->particles = $particles;
		$npk->duration = $duration;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->eventId = Byte::readUnsigned($in);
		$this->effectId = Byte::readUnsigned($in);
		$this->amplifier = Byte::readUnsigned($in);
		$this->particles = Byte::readUnsigned($in) !== 0;
		$this->duration = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		Byte::writeUnsigned($out, $this->eventId & self::BYTE_MASK);
		Byte::writeUnsigned($out, $this->effectId & self::BYTE_MASK);
		Byte::writeUnsigned($out, $this->amplifier & self::BYTE_MASK);
		Byte::writeUnsigned($out, $this->particles ? 1 : 0);
		BE::writeSignedInt($out, $this->duration);
	}
}
