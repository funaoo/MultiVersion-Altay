<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844AnimatePacket extends AnimatePacket {

	public float $rowingTime = 0.0;

	static public function fromLatest(AnimatePacket $packet) : v844AnimatePacket
	{
		$npk = new self();
		$npk->action = $packet->action;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->action = VarInt::readSignedInt($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if(($this->action & 0x80) !== 0){
			$this->rowingTime = LE::readFloat($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->action);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if(($this->action & 0x80) !== 0){
			LE::writeFloat($out, $this->rowingTime);
		}
	}
}
