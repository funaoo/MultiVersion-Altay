<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84SetEntityLinkPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_ENTITY_LINK_PACKET;

	public int $fromId = 0;

	public int $toId = 0;

	public int $type = 0;

	static public function fromParts(int $fromId, int $toId, int $type) : self
	{
		$npk = new self();
		$npk->fromId = $fromId;
		$npk->toId = $toId;
		$npk->type = $type;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->fromId = BE::readSignedLong($in);
		$this->toId = BE::readSignedLong($in);
		$this->type = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->fromId);
		BE::writeSignedLong($out, $this->toId);
		Byte::writeUnsigned($out, $this->type);
	}
}
