<?php

declare(strict_types=1);

namespace lol\network\proto\v84\blocks;

use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\BlockStateSerializer;
use function count;
use function sort;

final class v84SubstitutionReport
{

	private const AIR_NAME = 'minecraft:air';

	public function __construct(
		private readonly v84BlockResolver $resolver,
		private readonly BlockStateSerializer $serializer
	)
	{
	}

	public function summarize(iterable $internalStateIds) : v84SubstitutionSummary
	{
		$mapped = [];
		foreach ($internalStateIds as $stateId) {
			$name = $this->nameOf($stateId);
			if ($name === null) {
				continue;
			}

			$mapped[$name] = ($mapped[$name] ?? false) || $this->resolver->resolveInternalId($stateId) !== v84LegacyBlock::AIR;
		}

		$substituted = [];
		foreach ($mapped as $name => $isMapped) {
			if (!$isMapped && $name !== self::AIR_NAME) {
				$substituted[] = $name;
			}
		}

		sort($substituted);

		return new v84SubstitutionSummary(count($mapped), $substituted);
	}

	private function nameOf(int $stateId) : ?string
	{
		try {
			return $this->serializer->serialize($stateId)->getName();
		} catch (BlockStateSerializeException) {
			return null;
		}
	}
}
