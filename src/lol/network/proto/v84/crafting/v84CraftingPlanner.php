<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\recipes\v84ShapedRecipe;
use function array_values;

final class v84CraftingPlanner
{

	public function __construct(
		private readonly v84CraftingInputs      $inputs,
		private readonly v84CraftingGridMatcher $grid,
		private readonly v84CraftingStock       $stock,
		private readonly v84CraftingItems       $items
	)
	{
	}

	public function plan(v84CraftingRequest $request, v84ShapedRecipe $recipe, array $contents, array $floating = []) : v84CraftingPlan|v84CraftingRejection
	{
		$wanted = $request->output[0] ?? null;
		if ($wanted === null || $wanted->isAir()) {
			return v84CraftingRejection::EmptyOutput;
		}

		if (!$recipe->result->sameKind($wanted)) {
			return v84CraftingRejection::ResultMismatch;
		}

		$units = $this->inputs->normalize($request->input);
		$specs = $units === [] ? $this->grid->required($recipe) : $this->grid->match($recipe, $units);
		if ($specs === null) {
			return v84CraftingRejection::IngredientsMismatch;
		}

		$takes = $this->stock->reserve($specs, $this->legacyFloating($floating) + $this->legacyContents($contents));
		if ($takes === null) {
			return v84CraftingRejection::MissingIngredients;
		}

		$result = $this->items->modern($recipe->result);
		if ($result === null) {
			return v84CraftingRejection::UnresolvedResult;
		}

		return new v84CraftingPlan($recipe, $takes, $result);
	}

	private function legacyFloating(array $floating) : array
	{
		$held = [];
		foreach (array_values($floating) as $index => $item) {
			if (!$item->isNull()) {
				$held[v84CraftingPlan::floatingSlot($index)] = $this->items->legacy($item);
			}
		}

		return $held;
	}

	private function legacyContents(array $contents) : array
	{
		$held = [];
		foreach ($contents as $slot => $item) {
			if ($item->isNull()) {
				continue;
			}

			$held[$slot] = $this->items->legacy($item);
		}

		return $held;
	}
}
