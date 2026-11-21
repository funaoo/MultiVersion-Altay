<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84RemoveEntityPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::REMOVE_ENTITY_PACKET;

	public int $actorId = 0;

	static public function fromActorId(int $actorId) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
	}
}
