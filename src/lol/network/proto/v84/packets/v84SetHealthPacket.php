<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use function ceil;
use function max;

class v84SetHealthPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_HEALTH_PACKET;

	public int $health = 0;

	static public function fromCurrent(float $health) : self
	{
		$npk = new self();
		$npk->health = (int) ceil(max(0.0, $health));
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->health = BE::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->health);
	}
}
