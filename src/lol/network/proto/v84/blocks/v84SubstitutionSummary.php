<?php

declare(strict_types=1);

namespace lol\network\proto\v84\blocks;

final readonly class v84SubstitutionSummary
{

	public function __construct(
		public int $totalNames,
		public array $substitutedNames
	)
	{
	}
}
