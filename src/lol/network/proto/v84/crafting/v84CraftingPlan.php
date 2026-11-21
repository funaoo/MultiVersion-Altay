<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\recipes\v84ShapedRecipe;
use pocketmine\item\Item;

final class v84CraftingPlan
{

	public static function floatingSlot(int $index) : int
	{
		return -1 - $index;
	}

	public static function floatingIndex(int $slot) : int
	{
		return -1 - $slot;
	}

	public function __construct(
		public readonly v84ShapedRecipe $recipe,
		public readonly array           $takes,
		public readonly Item            $result
	)
	{
	}
}
