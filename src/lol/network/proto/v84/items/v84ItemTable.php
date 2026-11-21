<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

use lol\Loader;
use lol\network\proto\v84\blocks\v84BlockTable;
use lol\network\proto\v84\blocks\v84LegacyBlock;
use pocketmine\utils\Filesystem;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function count;
use function ctype_digit;
use function explode;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function str_ends_with;
use const JSON_THROW_ON_ERROR;

final class v84ItemTable
{

	public const RESOURCE_FILE = 'items.json';

	public const FORMAT = 1;

	private const JSON_MAX_DEPTH = 8;

	private const INBOUND_SEPARATOR = ':';

	private function __construct(
		private readonly array $entries,
		private readonly array $families,
		private readonly int $neutral,
		private readonly array $blockItems,
		private readonly array $blockMeta,
		private readonly array $inbound
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
		if (!is_array($document) || ($document['format'] ?? null) !== self::FORMAT || !isset($document['items'], $document['neutral'])) {
			throw new RuntimeException('Unsupported v84 item table format');
		}

		$entries = [];
		foreach ($document['items'] as $name => $entry) {
			$entries[$name] = self::compileEntry($entry);
		}

		$families = [];
		foreach ($document['families'] ?? [] as $family) {
			if (!is_array($family) || count($family) !== 2) {
				throw new RuntimeException('Invalid v84 item family rule');
			}
			$families[] = [$family[0], self::packPair($family[1])];
		}

		$blockItems = [];
		foreach ($document['blockItems'] ?? [] as $blockId => $itemId) {
			if (!is_int($itemId) || $itemId < 0 || $itemId > v84LegacyItem::ID_MAX) {
				throw new RuntimeException('Invalid v84 block item alias');
			}
			$blockItems[(int) $blockId] = $itemId;
		}

		$blockMeta = [];
		foreach ($document['blockMeta'] ?? [] as $blockId => $mask) {
			if (!is_int($mask) || $mask < 0 || $mask > v84LegacyBlock::META_MASK) {
				throw new RuntimeException('Invalid v84 block item meta mask');
			}
			$blockMeta[(int) $blockId] = $mask;
		}

		$inbound = [];
		foreach ($document['inbound'] ?? [] as $key => $name) {
			$inbound[self::inboundKey($key)] = is_string($name) && $name !== '' ? $name : throw new RuntimeException('Invalid v84 inbound item alias');
		}

		return new self($entries, $families, self::packPair($document['neutral']), $blockItems, $blockMeta, $inbound);
	}

	public function lookup(string $name, int $meta) : ?int
	{
		$entry = $this->entries[$name] ?? null;
		if ($entry === null) {
			return null;
		}

		return is_int($entry) ? $entry : $entry->resolve($meta);
	}

	public function family(string $name) : ?int
	{
		foreach ($this->families as [$suffix, $packed]) {
			if (str_ends_with($name, $suffix)) {
				return $packed;
			}
		}

		return null;
	}

	public function inboundName(int $id, int $damage) : ?string
	{
		return $this->inbound[v84LegacyItem::pack($id, $damage)] ?? null;
	}

	public function neutral() : int
	{
		return $this->neutral;
	}

	public function blockItem(int $blockId, int $blockMeta) : int
	{
		$itemId = $this->blockItems[$blockId] ?? null;
		if ($itemId !== null) {
			return v84LegacyItem::pack($itemId, 0);
		}

		return v84LegacyItem::pack($blockId, $blockMeta & ($this->blockMeta[$blockId] ?? v84LegacyBlock::META_MASK));
	}

	private static function compileEntry(mixed $entry) : int|v84ItemVariants
	{
		if (!is_array($entry)) {
			throw new RuntimeException('Invalid v84 item entry');
		}

		if (!isset($entry['m'])) {
			return self::packPair($entry);
		}

		$variants = [];
		foreach ($entry['m'] as $meta => $pair) {
			$variants[(int) $meta] = self::packPair($pair);
		}

		return new v84ItemVariants($variants, self::packPair($entry['d']));
	}

	private static function inboundKey(mixed $key) : int
	{
		$parts = explode(self::INBOUND_SEPARATOR, (string) $key);
		if (count($parts) !== 2 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
			throw new RuntimeException('Invalid v84 inbound item alias key');
		}

		return self::packPair([(int) $parts[0], (int) $parts[1]]);
	}

	private static function packPair(mixed $pair) : int
	{
		if (!is_array($pair) || count($pair) !== 2 || !is_int($pair[0]) || !is_int($pair[1])) {
			throw new RuntimeException('Invalid v84 legacy item pair');
		}

		if ($pair[0] < 0 || $pair[0] > v84LegacyItem::ID_MAX || $pair[1] < 0 || $pair[1] > v84LegacyItem::DAMAGE_MASK) {
			throw new RuntimeException('v84 legacy item pair out of range');
		}

		return v84LegacyItem::pack($pair[0], $pair[1]);
	}
}
