<?php

declare(strict_types=1);

namespace lol\network\proto\v84\recipes;

use lol\network\proto\v84\items\v84ItemSlotData;

final class v84ShapedRecipe
{

	public function __construct(
		public readonly string $id,
		public readonly int $width,
		public readonly int $height,
		private readonly array $ingredients,
		public readonly v84ItemSlotData $result,
		public readonly bool $needsTable = false
	)
	{
	}

	public function ingredient(int $x, int $y) : v84ItemSlotData
	{
		if ($x < 0 || $y < 0 || $x >= $this->width || $y >= $this->height) {
			return v84ItemSlotData::air();
		}

		return $this->ingredients[$y * $this->width + $x];
	}
}
