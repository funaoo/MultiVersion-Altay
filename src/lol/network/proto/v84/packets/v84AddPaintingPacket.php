<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84CommonTypes;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84AddPaintingPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ADD_PAINTING_PACKET;

	public int $actorId = 0;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public int $direction = 0;

	public string $title = '';

	static public function fromParts(int $actorId, int $x, int $y, int $z, int $direction, string $title) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		$npk->direction = $direction;
		$npk->title = $title;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->x = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
		$this->direction = BE::readSignedInt($in);
		$this->title = v84CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		BE::writeSignedInt($out, $this->x);
		BE::writeSignedInt($out, $this->y);
		BE::writeSignedInt($out, $this->z);
		BE::writeSignedInt($out, $this->direction);
		v84CommonTypes::putString($out, $this->title);
	}
}
