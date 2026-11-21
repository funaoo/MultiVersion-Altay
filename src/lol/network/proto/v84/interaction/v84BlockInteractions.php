<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\ConsumableItem;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;
use function in_array;
use function is_finite;

final class v84BlockInteractions implements v84BlockUse
{

	public function __construct(
		private readonly Player           $player,
		private readonly InventoryManager $inventoryManager,
		private readonly v84HeldItems     $heldItems,
		private readonly v84BlockResync   $resync,
		private readonly v84ItemUse       $airUse
	)
	{
	}

	public function useItemOn(Vector3 $block, int $face, Vector3 $clickOffset, v84ItemSlotData $item) : void
	{
		if (!in_array($face, Facing::ALL, true) || !$this->player->isAlive() || !$this->player->isConnected() || $this->player->getCurrentWindow() !== null) {
			return;
		}

		if ($this->isEating()) {
			return;
		}

		if (!$this->isAccepted($clickOffset, $item)) {
			$this->inventoryManager->syncContents($this->player->getInventory());
			$this->resync->around($block, $face);
			return;
		}

		if ($this->player->interactBlock($block, $face, $clickOffset)) {
			return;
		}

		$this->resync->around($block, $face);

		if ($this->isConsumable()) {
			$this->airUse->useInAir($item);
			return;
		}

		$this->inventoryManager->syncContents($this->player->getInventory());
	}

	private function isAccepted(Vector3 $clickOffset, v84ItemSlotData $item) : bool
	{
		if (!is_finite($clickOffset->x) || !is_finite($clickOffset->y) || !is_finite($clickOffset->z)) {
			return false;
		}

		return $this->player->isCreative() || $this->heldItems->matches($this->player->getInventory()->getItemInHand(), $item);
	}

	private function isConsumable() : bool
	{
		return $this->player->getInventory()->getItemInHand() instanceof ConsumableItem;
	}

	private function isEating() : bool
	{
		return $this->player->isUsingItem() && $this->isConsumable();
	}
}
