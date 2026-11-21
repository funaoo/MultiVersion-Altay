<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use lol\network\proto\v84\inventory\v84ContainerId;
use pocketmine\block\inventory\CraftingTableInventory;
use pocketmine\inventory\Inventory;

final class v84LocalWindowClose
{

	private function __construct()
	{
	}

	public static function closes(int $clientId, ?Inventory $current) : bool
	{
		if ($current === null || v84WindowIds::fromClient($clientId) !== null) {
			return false;
		}

		return !v84ContainerId::isScreen($clientId) || $current instanceof CraftingTableInventory;
	}
}
