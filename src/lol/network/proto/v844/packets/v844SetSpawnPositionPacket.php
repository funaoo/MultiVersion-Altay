<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;

class v844SetSpawnPositionPacket extends SetSpawnPositionPacket {

	public static function fromLatest(SetSpawnPositionPacket $packet) : self{
		$result = new self();
		$result->spawnType = $packet->spawnType;
		$result->spawnPosition = $packet->spawnPosition;
		$result->dimension = $packet->dimension;
		$result->causingBlockPosition = $packet->causingBlockPosition;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->spawnType = VarInt::readSignedInt($in);
		$this->spawnPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->dimension = VarInt::readSignedInt($in);
		$this->causingBlockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeSignedInt($out, $this->spawnType);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->spawnPosition);
		VarInt::writeSignedInt($out, $this->dimension);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->causingBlockPosition);
	}
}
