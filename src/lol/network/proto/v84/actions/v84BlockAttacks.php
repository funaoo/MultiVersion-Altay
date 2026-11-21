<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

use pocketmine\math\Vector3;

interface v84BlockAttacks
{

	public function start(Vector3 $block, int $face) : void;

	public function stop(Vector3 $block) : void;
}
