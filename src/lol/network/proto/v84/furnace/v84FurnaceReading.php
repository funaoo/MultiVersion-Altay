<?php

declare(strict_types=1);

namespace lol\network\proto\v84\furnace;

final class v84FurnaceReading
{

	public function __construct(
		public readonly int $property,
		public readonly int $value
	) {
	}
}
