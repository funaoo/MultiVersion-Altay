<?php

declare(strict_types=1);

namespace lol\network\proto\v84\sound;

final class v84SoundId
{

	public const CLICK = 1000;

	public const CLICK_FAIL = 1001;

	public const SHOOT = 1002;

	public const DOOR = 1003;

	public const FIZZ = 1004;

	public const TNT = 1005;

	public const GHAST = 1007;

	public const GHAST_SHOOT = 1008;

	public const BLAZE_SHOOT = 1009;

	public const DOOR_BUMP = 1010;

	public const DOOR_CRASH = 1012;

	public const ZOMBIE_INFECT = 1016;

	public const ZOMBIE_HEAL = 1017;

	public const ENDERMAN_TELEPORT = 1018;

	public const ANVIL_BREAK = 1020;

	public const ANVIL_USE = 1021;

	public const ANVIL_FALL = 1022;

	public const DROP_ITEM = 1030;

	public const ITEMFRAME_ADD_ITEM = 1040;

	public const ITEMFRAME_PLACE = 1041;

	public const ITEMFRAME_DROP_ITEM = 1043;

	public const ITEMFRAME_ROTATE_ITEM = 1044;

	public const EXP_PICKUP = 1051;

	public const BLOCK_PLACE = 1052;

	public const BUTTON_CLICK = 3500;

	public const EXPLODE = 3501;

	public const SPELL = 3504;

	public const SPLASH = 3506;

	public const GRAY_SPLASH = 3507;

	private function __construct()
	{
	}
}
