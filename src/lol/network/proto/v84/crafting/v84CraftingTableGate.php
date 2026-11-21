<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\recipes\v84ShapedRecipe;
use pocketmine\block\inventory\CraftingTableInventory;
use pocketmine\player\Player;

final class v84CraftingTableGate
{

	public function __construct(private readonly Player $player)
	{
	}

	public function permits(v84ShapedRecipe $recipe) : bool
	{
		return !$recipe->needsTable || $this->player->getCurrentWindow() instanceof CraftingTableInventory;
	}
}
