<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

interface v84CraftingEvents
{

	public function receive(v84CraftingRequest $request) : ?v84CraftingPlan;
}
