<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingContainerClosePacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CONTAINER_CLOSE_PACKET;

	public int $windowId = 0;

	static public function fromParts(int $windowId) : self
	{
		$npk = new self();
		$npk->windowId = $windowId;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->windowId);
	}
}
