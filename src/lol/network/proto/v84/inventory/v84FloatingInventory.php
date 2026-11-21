<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use pocketmine\item\Item;
use function array_values;
use function min;

final class v84FloatingInventory
{

	private array $items = [];

	public function contents() : array
	{
		$copies = [];
		foreach ($this->items as $item) {
			$copies[] = clone $item;
		}

		return $copies;
	}

	public function isEmpty() : bool
	{
		return $this->items === [];
	}

	public function contains(Item $wanted) : bool
	{
		$remaining = $wanted->getCount();
		foreach ($this->items as $item) {
			if (!$item->canStackWith($wanted)) {
				continue;
			}

			$remaining -= $item->getCount();
			if ($remaining <= 0) {
				return true;
			}
		}

		return false;
	}

	public function add(Item $item) : void
	{
		foreach ($this->items as $held) {
			if ($held->canStackWith($item)) {
				$held->setCount($held->getCount() + $item->getCount());
				return;
			}
		}

		$this->items[] = clone $item;
	}

	public function remove(Item $item) : void
	{
		$remaining = $item->getCount();
		foreach ($this->items as $index => $held) {
			if (!$held->canStackWith($item)) {
				continue;
			}

			$amount = min($remaining, $held->getCount());
			$held->setCount($held->getCount() - $amount);
			$remaining -= $amount;
			if ($held->getCount() <= 0) {
				unset($this->items[$index]);
			}

			if ($remaining <= 0) {
				return;
			}
		}
	}

	public function drain() : array
	{
		$items = array_values($this->items);
		$this->items = [];

		return $items;
	}
}
