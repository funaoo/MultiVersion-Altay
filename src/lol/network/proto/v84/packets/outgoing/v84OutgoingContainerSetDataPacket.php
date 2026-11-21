<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingContainerSetDataPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CONTAINER_SET_DATA_PACKET;

	public int $windowId = 0;

	public int $property = 0;

	public int $value = 0;

	static public function fromParts(int $windowId, int $property, int $value) : self
	{
		$npk = new self();
		$npk->windowId = $windowId;
		$npk->property = $property;
		$npk->value = $value;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$this->property = BE::readUnsignedShort($in);
		$this->value = BE::readUnsignedShort($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->windowId);
		BE::writeUnsignedShort($out, $this->property);
		BE::writeUnsignedShort($out, $this->value);
	}
}
