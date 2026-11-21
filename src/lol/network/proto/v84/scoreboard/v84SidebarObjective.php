<?php

declare(strict_types=1);

namespace lol\network\proto\v84\scoreboard;

final class v84SidebarObjective
{

	public function __construct(
		public readonly string $title,
		public readonly int $order
	)
	{
	}
}
