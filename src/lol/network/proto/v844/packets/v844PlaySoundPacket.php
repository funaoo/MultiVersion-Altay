<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class v844PlaySoundPacket extends PlaySoundPacket {

	public static function fromLatest(PlaySoundPacket $packet) : self{
		$result = new self();
		$result->soundName = $packet->soundName;
		$result->x = $packet->x;
		$result->y = $packet->y;
		$result->z = $packet->z;
		$result->volume = $packet->volume;
		$result->pitch = $packet->pitch;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->soundName = CommonTypes::getString($in);
		$blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->x = $blockPosition->getX() / 8;
		$this->y = $blockPosition->getY() / 8;
		$this->z = $blockPosition->getZ() / 8;
		$this->volume = LE::readFloat($in);
		$this->pitch = LE::readFloat($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->soundName);
		$blockPosition = new BlockPosition((int) ($this->x * 8), (int) ($this->y * 8), (int) ($this->z * 8));
		RawPacketHelper::putUnsignedYBlockPosition($out, $blockPosition);
		LE::writeFloat($out, $this->volume);
		LE::writeFloat($out, $this->pitch);
	}
}
