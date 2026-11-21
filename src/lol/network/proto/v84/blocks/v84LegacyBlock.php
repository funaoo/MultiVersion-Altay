<?php

declare(strict_types=1);

namespace lol\network\proto\v84\blocks;

final class v84LegacyBlock
{

	public const AIR = 0;

	public const META_BITS = 4;

	public const META_MASK = 0xf;

	public const ID_MAX = 255;

	private function __construct()
	{
	}

	public static function pack(int $id, int $meta) : int
	{
		return ($id << self::META_BITS) | ($meta & self::META_MASK);
	}

	public static function id(int $packed) : int
	{
		return $packed >> self::META_BITS;
	}

	public static function meta(int $packed) : int
	{
		return $packed & self::META_MASK;
	}
}
