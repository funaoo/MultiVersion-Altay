<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\Item;

interface v84ClientItems
{

	public function resolve(v84ItemSlotData $slot, array $candidates) : ?Item;
}
