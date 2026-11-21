<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84ContainerSetSlotPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CONTAINER_SET_SLOT_PACKET;

	public int $windowId = 0;

	public int $slot = 0;

	public int $hotbarSlot = 0;

	public v84ItemSlotData $item;

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleContainerSetSlot($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$this->slot = BE::readSignedShort($in);
		$this->hotbarSlot = BE::readSignedShort($in);
		$this->item = v84ItemSlot::decode($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
	}
}
