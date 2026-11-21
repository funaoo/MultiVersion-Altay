<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\inventory\Inventory;

final class v84PendingSlotChange
{

	public function __construct(
		public readonly Inventory       $inventory,
		public readonly int             $slot,
		public readonly v84ItemSlotData $item
	)
	{
	}
}
