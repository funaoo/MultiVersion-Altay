<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use pocketmine\math\Vector3;

interface v84BlockResync
{

	public function around(Vector3 $block, int $face) : void;
}
