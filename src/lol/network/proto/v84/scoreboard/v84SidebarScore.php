<?php

declare(strict_types=1);

namespace lol\network\proto\v84\scoreboard;

final class v84SidebarScore
{

	public function __construct(
		public readonly string $objective,
		public readonly string $label,
		public readonly int $value
	)
	{
	}
}
