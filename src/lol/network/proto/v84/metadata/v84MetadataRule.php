<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

final readonly class v84MetadataRule
{

	public const FULL_MASK = -1;

	public function __construct(
		public int $key,
		public int $type,
		public int $mask = self::FULL_MASK
	)
	{
	}
}
