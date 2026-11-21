<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\ConsumableItem;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;

final class v84AirInteractions implements v84ItemUse
{

	public function __construct(
		private readonly Player           $player,
		private readonly InventoryManager $inventoryManager,
		private readonly v84HeldItems     $heldItems
	)
	{
	}

	public function useInAir(v84ItemSlotData $item) : void
	{
		if (!$this->player->isAlive() || !$this->player->isConnected() || $this->player->isSpectator() || $this->player->getCurrentWindow() !== null) {
			return;
		}

		if (!$this->isAccepted($item)) {
			$this->inventoryManager->syncContents($this->player->getInventory());
			return;
		}

		if ($this->isEatingAlready()) {
			return;
		}

		$this->player->clearAwaitingConsumableRelease();
		$this->player->useHeldItem();

		if ($this->isEatingAlready()) {
			v84EatingClock::begin($this->player);
		}
	}

	private function isAccepted(v84ItemSlotData $item) : bool
	{
		return $this->player->isCreative() || $this->heldItems->matches($this->player->getInventory()->getItemInHand(), $item);
	}

	private function isEatingAlready() : bool
	{
		return $this->player->isUsingItem() && $this->player->getInventory()->getItemInHand() instanceof ConsumableItem;
	}
}
