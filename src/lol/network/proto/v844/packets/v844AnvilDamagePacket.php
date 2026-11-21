<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\utils\RawPacketHelper;
use lol\network\utils\ReflectionUtils;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class v844AnvilDamagePacket extends AnvilDamagePacket {

	private int $damageAmount = 0;

	public static function fromLatest(AnvilDamagePacket $packet) : self{
		$result = new self();
		ReflectionUtils::setProperty(AnvilDamagePacket::class, $result, "blockPosition", $packet->getBlockPosition());
		return $result;
	}

	public function getDamageAmount() : int{ return $this->damageAmount; }

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->damageAmount = Byte::readUnsigned($in);
		ReflectionUtils::setProperty(AnvilDamagePacket::class, $this, "blockPosition", RawPacketHelper::getUnsignedYBlockPosition($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->damageAmount);
		RawPacketHelper::putUnsignedYBlockPosition($out, ReflectionUtils::getProperty(AnvilDamagePacket::class, $this, "blockPosition"));
	}
}