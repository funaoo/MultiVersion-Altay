<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84SetEntityDataPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_ENTITY_DATA_PACKET;

	public int $actorId = 0;

	public string $metadata = '';

	static public function fromParts(int $actorId, string $metadata) : self
	{
		$npk = new self();
		$npk->actorId = $actorId;
		$npk->metadata = $metadata;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorId = BE::readSignedLong($in);
		$this->metadata = $in->readByteArray($in->getUnreadLength());
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedLong($out, $this->actorId);
		$out->writeByteArray($this->metadata);
	}
}
