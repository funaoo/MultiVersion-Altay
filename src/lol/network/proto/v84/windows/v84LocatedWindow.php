<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use pocketmine\inventory\Inventory;

final class v84LocatedWindow
{

	public function __construct(
		public readonly int       $clientId,
		public readonly Inventory $inventory,
		public readonly int       $slot
	)
	{
	}
}
