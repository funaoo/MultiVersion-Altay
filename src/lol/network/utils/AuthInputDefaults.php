<?php

declare(strict_types=1);

namespace lol\network\utils;

use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use function cos;
use function deg2rad;
use function sin;

final class AuthInputDefaults
{

	private function __construct()
	{
	}

	public static function apply(PlayerAuthInputPacket $packet) : void
	{
		$pitch = $packet->getPitch();
		$yaw = $packet->getYaw();
		$pitchRad = deg2rad($pitch);
		$yawRad = deg2rad($yaw);

		$defaults = [
			"interactionMode" => 0,
			"interactRotation" => new Vector2($pitch, $yaw),
			"analogMoveVecX" => $packet->getMoveVecX(),
			"analogMoveVecZ" => $packet->getMoveVecZ(),
			"cameraOrientation" => new Vector3(-sin($yawRad) * cos($pitchRad), -sin($pitchRad), cos($yawRad) * cos($pitchRad)),
			"rawMove" => new Vector2($packet->getMoveVecX(), $packet->getMoveVecZ()),
		];

		foreach ($defaults as $property => $value) {
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $packet, $property, $value);
		}
	}
}
