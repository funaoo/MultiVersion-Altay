<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\utils\AuthInputDefaults;
use lol\network\utils\ReflectionUtils;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;

class v84MovePlayerPacket extends PlayerAuthInputPacket
{
	public const NETWORK_ID = v84ProtocolInfo::MOVE_PLAYER_PACKET;

	private const TOUCHSCREEN_INPUT_MODE = 2;

	private const NORMAL_PLAY_MODE = 0;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		BE::readSignedLong($in);
		$x = BE::readFloat($in);
		$y = BE::readFloat($in);
		$z = BE::readFloat($in);
		$yaw = BE::readFloat($in);
		$headYaw = BE::readFloat($in);
		$pitch = BE::readFloat($in);
		Byte::readUnsigned($in);
		Byte::readUnsigned($in);

		$values = [
			"pitch" => $pitch,
			"yaw" => $yaw,
			"position" => new Vector3($x, $y, $z),
			"moveVecX" => 0.0,
			"moveVecZ" => 0.0,
			"headYaw" => $headYaw,
			"inputFlags" => new BitSet(PlayerAuthInputFlags::NUMBER_OF_FLAGS),
			"inputMode" => self::TOUCHSCREEN_INPUT_MODE,
			"playMode" => self::NORMAL_PLAY_MODE,
			"tick" => 0,
			"delta" => new Vector3(0.0, 0.0, 0.0),
		];

		foreach ($values as $name => $value) {
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, $name, $value);
		}

		AuthInputDefaults::apply($this);
	}
}
