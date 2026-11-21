<?php

declare(strict_types=1);

namespace lol\network\proto\v84\creative;

use Logger;
use function count;
use function implode;

final class v84CreativeReconciliation
{

	private const LOG_PREFIX = '[MultiVersion] 0.15.10 (84): ';

	private const LIST_SEPARATOR = ', ';

	private const REMAP_ARROW = ' -> ';

	public function __construct(
		public readonly v84CreativeTable $table,
		public readonly array $remapped,
		public readonly array $dropped,
		public readonly array $merged
	)
	{
	}

	public function summary() : string
	{
		return self::LOG_PREFIX . count($this->table->entries()) . ' creative entries are sent: ' . count($this->remapped) . ' remapped, ' . count($this->dropped) . ' dropped without a PM5 equivalent, ' . count($this->merged) . ' merged as duplicates';
	}

	public function details() : string
	{
		$remapped = [];
		foreach ($this->remapped as $from => $to) {
			$remapped[] = $from . self::REMAP_ARROW . $to;
		}

		return self::LOG_PREFIX . 'creative remapped: ' . implode(self::LIST_SEPARATOR, $remapped) . '; dropped: ' . implode(self::LIST_SEPARATOR, $this->dropped) . '; merged: ' . implode(self::LIST_SEPARATOR, $this->merged);
	}

	public function report(Logger $logger) : void
	{
		$logger->info($this->summary());
		$logger->debug($this->details());
	}
}
