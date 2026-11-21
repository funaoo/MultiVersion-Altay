<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use lol\network\utils\ReflectionUtils;
use pocketmine\item\ConsumableItem;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;
use Throwable;

final class v84HeldItemConsumption implements v84ItemConsumption
{

	public function __construct(
		private readonly Player           $player,
		private readonly InventoryManager $inventoryManager
	)
	{
	}

	public function finish() : void
	{
		if (!$this->player->isAlive() || !$this->player->isConnected() || !$this->player->isUsingItem()) {
			return;
		}

		$held = $this->player->getInventory()->getItemInHand();
		if (!$held instanceof ConsumableItem) {
			return;
		}

		$minimum = $held->getMinUseDuration();
		if ($this->player->getItemUseDuration() < $minimum && !$this->reach($minimum)) {
			return;
		}

		if ($this->player->consumeHeldItem()) {
			v84EatingClock::end($this->player);
			return;
		}

		$this->player->setUsingItem(false);
		$this->inventoryManager->syncContents($this->player->getInventory());
	}

	private function reach(int $minimum) : bool
	{
		$remaining = v84EatingClock::remainingTicks($this->player, $minimum);
		if ($remaining === null) {
			return false;
		}

		if ($remaining > 0) {
			v84EatingClock::defer($this->player, $remaining, $this->finish(...));
			return false;
		}

		return $this->advance($minimum);
	}

	private function advance(int $ticks) : bool
	{
		try {
			ReflectionUtils::setProperty(Player::class, $this->player, 'startAction', $this->player->getServer()->getTick() - $ticks);
		} catch (Throwable) {
			return false;
		}

		return true;
	}
}
