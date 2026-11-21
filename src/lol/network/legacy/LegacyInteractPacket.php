<?php

declare(strict_types=1);

namespace lol\network\legacy;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class LegacyInteractPacket extends InteractPacket {

	public const NETWORK_ID = -1;

	public float $x;
	public float $y;
	public float $z;

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->action = Byte::readUnsigned($in);
		$this->targetActorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if ($this->action === self::ACTION_MOUSEOVER) {
			$this->x = LE::readFloat($in);
			$this->y = LE::readFloat($in);
			$this->z = LE::readFloat($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		Byte::writeUnsigned($out, $this->action);
		CommonTypes::putActorRuntimeId($out, $this->targetActorRuntimeId);
		if ($this->action === self::ACTION_MOUSEOVER) {
			LE::writeFloat($out, $this->x);
			LE::writeFloat($out, $this->y);
			LE::writeFloat($out, $this->z);
		}
	}
}
