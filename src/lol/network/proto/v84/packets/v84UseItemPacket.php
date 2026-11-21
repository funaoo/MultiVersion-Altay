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

class v84UseItemPacket extends DataPacket implements ServerboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::USE_ITEM_PACKET;

	public const FACE_AIR = 255;

	public int $x = 0;

	public int $y = 0;

	public int $z = 0;

	public int $face = 0;

	public float $clickX = 0.0;

	public float $clickY = 0.0;

	public float $clickZ = 0.0;

	public float $playerX = 0.0;

	public float $playerY = 0.0;

	public float $playerZ = 0.0;

	public int $slot = 0;

	public v84ItemSlotData $item;

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler instanceof v84InGamePacketHandler && $handler->handleUseItem($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->x = BE::readSignedInt($in);
		$this->y = BE::readSignedInt($in);
		$this->z = BE::readSignedInt($in);
		$this->face = Byte::readUnsigned($in);
		$this->clickX = BE::readFloat($in);
		$this->clickY = BE::readFloat($in);
		$this->clickZ = BE::readFloat($in);
		$this->playerX = BE::readFloat($in);
		$this->playerY = BE::readFloat($in);
		$this->playerZ = BE::readFloat($in);
		$this->slot = BE::readSignedInt($in);
		$this->item = v84ItemSlot::decode($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
	}
}
