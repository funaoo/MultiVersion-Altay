<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

final class v84LegacyItem
{

	public const AIR = 0;

	public const DAMAGE_BITS = 16;

	public const DAMAGE_MASK = 0xffff;

	public const ANY_DAMAGE = 0xffff;

	public const ID_MAX = 511;

	private function __construct()
	{
	}

	public static function pack(int $id, int $damage) : int
	{
		return ($id << self::DAMAGE_BITS) | ($damage & self::DAMAGE_MASK);
	}

	public static function id(int $packed) : int
	{
		return $packed >> self::DAMAGE_BITS;
	}

	public static function damage(int $packed) : int
	{
		return $packed & self::DAMAGE_MASK;
	}
}
