<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\math\Vector3;

interface v84BlockUse
{

	public function useItemOn(Vector3 $block, int $face, Vector3 $clickOffset, v84ItemSlotData $item) : void;
}
