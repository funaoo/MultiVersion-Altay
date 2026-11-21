<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844ContainerOpenPacket extends ContainerOpenPacket {

	public static function fromLatest(ContainerOpenPacket $packet) : self{
		$result = new self();
		$result->windowId = $packet->windowId;
		$result->windowType = $packet->windowType;
		$result->blockPosition = $packet->blockPosition;
		$result->actorUniqueId = $packet->actorUniqueId;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->windowId = Byte::readUnsigned($in);
		$this->windowType = Byte::readUnsigned($in);
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->windowId);
		Byte::writeUnsigned($out, $this->windowType);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
	}
}
