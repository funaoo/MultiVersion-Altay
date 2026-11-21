<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\Durable;
use pocketmine\item\Item;

final class v84HeldItemCheck implements v84HeldItems
{

	public function __construct(private readonly v84ItemEncoder $encoder)
	{
	}

	public function matches(Item $held, v84ItemSlotData $slot) : bool
	{
		return $this->encoder->data($held)->sameItem($slot, !$held instanceof Durable);
	}
}
