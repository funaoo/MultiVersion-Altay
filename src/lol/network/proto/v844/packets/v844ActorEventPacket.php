<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\global\MVTypeConverter;
use lol\network\proto\v844\structure\v844ItemIdMetaTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class v844ActorEventPacket extends ActorEventPacket {

	static public function fromLatest(ActorEventPacket $packet, MVTypeConverter $converter) : self {
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->eventId = $packet->eventId;
		$result->eventData = $packet->eventId === ActorEvent::EATING_ITEM ?
			v844ItemIdMetaTranslator::getInstance()->translate($packet->eventData, $converter) ?? 0 :
			$packet->eventData;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->eventId = Byte::readUnsigned($in);
		$this->eventData = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		Byte::writeUnsigned($out, $this->eventId);
		VarInt::writeSignedInt($out, $this->eventData);
	}

}
