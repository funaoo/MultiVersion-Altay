<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\outgoing;

use lol\network\proto\v84\inventory\v84ContainerId;
use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use function count;

class v84OutgoingContainerSetContentPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CONTAINER_SET_CONTENT_PACKET;

	public int $windowId = 0;

	public array $slots = [];

	public array $hotbar = [];

	static public function fromParts(int $windowId, array $slots, array $hotbar) : self
	{
		$npk = new self();
		$npk->windowId = $windowId;
		$npk->slots = $slots;
		$npk->hotbar = $hotbar;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$slotCount = BE::readUnsignedShort($in);
		for ($index = 0; $index < $slotCount; ++$index) {
			$this->slots[] = v84ItemSlot::read($in);
		}

		$hotbarCount = BE::readUnsignedShort($in);
		for ($index = 0; $index < $hotbarCount; ++$index) {
			$this->hotbar[] = BE::readSignedInt($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->windowId);
		BE::writeUnsignedShort($out, count($this->slots));
		foreach ($this->slots as $slot) {
			$out->writeByteArray($slot);
		}

		$hotbar = $this->windowId === v84ContainerId::INVENTORY ? $this->hotbar : [];
		BE::writeUnsignedShort($out, count($hotbar));
		foreach ($hotbar as $link) {
			BE::writeSignedInt($out, $link);
		}
	}
}
