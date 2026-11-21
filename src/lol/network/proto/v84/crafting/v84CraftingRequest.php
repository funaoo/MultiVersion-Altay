<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

final class v84CraftingRequest
{

	public function __construct(
		public readonly int    $windowId,
		public readonly int    $type,
		public readonly string $recipeId,
		public readonly array  $input,
		public readonly array  $output
	)
	{
	}
}
