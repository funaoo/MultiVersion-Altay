<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

final class v84ItemVariants
{

	public function __construct(
		private readonly array $variants,
		private readonly int $fallback
	)
	{
	}

	public function resolve(int $meta) : int
	{
		return $this->variants[$meta] ?? $this->fallback;
	}
}
