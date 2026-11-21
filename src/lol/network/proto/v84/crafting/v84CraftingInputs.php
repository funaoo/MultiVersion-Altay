<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\items\v84ItemSlotData;

final class v84CraftingInputs
{

	private const UNIT = 1;

	public function normalize(array $input) : array
	{
		$units = [];
		foreach ($input as $slot) {
			if ($slot->isAir() || $slot->count <= 0) {
				continue;
			}

			$units[] = new v84ItemSlotData($slot->id, self::UNIT, $slot->damage, $slot->compoundTag);
		}

		return $units;
	}
}
