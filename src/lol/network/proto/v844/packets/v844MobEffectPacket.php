<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844MobEffectPacket extends MobEffectPacket
{

	public static function fromLatest(MobEffectPacket $pk) : self
	{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->eventId = $pk->eventId;
		$npk->effectId = $pk->effectId;
		$npk->amplifier = $pk->amplifier;
		$npk->particles = $pk->particles;
		$npk->duration = $pk->duration;
		$npk->tick = $pk->tick;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->eventId = Byte::readUnsigned($in);
		$this->effectId = VarInt::readSignedInt($in);
		$this->amplifier = VarInt::readSignedInt($in);
		$this->particles = CommonTypes::getBool($in);
		$this->duration = VarInt::readSignedInt($in);
		$this->tick = VarInt::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		Byte::writeUnsigned($out, $this->eventId);
		VarInt::writeSignedInt($out, $this->effectId);
		VarInt::writeSignedInt($out, $this->amplifier);
		CommonTypes::putBool($out, $this->particles);
		VarInt::writeSignedInt($out, $this->duration);
		VarInt::writeUnsignedLong($out, $this->tick);
	}
}
