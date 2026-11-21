<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingLevelEventPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::LEVEL_EVENT_PACKET;

	private const SHORT_MASK = 0xffff;

	private const INT_MASK = 0xffffffff;

	private const INT_SIGN = 0x80000000;

	private const INT_RANGE = 0x100000000;

	public int $eventId = 0;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	public int $data = 0;

	static public function fromParts(int $eventId, float $x, float $y, float $z, int $data) : self
	{
		$npk = new self();
		$npk->eventId = $eventId & self::SHORT_MASK;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->data = self::wrapInt($data);
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->eventId = BE::readUnsignedShort($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		$this->data = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeUnsignedShort($out, $this->eventId);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		BE::writeSignedInt($out, $this->data);
	}

	static private function wrapInt(int $value) : int
	{
		$wrapped = $value & self::INT_MASK;
		return $wrapped >= self::INT_SIGN ? $wrapped - self::INT_RANGE : $wrapped;
	}
}
