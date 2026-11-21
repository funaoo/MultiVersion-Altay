<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets\types;

final class v84PlayerListEntry
{

	public function __construct(
		public readonly string $uuidBytes,
		public readonly int    $actorId = 0,
		public readonly string $name = '',
		public readonly string $skinId = '',
		public readonly string $skinData = ''
	)
	{
	}

	public static function removal(string $uuidBytes) : self
	{
		return new self($uuidBytes);
	}
}
