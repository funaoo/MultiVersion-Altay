<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84ContainerOpenPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CONTAINER_OPEN_PACKET;

	public const NO_ENTITY = -1;

	public int $windowId = 0;

	public int $type = 0;

	public int $slots = 0;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public int $entityId = self::NO_ENTITY;

	static public function fromParts(int $windowId, int $type, int $slots, int $x, int $y, int $z) : self
	{
		$npk = new self();
		$npk->windowId = $windowId;
		$npk->type = $type;
		$npk->slots = $slots;
		$npk->x = $x;
		$npk->y = $y;
		$npk->z = $z;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$this->type = Byte::readUnsigned($in);
		$this->slots = BE::readUnsignedShort($in);
		$this->x = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
		$this->entityId = BE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->windowId);
		Byte::writeUnsigned($out, $this->type);
		BE::writeUnsignedShort($out, $this->slots);
		BE::writeSignedInt($out, $this->x);
		BE::writeSignedInt($out, $this->y);
		BE::writeSignedInt($out, $this->z);
		BE::writeSignedLong($out, $this->entityId);
	}
}
