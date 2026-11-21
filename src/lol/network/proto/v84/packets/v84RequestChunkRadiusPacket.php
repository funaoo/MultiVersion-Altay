<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;

class v84RequestChunkRadiusPacket extends RequestChunkRadiusPacket
{
	public const NETWORK_ID = v84ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->radius = BE::readSignedInt($in);
	}
}
