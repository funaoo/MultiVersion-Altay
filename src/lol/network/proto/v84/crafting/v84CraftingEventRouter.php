<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use GlobalLogger;
use lol\network\proto\v84\inventory\v84FloatingInventory;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\player\Player;
use function bin2hex;

final class v84CraftingEventRouter implements v84CraftingEvents
{

	public function __construct(
		private readonly Player                 $player,
		private readonly InventoryManager       $inventoryManager,
		private readonly v84RecipeIndex         $recipes,
		private readonly v84CraftingTableGate   $table,
		private readonly v84CraftingPlanner     $planner,
		private readonly v84CraftingExecutor    $executor,
		private readonly v84FloatingInventory   $floating = new v84FloatingInventory()
	)
	{
	}

	public function receive(v84CraftingRequest $request) : ?v84CraftingPlan
	{
		if (!$this->player->isAlive() || !$this->player->isConnected()) {
			return null;
		}

		$recipe = $this->recipes->find($request->recipeId);
		if ($recipe === null) {
			$this->refuse('Unknown crafting recipe ' . bin2hex($request->recipeId));

			return null;
		}

		if (!$this->table->permits($recipe)) {
			$this->reject($request, v84CraftingRejection::NeedsTable);

			return null;
		}

		$outcome = $this->planner->plan($request, $recipe, $this->player->getInventory()->getContents(), $this->floating->contents());
		if ($outcome instanceof v84CraftingRejection) {
			$this->reject($request, $outcome);

			return null;
		}

		if (!$this->executor->execute($outcome)) {
			$this->refuse('Could not take the ingredients of crafting recipe ' . bin2hex($request->recipeId));

			return null;
		}

		return $outcome;
	}

	private function reject(v84CraftingRequest $request, v84CraftingRejection $reason) : void
	{
		$this->refuse('Rejected crafting recipe ' . bin2hex($request->recipeId) . ' (' . $reason->value . ')');
	}

	private function refuse(string $reason) : void
	{
		GlobalLogger::get()->debug($reason . ' received from ' . $this->player->getName());
		$this->inventoryManager->syncContents($this->player->getInventory());
	}
}
