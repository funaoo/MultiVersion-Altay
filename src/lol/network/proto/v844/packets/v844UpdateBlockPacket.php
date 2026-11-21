<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class v844UpdateBlockPacket extends UpdateBlockPacket {

	public static function fromLatest(UpdateBlockPacket $packet) : self{
		$result = new self();
		$result->blockPosition = $packet->blockPosition;
		$result->blockRuntimeId = $packet->blockRuntimeId;
		$result->flags = $packet->flags;
		$result->dataLayerId = $packet->dataLayerId;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->blockRuntimeId = VarInt::readUnsignedInt($in);
		$this->flags = VarInt::readUnsignedInt($in);
		$this->dataLayerId = VarInt::readUnsignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		VarInt::writeUnsignedInt($out, $this->blockRuntimeId);
		VarInt::writeUnsignedInt($out, $this->flags);
		VarInt::writeUnsignedInt($out, $this->dataLayerId);
	}
}
