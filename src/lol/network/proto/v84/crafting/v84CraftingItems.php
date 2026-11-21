<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\Item;

interface v84CraftingItems
{

	public function legacy(Item $item) : v84ItemSlotData;

	public function modern(v84ItemSlotData $slot) : ?Item;
}
