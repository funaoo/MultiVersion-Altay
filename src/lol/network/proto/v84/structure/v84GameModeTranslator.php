<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use pocketmine\network\mcpe\protocol\types\GameMode as ProtocolGameMode;

final class v84GameModeTranslator
{

	public const SURVIVAL = 0;

	public const CREATIVE = 1;

	private function __construct()
	{
	}

	public static function fromProtocol(int $gameMode) : int
	{
		return match ($gameMode) {
			ProtocolGameMode::CREATIVE, ProtocolGameMode::CREATIVE_VIEWER => self::CREATIVE,
			default => self::SURVIVAL
		};
	}
}
