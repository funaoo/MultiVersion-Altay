<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\structure\v844LevelSoundTranslator;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844LevelSoundEventPacket extends LevelSoundEventPacket {

	private const UNKNOWN_SOUND = "unknown";
	private const UNKNOWN_SOUND_ID = 0xFFFFFFFF;

	static public function fromLatest(LevelSoundEventPacket $packet) : self    {
		$result = new self();
		$result->sound = $packet->sound;
		$result->position = $packet->position;
		$result->extraData = $packet->extraData;
		$result->entityType = $packet->entityType;
		$result->isBabyMob = $packet->isBabyMob;
		$result->disableRelativeVolume = $packet->disableRelativeVolume;
		$result->actorUniqueId = $packet->actorUniqueId;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->sound = v844LevelSoundTranslator::getInstance()->getMap()->toName(VarInt::readUnsignedInt($in)) ?? self::UNKNOWN_SOUND;
		$this->position = CommonTypes::getVector3($in);
		$this->extraData = VarInt::readSignedInt($in);
		$this->entityType = CommonTypes::getString($in);
		$this->isBabyMob = CommonTypes::getBool($in);
		$this->disableRelativeVolume = CommonTypes::getBool($in);
		$this->actorUniqueId = LE::readSignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, v844LevelSoundTranslator::getInstance()->getMap()->toId($this->sound) ?? self::UNKNOWN_SOUND_ID);
		CommonTypes::putVector3($out, $this->position);
		VarInt::writeSignedInt($out, $this->extraData);
		CommonTypes::putString($out, $this->entityType);
		CommonTypes::putBool($out, $this->isBabyMob);
		CommonTypes::putBool($out, $this->disableRelativeVolume);
		LE::writeSignedLong($out, $this->actorUniqueId);
	}
}
