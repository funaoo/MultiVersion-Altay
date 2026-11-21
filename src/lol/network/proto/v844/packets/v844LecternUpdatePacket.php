<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\LecternUpdatePacket;

class v844LecternUpdatePacket extends LecternUpdatePacket {

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->page = Byte::readUnsigned($in);
		$this->totalPages = Byte::readUnsigned($in);
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->page);
		Byte::writeUnsigned($out, $this->totalPages);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
	}
}
