<?php

declare(strict_types=1);

namespace lol\network\proto\v84\creative;

use lol\network\proto\v84\items\v84ItemDecoder;
use lol\network\proto\v84\items\v84ItemEncoder;
use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\items\v84LegacyItem;
use function array_values;

final class v84CreativeReconciler
{

	public function __construct(
		private readonly v84ItemDecoder $decoder,
		private readonly v84ItemEncoder $encoder,
		private readonly int $neutral
	)
	{
	}

	public function reconcile(v84CreativeTable $table) : v84CreativeReconciliation
	{
		$entries = [];
		$remapped = [];
		$dropped = [];
		$merged = [];
		foreach ($table->entries() as $entry) {
			$key = self::keyOf($entry);
			$resolved = $this->roundTrip($entry);
			if ($resolved === null) {
				$dropped[] = $key;
				continue;
			}

			$resolvedKey = self::keyOf($resolved);
			if (isset($entries[$resolvedKey])) {
				$merged[] = $key;
				continue;
			}

			$entries[$resolvedKey] = $resolved;
			if ($resolvedKey !== $key) {
				$remapped[$key] = $resolvedKey;
			}
		}

		return new v84CreativeReconciliation($table->withEntries(array_values($entries)), $remapped, $dropped, $merged);
	}

	private function roundTrip(v84ItemSlotData $entry) : ?v84ItemSlotData
	{
		$item = $this->decoder->decode($entry);
		if ($item === null || $item->isNull()) {
			return null;
		}

		$back = $this->encoder->data($item);
		if ($back->isAir() || $this->isSubstitute($entry, $back)) {
			return null;
		}

		return new v84ItemSlotData($back->id, $entry->count, $back->damage, '');
	}

	private function isSubstitute(v84ItemSlotData $entry, v84ItemSlotData $back) : bool
	{
		return self::packedOf($back) === $this->neutral && self::packedOf($entry) !== $this->neutral;
	}

	private static function packedOf(v84ItemSlotData $entry) : int
	{
		return v84LegacyItem::pack($entry->id, $entry->damage);
	}

	private static function keyOf(v84ItemSlotData $entry) : string
	{
		return $entry->id . ':' . $entry->damage;
	}
}
