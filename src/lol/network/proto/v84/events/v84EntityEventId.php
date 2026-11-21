<?php

declare(strict_types=1);

namespace lol\network\proto\v84\events;

final class v84EntityEventId
{

	public const HURT = 2;

	public const DEATH = 3;

	public const TAME_FAIL = 6;

	public const TAME_SUCCESS = 7;

	public const SHAKE_WET = 8;

	public const USE_ITEM = 9;

	public const EAT_GRASS = 10;

	public const FISH_HOOK_BUBBLE = 11;

	public const FISH_HOOK_POSITION = 12;

	public const FISH_HOOK_HOOK = 13;

	public const FISH_HOOK_TEASE = 14;

	public const SQUID_INK_CLOUD = 15;

	public const AMBIENT_SOUND = 16;

	public const RESPAWN = 17;

	private function __construct()
	{
	}
}
