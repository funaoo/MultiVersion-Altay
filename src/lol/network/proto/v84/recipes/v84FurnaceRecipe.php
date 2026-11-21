<?php

declare(strict_types=1);

namespace lol\network\proto\v84\recipes;

use lol\network\proto\v84\items\v84ItemSlotData;

final class v84FurnaceRecipe
{

	public function __construct(
		public readonly v84ItemSlotData $input,
		public readonly v84ItemSlotData $result
	)
	{
	}
}
