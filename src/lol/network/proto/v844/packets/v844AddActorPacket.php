<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\mcpe\MVEntityMetadataTranslator;
use lol\network\proto\v844\structure\v844CommonTypes;
use lol\network\proto\v844\v844TypeConverter;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use function count;

class v844AddActorPacket extends AddActorPacket {

	static public function fromLatest(AddActorPacket $packet) : self {
		$result = new self();
		$result->actorUniqueId = $packet->actorUniqueId;
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->type = $packet->type;
		$result->position = $packet->position;
		$result->motion = $packet->motion;
		$result->pitch = $packet->pitch;
		$result->yaw = $packet->yaw;
		$result->headYaw = $packet->headYaw;
		$result->bodyYaw = $packet->bodyYaw;
		$result->attributes = $packet->attributes;
		$result->metadata = MVEntityMetadataTranslator::translateBlockRuntimeIds($packet->type, $packet->metadata, v844TypeConverter::getInstance()->getConverter());
		$result->syncedProperties = $packet->syncedProperties;
		$result->links = $packet->links;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->type = CommonTypes::getString($in);
		$this->position = CommonTypes::getVector3($in);
		$this->motion = CommonTypes::getVector3($in);
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);
		$this->bodyYaw = LE::readFloat($in);

		$this->attributes = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$id = CommonTypes::getString($in);
			$min = LE::readFloat($in);
			$current = LE::readFloat($in);
			$max = LE::readFloat($in);
			$this->attributes[] = new Attribute($id, $min, $max, $current, $current, []);
		}

		$this->metadata = v844CommonTypes::getEntityMetadata($in);
		$this->syncedProperties = PropertySyncData::read($in);

		$this->links = [];
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$this->links[] = CommonTypes::getEntityLink($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putString($out, $this->type);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putVector3Nullable($out, $this->motion);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		LE::writeFloat($out, $this->headYaw);
		LE::writeFloat($out, $this->bodyYaw);

		VarInt::writeUnsignedInt($out, count($this->attributes));
		foreach($this->attributes as $attribute){
			CommonTypes::putString($out, $attribute->getId());
			LE::writeFloat($out, $attribute->getMin());
			LE::writeFloat($out, $attribute->getCurrent());
			LE::writeFloat($out, $attribute->getMax());
		}

		v844CommonTypes::putEntityMetadata($out, $this->metadata);
		$this->syncedProperties->write($out);

		VarInt::writeUnsignedInt($out, count($this->links));
		foreach($this->links as $link){
			CommonTypes::putEntityLink($out, $link);
		}
	}
}
