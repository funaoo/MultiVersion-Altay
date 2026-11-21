<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

final class v84AngleCodec
{

	public const DEGREES_PER_UNIT = 360.0 / 256;

	private const UNIT_MASK = 0xff;

	private function __construct()
	{
	}

	public static function toUnit(float $degrees) : int
	{
		return ((int) ($degrees / self::DEGREES_PER_UNIT)) & self::UNIT_MASK;
	}

	public static function toDegrees(int $unit) : float
	{
		return $unit * self::DEGREES_PER_UNIT;
	}
}
