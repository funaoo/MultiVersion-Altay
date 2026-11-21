<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844PlayerActionPacket extends PlayerActionPacket {

	public static function fromLatest(PlayerActionPacket $packet) : self{
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->action = $packet->action;
		$result->blockPosition = $packet->blockPosition;
		$result->resultPosition = $packet->resultPosition;
		$result->face = $packet->face;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->action = VarInt::readSignedInt($in);
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->resultPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->face = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		VarInt::writeSignedInt($out, $this->action);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->resultPosition);
		VarInt::writeSignedInt($out, $this->face);
	}

}
