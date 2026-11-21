<?php

declare(strict_types=1);

namespace lol\network\proto\v84\creative;

use lol\Loader;
use lol\network\proto\v84\blocks\v84BlockTable;
use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\items\v84LegacyItem;
use pocketmine\utils\Filesystem;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function array_is_list;
use function array_values;
use function count;
use function is_array;
use function is_int;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class v84CreativeTable
{

	public const RESOURCE_FILE = 'creative.json';

	public const FORMAT = 1;

	private const JSON_MAX_DEPTH = 4;

	private const ENTRY_COUNT = 1;

	private const PAIR_LENGTH = 2;

	private function __construct(
		private readonly array $entries
	)
	{
	}

	public static function load() : self
	{
		$path = Path::join(Loader::getPluginResourcePath(), v84BlockTable::RESOURCE_DIRECTORY, self::RESOURCE_FILE);

		return self::fromJson(Filesystem::fileGetContents($path));
	}

	public static function fromJson(string $json) : self
	{
		$document = json_decode($json, true, self::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR);
		if (!is_array($document) || ($document['format'] ?? null) !== self::FORMAT || !is_array($document['items'] ?? null) || $document['items'] === []) {
			throw new RuntimeException('Unsupported v84 creative table format');
		}

		return new self(self::compile($document['items']));
	}

	public function entries() : array
	{
		return $this->entries;
	}

	public function withEntries(array $entries) : self
	{
		return new self($entries);
	}

	private static function compile(array $pairs) : array
	{
		$entries = [];
		foreach ($pairs as $pair) {
			$entry = self::compileEntry($pair);
			$key = $entry->id . ':' . $entry->damage;
			if (isset($entries[$key])) {
				throw new RuntimeException('Duplicate v84 creative item');
			}
			$entries[$key] = $entry;
		}

		return array_values($entries);
	}

	private static function compileEntry(mixed $pair) : v84ItemSlotData
	{
		if (!is_array($pair) || !array_is_list($pair) || count($pair) !== self::PAIR_LENGTH || !is_int($pair[0]) || !is_int($pair[1])) {
			throw new RuntimeException('Invalid v84 creative item pair');
		}

		[$id, $damage] = $pair;
		if ($id <= v84LegacyItem::AIR || $id > v84LegacyItem::ID_MAX || $damage < 0 || $damage >= v84LegacyItem::ANY_DAMAGE) {
			throw new RuntimeException('v84 creative item out of range');
		}

		return new v84ItemSlotData($id, self::ENTRY_COUNT, $damage, '');
	}
}
