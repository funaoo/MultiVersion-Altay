<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\items\v84LegacyItem;

final class v84CraftingStock
{

	public function reserve(array $specs, array $held) : ?array
	{
		$takes = [];
		foreach ($specs as $spec) {
			$slot = $this->find($spec, $held, $takes);
			if ($slot === null) {
				return null;
			}

			$takes[$slot] = ($takes[$slot] ?? 0) + $spec->count;
		}

		return $takes;
	}

	private function find(v84ItemSlotData $spec, array $held, array $takes) : ?int
	{
		foreach ($held as $slot => $item) {
			if ($this->holds($item, $spec) && $item->count - ($takes[$slot] ?? 0) >= $spec->count) {
				return $slot;
			}
		}

		return null;
	}

	private function holds(v84ItemSlotData $item, v84ItemSlotData $spec) : bool
	{
		if ($item->id !== $spec->id || $item->compoundTag !== $spec->compoundTag) {
			return false;
		}

		return $spec->damage === v84LegacyItem::ANY_DAMAGE || $item->damage === $spec->damage;
	}
}
