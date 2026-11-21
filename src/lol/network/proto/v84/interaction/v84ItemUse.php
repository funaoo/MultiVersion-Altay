<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use lol\network\proto\v84\items\v84ItemSlotData;

interface v84ItemUse
{

	public function useInAir(v84ItemSlotData $item) : void;
}
