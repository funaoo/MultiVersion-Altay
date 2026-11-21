<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

final class v84PlayerAction
{

	public const START_BREAK = 0;

	public const ABORT_BREAK = 1;

	public const STOP_BREAK = 2;

	public const RELEASE_ITEM = 5;

	public const RESPAWN = 7;

	public const RESPAWN_OVERWORLD = 13;

	public const RESPAWN_NETHER = 14;

	public const STOP_SLEEPING = 6;

	public const JUMP = 8;

	public const START_SPRINT = 9;

	public const STOP_SPRINT = 10;

	public const START_SNEAK = 11;

	public const STOP_SNEAK = 12;

	public static function keepsItemUse(int $action) : bool
	{
		return $action >= self::JUMP && $action <= self::STOP_SNEAK;
	}

	public static function isRespawn(int $action) : bool
	{
		return $action === self::RESPAWN || $action === self::RESPAWN_OVERWORLD || $action === self::RESPAWN_NETHER;
	}

	private function __construct()
	{
	}
}
