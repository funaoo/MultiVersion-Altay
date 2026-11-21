<?php

declare(strict_types=1);

namespace lol\network\proto\v84\creative;

use lol\network\proto\v84\inventory\v84ContainerId;
use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerSetContentPacket;

final class v84CreativeContent
{

	private function __construct(
		private readonly v84OutgoingContainerSetContentPacket $full,
		private readonly v84OutgoingContainerSetContentPacket $empty
	)
	{
	}

	public static function fromTable(v84CreativeTable $table) : self
	{
		$slots = [];
		foreach ($table->entries() as $entry) {
			$slots[] = v84ItemSlot::encode($entry->id, $entry->count, $entry->damage, $entry->compoundTag);
		}

		return new self(
			v84OutgoingContainerSetContentPacket::fromParts(v84ContainerId::CREATIVE, $slots, []),
			v84OutgoingContainerSetContentPacket::fromParts(v84ContainerId::CREATIVE, [], [])
		);
	}

	public function forPlayer(bool $spectator) : v84OutgoingContainerSetContentPacket
	{
		return $spectator ? $this->empty : $this->full;
	}
}
