<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use lol\network\proto\v84\items\v84ItemSlotData;

interface v84InventoryChanges
{

	public function changeSlot(int $windowId, int $slot, v84ItemSlotData $item) : void;

	public function dropItem(v84ItemSlotData $item) : void;
}
