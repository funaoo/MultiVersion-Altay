<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\TransferPacket;

class v844TransferPacket extends TransferPacket {

	static public function fromLatest(TransferPacket $packet) : self {
		$result = new self();
		$result->address = $packet->address;
		$result->port = $packet->port;
		$result->reloadWorld = $packet->reloadWorld;
		$result->gatheringJoinInfo = $packet->gatheringJoinInfo;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->address = CommonTypes::getString($in);
		$this->port = LE::readUnsignedShort($in);
		$this->reloadWorld = CommonTypes::getBool($in);
		$this->gatheringJoinInfo = null;
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->address);
		LE::writeUnsignedShort($out, $this->port);
		CommonTypes::putBool($out, $this->reloadWorld);
	}
}
