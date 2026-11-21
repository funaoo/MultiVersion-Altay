<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\items\v84LegacyItem;
use lol\network\proto\v84\recipes\v84ShapedRecipe;
use function array_merge;
use function count;

final class v84CraftingGridMatcher
{

	public function match(v84ShapedRecipe $recipe, array $units) : ?array
	{
		$ingredients = $this->ingredients($recipe);
		if (count($ingredients) !== count($units)) {
			return null;
		}

		$free = $units;
		$specs = [];
		foreach ($ingredients as $ingredient) {
			$index = $this->claim($ingredient, $free);
			if ($index === null) {
				return null;
			}

			$specs[] = $this->concrete($ingredient, $free[$index]);
			unset($free[$index]);
		}

		return $specs;
	}

	public function required(v84ShapedRecipe $recipe) : array
	{
		$specs = [];
		foreach ($this->ingredients($recipe) as $ingredient) {
			$specs[] = new v84ItemSlotData($ingredient->id, 1, $ingredient->damage, $ingredient->compoundTag);
		}

		return $specs;
	}

	private function ingredients(v84ShapedRecipe $recipe) : array
	{
		$specific = [];
		$wildcard = [];
		for ($y = 0; $y < $recipe->height; ++$y) {
			for ($x = 0; $x < $recipe->width; ++$x) {
				$ingredient = $recipe->ingredient($x, $y);
				if ($ingredient->isAir()) {
					continue;
				}

				if ($ingredient->damage === v84LegacyItem::ANY_DAMAGE) {
					$wildcard[] = $ingredient;
					continue;
				}

				$specific[] = $ingredient;
			}
		}

		return array_merge($specific, $wildcard);
	}

	private function claim(v84ItemSlotData $ingredient, array $free) : ?int
	{
		foreach ([false, true] as $wildcardUnit) {
			foreach ($free as $index => $unit) {
				if (($unit->damage === v84LegacyItem::ANY_DAMAGE) === $wildcardUnit && $this->accepts($ingredient, $unit)) {
					return $index;
				}
			}
		}

		return null;
	}

	private function accepts(v84ItemSlotData $ingredient, v84ItemSlotData $unit) : bool
	{
		if ($ingredient->id !== $unit->id) {
			return false;
		}

		return $ingredient->damage === v84LegacyItem::ANY_DAMAGE
			|| $unit->damage === v84LegacyItem::ANY_DAMAGE
			|| $ingredient->damage === $unit->damage;
	}

	private function concrete(v84ItemSlotData $ingredient, v84ItemSlotData $unit) : v84ItemSlotData
	{
		$damage = $unit->damage === v84LegacyItem::ANY_DAMAGE ? $ingredient->damage : $unit->damage;

		return new v84ItemSlotData($ingredient->id, $unit->count, $damage, $unit->compoundTag);
	}
}
