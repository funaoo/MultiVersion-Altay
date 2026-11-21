<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\global\MVTypeConverter;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844MobArmorEquipmentPacket extends MobArmorEquipmentPacket {

	static public function fromLatest(MobArmorEquipmentPacket $packet, MVTypeConverter $converter) : self {
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->head = $packet->head;
		$result->chest = $packet->chest;
		$result->legs = $packet->legs;
		$result->feet = $packet->feet;
		$result->body = $packet->body;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->head = v844ItemStackSerializer::getItemStackWrapper($in);
		$this->chest = v844ItemStackSerializer::getItemStackWrapper($in);
		$this->legs = v844ItemStackSerializer::getItemStackWrapper($in);
		$this->feet = v844ItemStackSerializer::getItemStackWrapper($in);
		$this->body = v844ItemStackSerializer::getItemStackWrapper($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->head);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->chest);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->legs);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->feet);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->body);
	}
}
