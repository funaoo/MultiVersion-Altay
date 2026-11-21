<?php

declare(strict_types=1);

namespace lol\network\legacy;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class LegacyMovePlayerPacket extends MovePlayerPacket {

	public static function fromLatest(MovePlayerPacket $packet) : static {
		$result = new static();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->position = $packet->position;
		$result->pitch = $packet->pitch;
		$result->yaw = $packet->yaw;
		$result->headYaw = $packet->headYaw;
		$result->mode = $packet->mode;
		$result->onGround = $packet->onGround;
		$result->ridingActorRuntimeId = $packet->ridingActorRuntimeId;
		$result->teleportCause = $packet->teleportCause;
		$result->teleportItem = $packet->teleportItem;
		$result->tick = $packet->tick;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->position = CommonTypes::getVector3($in);
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);
		$this->mode = Byte::readUnsigned($in);
		$this->onGround = CommonTypes::getBool($in);
		$this->ridingActorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if ($this->mode === self::MODE_TELEPORT) {
			$this->teleportCause = LE::readSignedInt($in);
			$this->teleportItem = LE::readSignedInt($in);
		} else {
			$this->teleportCause = null;
			$this->teleportItem = null;
		}
		$this->tick = VarInt::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putVector3($out, $this->position);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		LE::writeFloat($out, $this->headYaw);
		Byte::writeUnsigned($out, $this->mode);
		CommonTypes::putBool($out, $this->onGround);
		CommonTypes::putActorRuntimeId($out, $this->ridingActorRuntimeId);
		if ($this->mode === self::MODE_TELEPORT) {
			LE::writeSignedInt($out, $this->teleportCause ?? 0);
			LE::writeSignedInt($out, $this->teleportItem ?? 0);
		}
		VarInt::writeUnsignedLong($out, $this->tick);
	}
}
