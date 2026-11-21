<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use Closure;
use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\windows\v84ClientWindows;
use lol\network\proto\v84\windows\v84LocatedWindow;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;
use function abs;
use function array_values;
use function spl_object_id;

final class v84InventoryTransactions implements v84InventoryChanges, v84FloatingRelease
{

	private const RETRY_TICKS = 5;

	private array $changes = [];

	private array $drops = [];

	private int $generation = 0;

	private const APPLIED = 0;

	private const BLOCKED = 1;

	private const REFUSED = 2;

	private array $blocked = [];

	private array $blockedDrops = [];

	private int $blockedGeneration = 0;

	public function __construct(
		private readonly Player           $player,
		private readonly InventoryManager $inventoryManager,
		private readonly v84ClientItems   $items,
		private readonly Closure          $defer,
		private readonly v84FloatingInventory $floating = new v84FloatingInventory()
	)
	{
	}

	public function changeSlot(int $windowId, int $slot, v84ItemSlotData $item) : void
	{
		if (!$this->isActive()) {
			return;
		}

		$window = $this->locate($windowId, $slot);
		if ($window === null) {
			return;
		}

		if ($this->player->hasFiniteResources()) {
			$this->carrySlot($window, $item);
			return;
		}

		$this->changes[spl_object_id($window->inventory) . ':' . $window->slot] = new v84PendingSlotChange($window->inventory, $window->slot, $item);
		$this->settle();
	}

	public function dropItem(v84ItemSlotData $item) : void
	{
		if ($item->isAir() || !$this->isActive()) {
			return;
		}

		if ($this->player->hasFiniteResources()) {
			$this->carryDrop($item);
			return;
		}

		$this->drops[] = $item;
		$this->settle();
	}

	public function release() : void
	{
		$this->blocked = [];
		$this->blockedDrops = [];
		++$this->blockedGeneration;

		$items = $this->floating->drain();
		if ($items === [] || !$this->player->isConnected()) {
			return;
		}

		$inventory = $this->player->getInventory();
		foreach ($items as $item) {
			foreach ($inventory->addItem($item) as $leftover) {
				$this->player->dropItem($leftover);
			}
		}

		$this->inventoryManager->syncContents($inventory);
	}

	private function carrySlot(v84LocatedWindow $window, v84ItemSlotData $item) : void
	{
		$key = spl_object_id($window->inventory) . ':' . $window->slot;
		unset($this->blocked[$key]);

		$result = $this->applySlot($window->inventory, $window->slot, $item);
		if ($result === self::BLOCKED) {
			$this->blocked[$key] = new v84PendingSlotChange($window->inventory, $window->slot, $item);
			$this->waitBlocked();
			return;
		}

		if ($result === self::REFUSED) {
			$this->resync([$window->inventory]);
			return;
		}

		$this->retryBlocked();
	}

	private function carryDrop(v84ItemSlotData $item) : void
	{
		$result = $this->applyDrop($item);
		if ($result === self::BLOCKED) {
			$this->blockedDrops[] = $item;
			$this->waitBlocked();
			return;
		}

		if ($result === self::REFUSED) {
			$this->resync([$this->player->getInventory()]);
			return;
		}

		$this->retryBlocked();
	}

	private function applySlot(Inventory $inventory, int $slot, v84ItemSlotData $data) : int
	{
		$source = $inventory->getItem($slot);
		$target = $this->items->resolve($data, [$source, ...$this->floating->contents()]);
		if ($target === null) {
			return self::REFUSED;
		}

		if ($target->equalsExact($source)) {
			return self::APPLIED;
		}

		[$out, $in] = $this->difference($source, $target);
		if ($in !== null && !$this->floating->contains($in)) {
			return self::BLOCKED;
		}

		$actions = [new SlotChangeAction($inventory, $slot, $source, $target)];
		if ($out !== null) {
			$actions[] = new v84FloatingAction(VanillaItems::AIR(), $out);
		}

		if ($in !== null) {
			$actions[] = new v84FloatingAction($in, VanillaItems::AIR());
		}

		try {
			(new InventoryTransaction($this->player, $actions))->execute();
		} catch (TransactionCancelledException|TransactionValidationException) {
			return self::REFUSED;
		}

		if ($out !== null) {
			$this->floating->add($out);
		}

		if ($in !== null) {
			$this->floating->remove($in);
		}

		return self::APPLIED;
	}

	private function applyDrop(v84ItemSlotData $data) : int
	{
		$item = $this->items->resolve($data, $this->floating->contents());
		if ($item === null || $item->isNull()) {
			return self::REFUSED;
		}

		if (!$this->floating->contains($item)) {
			return self::BLOCKED;
		}

		try {
			(new InventoryTransaction($this->player, [new DropItemAction($item), new v84FloatingAction($item, VanillaItems::AIR())]))->execute();
		} catch (TransactionCancelledException|TransactionValidationException) {
			return self::REFUSED;
		}

		$this->floating->remove($item);

		return self::APPLIED;
	}

	private function difference(Item $source, Item $target) : array
	{
		if ($source->canStackWith($target)) {
			$amount = $target->getCount() - $source->getCount();
			$moved = clone $source;
			$moved->setCount(abs($amount));

			return $amount < 0 ? [$moved, null] : [null, $moved];
		}

		if ($source->isNull()) {
			return [null, clone $target];
		}

		if ($target->isNull()) {
			return [clone $source, null];
		}

		return [clone $source, clone $target];
	}

	private function retryBlocked() : void
	{
		do {
			$progress = false;
			foreach ($this->blocked as $key => $change) {
				$result = $this->applySlot($change->inventory, $change->slot, $change->item);
				if ($result === self::BLOCKED) {
					continue;
				}

				unset($this->blocked[$key]);
				$progress = true;
				if ($result === self::REFUSED) {
					$this->resync([$change->inventory]);
				}
			}

			foreach ($this->blockedDrops as $index => $drop) {
				$result = $this->applyDrop($drop);
				if ($result === self::BLOCKED) {
					continue;
				}

				unset($this->blockedDrops[$index]);
				$progress = true;
				if ($result === self::REFUSED) {
					$this->resync([$this->player->getInventory()]);
				}
			}
		} while ($progress && ($this->blocked !== [] || $this->blockedDrops !== []));
	}

	private function waitBlocked() : void
	{
		$generation = ++$this->blockedGeneration;
		($this->defer)(function () use ($generation) : void {
			if ($generation === $this->blockedGeneration) {
				$this->expireBlocked();
			}
		}, self::RETRY_TICKS);
	}

	private function expireBlocked() : void
	{
		$inventories = [];
		foreach ($this->blocked as $change) {
			$inventories[spl_object_id($change->inventory)] = $change->inventory;
		}

		if ($this->blockedDrops !== []) {
			$inventory = $this->player->getInventory();
			$inventories[spl_object_id($inventory)] = $inventory;
		}

		$this->blocked = [];
		$this->blockedDrops = [];
		$this->resync(array_values($inventories));
	}

	private function resync(array $inventories) : void
	{
		if (!$this->player->isConnected()) {
			return;
		}

		foreach ($inventories as $inventory) {
			$this->inventoryManager->syncContents($inventory);
		}
	}

	private function locate(int $windowId, int $slot) : ?v84LocatedWindow
	{
		$inventory = v84InventoryWindows::find($this->player, $windowId);
		if ($inventory === null) {
			return v84ClientWindows::locate($this->inventoryManager, $windowId, $slot);
		}

		return $inventory->slotExists($slot) ? new v84LocatedWindow($windowId, $inventory, $slot) : null;
	}

	private function isActive() : bool
	{
		return $this->player->isAlive() && $this->player->isConnected();
	}

	private function settle(bool $final = false) : void
	{
		$actions = $this->buildActions();
		if ($actions === null) {
			$this->reject();
			return;
		}

		if ($actions === []) {
			$this->clear();
			return;
		}

		if ($this->hasInfiniteResources()) {
			$actions = [...$actions, ...v84CreativeBalance::extraActions($actions, $final)];
		}

		try {
			(new InventoryTransaction($this->player, $actions))->execute();
		} catch (TransactionCancelledException) {
			$this->reject();
			return;
		} catch (TransactionValidationException) {
			if ($final) {
				$this->reject();
				return;
			}

			$this->wait();
			return;
		}

		$this->clear();
	}

	private function buildActions() : ?array
	{
		$sources = [];
		foreach ($this->changes as $change) {
			$sources[] = $change->inventory->getItem($change->slot);
		}

		$actions = [];
		foreach ($this->changes as $change) {
			$source = $change->inventory->getItem($change->slot);
			$target = $this->items->resolve($change->item, [$source, ...$sources]);
			if ($target === null) {
				return null;
			}

			if (!$target->equalsExact($source)) {
				$actions[] = new SlotChangeAction($change->inventory, $change->slot, $source, $target);
			}
		}

		foreach ($this->drops as $drop) {
			$dropped = $this->items->resolve($drop, $sources);
			if ($dropped === null) {
				return null;
			}

			$actions[] = new DropItemAction($dropped);
		}

		return $actions;
	}

	private function wait() : void
	{
		$generation = ++$this->generation;
		($this->defer)(function () use ($generation) : void {
			if ($generation === $this->generation) {
				$this->expire();
			}
		}, self::RETRY_TICKS);
	}

	private function expire() : void
	{
		if (!$this->isActive() || !$this->hasInfiniteResources()) {
			$this->reject();
			return;
		}

		$this->settle(true);
	}

	private function hasInfiniteResources() : bool
	{
		return !$this->player->hasFiniteResources();
	}

	private function reject() : void
	{
		$inventories = [];
		foreach ($this->changes as $change) {
			$inventories[spl_object_id($change->inventory)] = $change->inventory;
		}

		if ($this->drops !== []) {
			$inventory = $this->player->getInventory();
			$inventories[spl_object_id($inventory)] = $inventory;
		}

		$this->clear();

		if (!$this->player->isConnected()) {
			return;
		}

		foreach ($inventories as $inventory) {
			$this->inventoryManager->syncContents($inventory);
		}
	}

	private function clear() : void
	{
		$this->changes = [];
		$this->drops = [];
		++$this->generation;
	}
}
