<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\recipes\v84RecipeTable;
use lol\network\proto\v84\recipes\v84ShapedRecipe;

final class v84RecipeIndex
{

	private function __construct(private readonly array $byId)
	{
	}

	public static function fromTable(v84RecipeTable $table) : self
	{
		$byId = [];
		foreach ($table->shaped() as $recipe) {
			$byId[$recipe->id] = $recipe;
		}

		return new self($byId);
	}

	public function find(string $recipeId) : ?v84ShapedRecipe
	{
		return $this->byId[$recipeId] ?? null;
	}
}
