<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ChunkRadiusUpdatedPacket;

class v84ChunkRadiusUpdatedPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CHUNK_RADIUS_UPDATED_PACKET;

	public int $radius = 0;

	static public function fromLatest(ChunkRadiusUpdatedPacket $packet) : self
	{
		$npk = new self();
		$npk->radius = $packet->radius;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->radius = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->radius);
	}
}
