<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v844BlockActorDataPacket extends BlockActorDataPacket {

	public static function fromLatest(BlockActorDataPacket $packet) : self{
		$result = new self();
		$result->blockPosition = $packet->blockPosition;
		$result->nbt = $packet->nbt;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->nbt = new CacheableNbt(CommonTypes::getNbtCompoundRoot($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		$out->writeByteArray($this->nbt->getEncodedNbt());
	}
}
