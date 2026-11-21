<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use pocketmine\network\mcpe\protocol\MovePlayerPacket;

final class v84MovementMode
{

	public const NORMAL = 0;

	public const RESET = 1;

	public const ROTATION = 2;

	private function __construct()
	{
	}

	public static function fromLatest(int $mode) : int
	{
		return match ($mode) {
			MovePlayerPacket::MODE_NORMAL => self::NORMAL,
			MovePlayerPacket::MODE_PITCH => self::ROTATION,
			default => self::RESET
		};
	}
}
