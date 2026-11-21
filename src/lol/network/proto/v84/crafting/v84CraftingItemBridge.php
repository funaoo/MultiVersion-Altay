<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

use lol\network\proto\v84\items\v84ItemDecoder;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\Item;

final class v84CraftingItemBridge implements v84CraftingItems
{

	public function __construct(
		private readonly v84ItemEncoder $encoder,
		private readonly v84ItemDecoder $decoder
	)
	{
	}

	public function legacy(Item $item) : v84ItemSlotData
	{
		return $this->encoder->data($item);
	}

	public function modern(v84ItemSlotData $slot) : ?Item
	{
		return $this->decoder->decode($slot);
	}
}
