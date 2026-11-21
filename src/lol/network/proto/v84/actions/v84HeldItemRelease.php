<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

use lol\network\proto\v84\interaction\v84EatingClock;
use lol\network\proto\v84\interaction\v84ItemConsumption;
use pocketmine\item\ConsumableItem;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;

final class v84HeldItemRelease implements v84ItemRelease
{

	public function __construct(
		private readonly Player             $player,
		private readonly InventoryManager   $inventoryManager,
		private readonly v84ItemConsumption $consumption
	)
	{
	}

	public function release() : void
	{
		if (!$this->player->isUsingItem()) {
			return;
		}

		$held = $this->player->getInventory()->getItemInHand();
		if ($held instanceof ConsumableItem) {
			$this->releaseConsumable($held);
			return;
		}

		if (!$this->player->releaseHeldItem()) {
			$this->inventoryManager->syncContents($this->player->getInventory());
		}
	}

	public function cancel() : void
	{
		if ($this->player->isUsingItem()) {
			$this->player->setUsingItem(false);
		}
	}

	private function releaseConsumable(ConsumableItem $held) : void
	{
		if ($this->player->getItemUseDuration() < $held->getMinUseDuration() && !v84EatingClock::hasElapsed($this->player, $held->getMinUseDuration())) {
			$this->player->setUsingItem(false);
			return;
		}

		$this->consumption->finish();
	}
}
