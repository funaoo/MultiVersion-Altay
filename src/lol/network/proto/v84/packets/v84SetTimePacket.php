<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\SetTimePacket;

class v84SetTimePacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::SET_TIME_PACKET;

	public int $time = 0;

	public bool $started = true;

	static public function fromLatest(SetTimePacket $packet, bool $started) : self
	{
		$npk = new self();
		$npk->time = $packet->time;
		$npk->started = $started;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->time = BE::readSignedInt($in);
		$this->started = Byte::readUnsigned($in) !== 0;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, $this->time);
		Byte::writeUnsigned($out, $this->started ? 1 : 0);
	}
}
