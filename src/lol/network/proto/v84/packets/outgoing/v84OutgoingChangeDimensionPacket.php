<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingChangeDimensionPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CHANGE_DIMENSION_PACKET;

	private const NO_RESPAWN = 0;

	public int $dimension = 0;

	public float $x = 0.0;

	public float $y = 0.0;

	public float $z = 0.0;

	static public function fromParts(int $dimension, float $x, float $y, float $z) : self
	{
		$npk = new self();
		$npk->dimension = $dimension;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->dimension = Byte::readUnsigned($in);
		$this->x = BE::readFloat($in);
		$this->y = BE::readFloat($in);
		$this->z = BE::readFloat($in);
		Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->dimension);
		BE::writeFloat($out, $this->x);
		BE::writeFloat($out, $this->y);
		BE::writeFloat($out, $this->z);
		Byte::writeUnsigned($out, self::NO_RESPAWN);
	}
}
