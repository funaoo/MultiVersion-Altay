<?php

declare(strict_types=1);

namespace lol\network\utils;

use lol\network\legacy\LegacyInteractPacket;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

final class RawPacketHelper
{

	private function __construct(){

	}

	public static function translateInteractPacketToLatest(LegacyInteractPacket $packet) : InteractPacket {
		$result = new InteractPacket();
		$result->action = $packet->action;
		$result->targetActorRuntimeId = $packet->targetActorRuntimeId;
		if ($packet->action === InteractPacket::ACTION_MOUSEOVER) {
			$result->position = new Vector3($packet->x, $packet->y, $packet->z);
		}
		return $result;
	}

	public static function getUnsignedYBlockPosition(ByteBufferReader $in) : BlockPosition {
		$x = VarInt::readSignedInt($in);
		$y = Binary::signInt(VarInt::readUnsignedInt($in));
		$z = VarInt::readSignedInt($in);
		return new BlockPosition($x, $y, $z);
	}

	public static function putUnsignedYBlockPosition(ByteBufferWriter $out, BlockPosition $blockPosition) : void    {
		VarInt::writeSignedInt($out, $blockPosition->getX());
		VarInt::writeUnsignedInt($out, Binary::unsignInt($blockPosition->getY()));
		VarInt::writeSignedInt($out, $blockPosition->getZ());
	}

}
