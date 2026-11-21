<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

use pocketmine\math\Vector3;

interface v84PlayerActions
{

	public function perform(int $action, Vector3 $position, int $face) : void;
}
