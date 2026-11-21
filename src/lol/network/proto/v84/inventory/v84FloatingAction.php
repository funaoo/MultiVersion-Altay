<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\player\Player;

final class v84FloatingAction extends InventoryAction
{

	public function validate(Player $source) : void
	{
	}

	public function execute(Player $source) : void
	{
	}
}
