<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\structure\v844CommonTypes;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class v844AddPlayerPacket extends AddPlayerPacket {

	static public function fromLatest(AddPlayerPacket $packet) : self {
		$result = new self();
		$result->uuid = $packet->uuid;
		$result->username = $packet->username;
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->platformChatId = $packet->platformChatId;
		$result->position = $packet->position;
		$result->motion = $packet->motion;
		$result->pitch = $packet->pitch;
		$result->yaw = $packet->yaw;
		$result->headYaw = $packet->headYaw;
		$result->item = $packet->item;
		$result->gameMode = $packet->gameMode;
		$result->metadata = $packet->metadata;
		$result->syncedProperties = $packet->syncedProperties;
		$result->abilitiesPacket = $packet->abilitiesPacket;
		$result->links = $packet->links;
		$result->deviceId = $packet->deviceId;
		$result->buildPlatform = $packet->buildPlatform;
		return $result;
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->username);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putString($out, $this->platformChatId);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putVector3Nullable($out, $this->motion);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		LE::writeFloat($out, $this->headYaw);
		v844ItemStackSerializer::putItemStackWrapper($out, $this->item);
		VarInt::writeSignedInt($out, $this->gameMode);
		v844CommonTypes::putEntityMetadata($out, $this->metadata);
		$this->syncedProperties->write($out);

		$this->abilitiesPacket->encodePayload($out);

		VarInt::writeUnsignedInt($out, count($this->links));
		foreach($this->links as $link){
			CommonTypes::putEntityLink($out, $link);
		}

		CommonTypes::putString($out, $this->deviceId);
		LE::writeSignedInt($out, $this->buildPlatform);
	}
}
