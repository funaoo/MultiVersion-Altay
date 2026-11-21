<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\MoveActorDeltaPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844MoveActorDeltaPacket extends MoveActorDeltaPacket {

	private const FLAG_HAS_X = 0x01;
	private const FLAG_HAS_Y = 0x02;
	private const FLAG_HAS_Z = 0x04;
	private const FLAG_HAS_PITCH = 0x08;
	private const FLAG_HAS_YAW = 0x10;
	private const FLAG_HAS_HEAD_YAW = 0x20;
	private const FLAG_GROUND = 0x40;
	private const FLAG_TELEPORT = 0x80;
	private const FLAG_FORCE_MOVE_LOCAL_ENTITY = 0x100;

	private int $flags = 0;

	public static function fromLatest(MoveActorDeltaPacket $packet) : self {
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;

		$flags = 0;
		if ($packet->xPos !== null) {
			$flags |= self::FLAG_HAS_X;
			$result->xPos = $packet->xPos;
		}
		if ($packet->yPos !== null) {
			$flags |= self::FLAG_HAS_Y;
			$result->yPos = $packet->yPos;
		}
		if ($packet->zPos !== null) {
			$flags |= self::FLAG_HAS_Z;
			$result->zPos = $packet->zPos;
		}
		if ($packet->pitch !== null) {
			$flags |= self::FLAG_HAS_PITCH;
			$result->pitch = $packet->pitch;
		}
		if ($packet->yaw !== null) {
			$flags |= self::FLAG_HAS_YAW;
			$result->yaw = $packet->yaw;
		}
		if ($packet->headYaw !== null) {
			$flags |= self::FLAG_HAS_HEAD_YAW;
			$result->headYaw = $packet->headYaw;
		}
		if ($packet->onGround) {
			$flags |= self::FLAG_GROUND;
		}
		if ($packet->teleport) {
			$flags |= self::FLAG_TELEPORT;
		}
		if ($packet->forceMoveLocalEntity) {
			$flags |= self::FLAG_FORCE_MOVE_LOCAL_ENTITY;
		}
		$result->flags = $flags;

		return $result;
	}

	private function maybeReadCoord(int $flag, ByteBufferReader $in) : ?float {
		if (($this->flags & $flag) !== 0) {
			return LE::readFloat($in);
		}
		return null;
	}

	private function maybeReadRotation(int $flag, ByteBufferReader $in) : ?float {
		if (($this->flags & $flag) !== 0) {
			return CommonTypes::getRotationByte($in);
		}
		return null;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->flags = LE::readUnsignedShort($in);
		$this->xPos = $this->maybeReadCoord(self::FLAG_HAS_X, $in);
		$this->yPos = $this->maybeReadCoord(self::FLAG_HAS_Y, $in);
		$this->zPos = $this->maybeReadCoord(self::FLAG_HAS_Z, $in);
		$this->pitch = $this->maybeReadRotation(self::FLAG_HAS_PITCH, $in);
		$this->yaw = $this->maybeReadRotation(self::FLAG_HAS_YAW, $in);
		$this->headYaw = $this->maybeReadRotation(self::FLAG_HAS_HEAD_YAW, $in);
		$this->onGround = ($this->flags & self::FLAG_GROUND) !== 0;
		$this->teleport = ($this->flags & self::FLAG_TELEPORT) !== 0;
		$this->forceMoveLocalEntity = ($this->flags & self::FLAG_FORCE_MOVE_LOCAL_ENTITY) !== 0;
		$this->forceCompletion = false;
		$this->ticks = 0;
	}

	private function maybeWriteCoord(int $flag, ?float $val, ByteBufferWriter $out) : void {
		if (($this->flags & $flag) !== 0 && $val !== null) {
			LE::writeFloat($out, $val);
		}
	}

	private function maybeWriteRotation(int $flag, ?float $val, ByteBufferWriter $out) : void {
		if (($this->flags & $flag) !== 0 && $val !== null) {
			CommonTypes::putRotationByte($out, $val);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		LE::writeUnsignedShort($out, $this->flags);
		$this->maybeWriteCoord(self::FLAG_HAS_X, $this->xPos, $out);
		$this->maybeWriteCoord(self::FLAG_HAS_Y, $this->yPos, $out);
		$this->maybeWriteCoord(self::FLAG_HAS_Z, $this->zPos, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_PITCH, $this->pitch, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_YAW, $this->yaw, $out);
		$this->maybeWriteRotation(self::FLAG_HAS_HEAD_YAW, $this->headYaw, $out);
	}
}
