<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\types;

final class v84AttributeEntry
{

	public function __construct(
		public readonly string $name,
		public readonly float  $min,
		public readonly float  $max,
		public readonly float  $current
	)
	{
	}
}
