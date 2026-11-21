<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\inventory\v84FloatingInventory;
use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

final class v84CraftingExecutor
{

	public function __construct(
		private readonly Player                $player,
		private readonly v84CraftingItems      $items,
		private readonly int                   $neutralId,
		private readonly v84CraftingResidues   $residues,
		private readonly v84FloatingInventory   $floating = new v84FloatingInventory()
	)
	{
	}

	public function execute(v84CraftingPlan $plan) : bool
	{
		$residue = $this->residue($plan);
		if ($residue === null) {
			return false;
		}

		$floating = $this->floating->contents();
		foreach ($plan->takes as $slot => $count) {
			$item = $this->source($slot, $floating);
			if ($item->getCount() < $count || !$this->genuine($item)) {
				return false;
			}
		}

		foreach ($plan->takes as $slot => $count) {
			$this->take($slot, $count, $this->source($slot, $floating));
		}

		$this->deliver($plan->result, $residue);

		return true;
	}

	private function source(int $slot, array $floating) : Item
	{
		if ($slot >= 0) {
			return $this->player->getInventory()->getItem($slot);
		}

		return $floating[v84CraftingPlan::floatingIndex($slot)] ?? VanillaItems::AIR();
	}

	private function take(int $slot, int $count, Item $item) : void
	{
		if ($slot < 0) {
			$this->floating->remove((clone $item)->setCount($count));
			return;
		}

		$left = $item->getCount() - $count;
		$this->player->getInventory()->setItem($slot, $left > 0 ? $item->setCount($left) : VanillaItems::AIR());
	}

	private function residue(v84CraftingPlan $plan) : ?array
	{
		$items = [];
		foreach ($this->residues->of($plan->recipe) as $slot) {
			$item = $this->items->modern($slot);
			if ($item === null) {
				return null;
			}

			$items[] = $item;
		}

		return $items;
	}

	private function deliver(Item $result, array $residue) : void
	{
		$leftover = $this->player->getInventory()->addItem($result, ...$residue);
		if (!$this->player->hasFiniteResources()) {
			return;
		}

		foreach ($leftover as $item) {
			$this->player->dropItem($item);
		}
	}

	private function genuine(Item $item) : bool
	{
		$shown = $this->items->legacy($item);
		if ($shown->id !== $this->neutralId) {
			return true;
		}

		$real = $this->items->modern(new v84ItemSlotData($shown->id, 1, $shown->damage, ''));

		return $real !== null && $real->equals($item, true, false);
	}
}
