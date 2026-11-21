<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

final class v84InventoryWindows
{

	private function __construct()
	{
	}

	public static function find(Player $player, int $windowId) : ?Inventory
	{
		return match ($windowId) {
			v84ContainerId::INVENTORY => $player->getInventory(),
			v84ContainerId::ARMOR => $player->getArmorInventory(),
			default => null
		};
	}
}
