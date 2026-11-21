<?php

declare(strict_types=1);

namespace lol\network\proto\v84\players;

final class v84LegacySkin
{

	public function __construct(
		public readonly string $id,
		public readonly string $data
	)
	{
	}
}
