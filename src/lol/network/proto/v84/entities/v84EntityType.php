<?php

declare(strict_types=1);

namespace lol\network\proto\v84\entities;

final readonly class v84EntityType
{

	private const HEIGHT_DIVISOR = 2.0;

	private const EYE_OFFSET = 0.1;

	public function __construct(
		public int   $id,
		public float $eyeHeight
	)
	{
	}

	public static function fromHeight(int $id, float $height) : self
	{
		return new self($id, $height / self::HEIGHT_DIVISOR + self::EYE_OFFSET);
	}
}
