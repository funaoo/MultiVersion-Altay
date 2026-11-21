<?php

declare(strict_types=1);

namespace lol\network\proto\v84\frames;

use pocketmine\math\Vector3;

interface v84ItemFrameStrikes
{

	public function strike(Vector3 $frame) : void;
}
