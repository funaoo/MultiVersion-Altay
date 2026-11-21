<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use lol\network\proto\v84\blocks\v84BlockResolver;
use lol\network\proto\v84\blocks\v84BlockTable;
use lol\network\proto\v84\blocks\v84SubstitutionReport;
use lol\network\proto\v84\chunk\v84BiomeColorTable;
use lol\network\proto\v84\creative\v84CreativeTable;
use lol\network\proto\v84\items\v84ItemTable;
use lol\network\proto\v84\recipes\v84RecipeTable;
use lol\network\proto\v84\tiles\v84TileTable;
use pocketmine\block\RuntimeBlockStateRegistry;
use Logger;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Throwable;
use function count;
use function implode;

final class v84StartupCheck
{

	private const LOG_PREFIX = '[MultiVersion] 0.15.10 (84): ';

	private function __construct()
	{
	}

	public static function run(Logger $logger) : bool
	{
		try {
			$blocks = v84BlockTable::load();
			v84BiomeColorTable::load();
			v84ItemTable::load();
			v84RecipeTable::load();
			v84CreativeTable::load();
			v84TileTable::create($blocks);

			$serializer = GlobalBlockStateHandlers::getSerializer();
			$summary = (new v84SubstitutionReport(new v84BlockResolver($blocks, $serializer), $serializer))->summarize(self::knownStateIds());
		} catch (Throwable $e) {
			$logger->error(self::LOG_PREFIX . 'resources are invalid, the protocol is disabled: ' . $e->getMessage());

			return false;
		}

		$logger->info(self::LOG_PREFIX . count($summary->substitutedNames) . ' of ' . $summary->totalNames . ' block names have no equivalent and are sent as air');
		if ($summary->substitutedNames !== []) {
			$logger->debug(self::LOG_PREFIX . 'blocks sent as air: ' . implode(', ', $summary->substitutedNames));
		}

		return true;
	}

	private static function knownStateIds() : iterable
	{
		foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state) {
			yield $state->getStateId();
		}
	}
}
