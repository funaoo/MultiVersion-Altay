<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\BlockEventPacket;

class v844BlockEventPacket extends BlockEventPacket {

	public static function fromLatest(BlockEventPacket $packet) : self{
		$result = new self();
		$result->blockPosition = $packet->blockPosition;
		$result->eventType = $packet->eventType;
		$result->eventData = $packet->eventData;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->eventType = VarInt::readSignedInt($in);
		$this->eventData = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		VarInt::writeSignedInt($out, $this->eventType);
		VarInt::writeSignedInt($out, $this->eventData);
	}
}
