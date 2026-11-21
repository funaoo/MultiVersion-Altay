<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use function count;

class v84OutgoingExplodePacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::EXPLODE_PACKET;

	private const BYTE_MASK = 0xff;

	private const BYTE_SIGN = 0x80;

	private const BYTE_RANGE = 0x100;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	public float $radius = 0.0;

	public array $records = [];

	static public function fromParts(float $x, float $y, float $z, float $radius, array $records = []) : self
	{
		$npk = new self();
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->radius = $radius;
		$npk->records = $records;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		$this->radius = BE::readFloat($in);
		$count = BE::readSignedInt($in);
		$this->records = [];
		for ($i = 0; $i < $count; ++$i) {
			$this->records[] = [self::readSigned($in), self::readSigned($in), self::readSigned($in)];
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		BE::writeFloat($out, $this->radius);
		BE::writeSignedInt($out, count($this->records));
		foreach ($this->records as [$offsetX, $offsetY, $offsetZ]) {
			Byte::writeUnsigned($out, $offsetX & self::BYTE_MASK);
			Byte::writeUnsigned($out, $offsetY & self::BYTE_MASK);
			Byte::writeUnsigned($out, $offsetZ & self::BYTE_MASK);
		}
	}

	static private function readSigned(ByteBufferReader $in) : int
	{
		$value = Byte::readUnsigned($in);
		return $value >= self::BYTE_SIGN ? $value - self::BYTE_RANGE : $value;
	}
}
