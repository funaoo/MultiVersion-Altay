<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84TakeItemEntityPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::TAKE_ITEM_ENTITY_PACKET;

	public int $targetId = 0;

	public int $takerId = 0;

	static public function fromParts(int $targetId, int $takerId) : self
	{
		$npk = new self();
		$npk->targetId = $targetId;
		$npk->takerId = $takerId;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->targetId = BE::readSignedLong($in);
		$this->takerId = BE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->targetId);
		BE::writeSignedLong($out, $this->takerId);
	}
}
