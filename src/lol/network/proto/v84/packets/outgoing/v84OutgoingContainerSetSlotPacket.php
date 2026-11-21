<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;

class v84OutgoingContainerSetSlotPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CONTAINER_SET_SLOT_PACKET;

	public int $windowId = 0;

	public int $slot = 0;

	public int $hotbarSlot = 0;

	public string $item = v84ItemSlot::AIR;

	static public function fromParts(int $windowId, int $slot, int $hotbarSlot, string $item) : self
	{
		$npk = new self();
		$npk->windowId = $windowId;
		$npk->slot = $slot;
		$npk->hotbarSlot = $hotbarSlot;
		$npk->item = $item;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$this->slot = BE::readUnsignedShort($in);
		$this->hotbarSlot = BE::readUnsignedShort($in);
		$this->item = v84ItemSlot::read($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->windowId);
		BE::writeUnsignedShort($out, $this->slot);
		BE::writeUnsignedShort($out, $this->hotbarSlot);
		$out->writeByteArray($this->item);
	}
}
