<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\structure\v84InGamePacketHandler;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v84ItemFrameDropItemPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public string $item = '';

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleItemFrameDropItem($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->z = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->x = BE::readSignedInt($in);
		$this->item = v84ItemSlot::read($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
	}
}
