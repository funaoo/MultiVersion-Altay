<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\structure\v844CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;

class v844SetActorDataPacket extends SetActorDataPacket {

	static public function fromLatest(SetActorDataPacket $packet) : self {
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->metadata = $packet->metadata;
		$result->syncedProperties = $packet->syncedProperties;
		$result->tick = $packet->tick;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->metadata = v844CommonTypes::getEntityMetadata($in);
		$this->syncedProperties = PropertySyncData::read($in);
		$this->tick = VarInt::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v844CommonTypes::putEntityMetadata($out, $this->metadata);
		$this->syncedProperties->write($out);
		VarInt::writeUnsignedLong($out, $this->tick);
	}
}
