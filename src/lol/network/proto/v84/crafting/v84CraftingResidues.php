<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\recipes\v84ShapedRecipe;

final class v84CraftingResidues
{

	private const CAKE_ID = 354;

	private const BUCKET_ID = 325;

	private const BUCKET_DAMAGE = 0;

	private const CAKE_BUCKETS = 3;

	public function of(v84ShapedRecipe $recipe) : array
	{
		if ($recipe->result->id === self::CAKE_ID) {
			return [new v84ItemSlotData(self::BUCKET_ID, self::CAKE_BUCKETS, self::BUCKET_DAMAGE, '')];
		}

		return [];
	}
}
