<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use lol\network\proto\v84\items\v84ItemDecoder;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemSlotData;
use pocketmine\item\Item;

final class v84ClientItemResolver implements v84ClientItems
{

	public function __construct(
		private readonly v84ItemEncoder $encoder,
		private readonly v84ItemDecoder $decoder
	)
	{
	}

	public function resolve(v84ItemSlotData $slot, array $candidates) : ?Item
	{
		if ($slot->isAir() || $slot->count === 0) {
			return $this->decoder->decode($slot);
		}

		foreach ($candidates as $candidate) {
			if (!$candidate->isNull() && $this->encoder->data($candidate)->sameKind($slot)) {
				return $this->decoder->decode($slot, $candidate);
			}
		}

		return $this->decoder->decode($slot);
	}
}
