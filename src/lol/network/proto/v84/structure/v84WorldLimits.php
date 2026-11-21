<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use function max;
use function min;

final class v84WorldLimits
{

	public const HEIGHT = 128;

	public const MAX_BLOCK_Y = self::HEIGHT - 1;

	public const DIMENSION_OVERWORLD = 0;

	public const DIMENSION_NETHER = 1;

	private function __construct()
	{
	}

	public static function isBlockY(int $y) : bool
	{
		return $y >= 0 && $y <= self::MAX_BLOCK_Y;
	}

	public static function clampBlockY(int $y) : int
	{
		return max(0, min(self::MAX_BLOCK_Y, $y));
	}

	public static function clampY(float $y) : float
	{
		return max(0.0, min((float) self::MAX_BLOCK_Y, $y));
	}

	public static function dimension(int $dimension) : int
	{
		return $dimension === self::DIMENSION_NETHER ? self::DIMENSION_NETHER : self::DIMENSION_OVERWORLD;
	}
}
