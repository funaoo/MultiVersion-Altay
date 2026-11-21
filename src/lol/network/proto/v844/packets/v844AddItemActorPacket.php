<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\global\MVTypeConverter;
use lol\network\proto\v844\structure\v844CommonTypes;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844AddItemActorPacket extends AddItemActorPacket {

	static public function fromLatest(AddItemActorPacket $packet, MVTypeConverter $converter) : self {
		$result = new self();
		$result->actorUniqueId = $packet->actorUniqueId;
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->item = $packet->item;
		$result->position = $packet->position;
		$result->motion = $packet->motion;
		$result->metadata = $packet->metadata;
		$result->isFromFishing = $packet->isFromFishing;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->item = v844ItemStackSerializer::getItemStackWrapper($in);
		$this->position = CommonTypes::getVector3($in);
		$this->motion = CommonTypes::getVector3($in);
		$this->metadata = v844CommonTypes::getEntityMetadata($in);
		$this->isFromFishing = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->item);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putVector3Nullable($out, $this->motion);
		v844CommonTypes::putEntityMetadata($out, $this->metadata);
		CommonTypes::putBool($out, $this->isFromFishing);
	}
}
