<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

use function substr;

final readonly class v84MetadataEntry
{

	private const BYTE_MASK = 0xff;

	private const SHORT_MASK = 0xffff;

	private const INT_MASK = 0xffffffff;

	private const INT_SIGN = 0x80000000;

	private const INT_RANGE = 0x100000000;

	private const MAX_STRING_LENGTH = 0xffff;

	private function __construct(
		public int                    $type,
		public int|float|string|array $value
	)
	{
	}

	public static function byte(int $value) : self
	{
		return new self(v84MetadataType::BYTE, $value & self::BYTE_MASK);
	}

	public static function short(int $value) : self
	{
		return new self(v84MetadataType::SHORT, $value & self::SHORT_MASK);
	}

	public static function int(int $value) : self
	{
		return new self(v84MetadataType::INT, self::signedInt($value));
	}

	public static function float(float $value) : self
	{
		return new self(v84MetadataType::FLOAT, $value);
	}

	public static function string(string $value) : self
	{
		return new self(v84MetadataType::STRING, substr($value, 0, self::MAX_STRING_LENGTH));
	}

	public static function slot(int $id, int $count, int $damage) : self
	{
		return new self(v84MetadataType::SLOT, [$id & self::SHORT_MASK, $count & self::BYTE_MASK, $damage & self::SHORT_MASK]);
	}

	public static function position(int $x, int $y, int $z) : self
	{
		return new self(v84MetadataType::POSITION, [self::signedInt($x), self::signedInt($y), self::signedInt($z)]);
	}

	public static function long(int $value) : self
	{
		return new self(v84MetadataType::LONG, $value);
	}

	private static function signedInt(int $value) : int
	{
		$unsigned = $value & self::INT_MASK;
		return $unsigned >= self::INT_SIGN ? $unsigned - self::INT_RANGE : $unsigned;
	}
}
