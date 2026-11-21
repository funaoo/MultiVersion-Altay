<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\Item;

interface v84HeldItems
{

	public function matches(Item $held, v84ItemSlotData $slot) : bool;
}
