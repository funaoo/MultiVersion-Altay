<?php

declare(strict_types=1);

namespace lol\network\anvil;

use pocketmine\item\Item;

final class AnvilOperation{

	public function __construct(
		public readonly Item $result,
		public readonly int $materialsUsed,
		public readonly int $levelCost
	){}
}
