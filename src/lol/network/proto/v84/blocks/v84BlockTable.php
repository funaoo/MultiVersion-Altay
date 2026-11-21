<?php

declare(strict_types=1);

namespace lol\network\proto\v84\blocks;

use lol\Loader;
use pocketmine\utils\Filesystem;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function count;
use function is_array;
use function is_int;
use function json_decode;
use function str_ends_with;
use const JSON_THROW_ON_ERROR;

final class v84BlockTable
{

	public const RESOURCE_DIRECTORY = 'v84';

	public const RESOURCE_FILE = 'blocks.json';

	public const FORMAT = 1;

	private const JSON_MAX_DEPTH = 8;

	private const META_MAX = 15;

	private function __construct(
		private readonly array $entries,
		private readonly array $families
	)
	{
	}

	public static function load() : self
	{
		$path = Path::join(Loader::getPluginResourcePath(), self::RESOURCE_DIRECTORY, self::RESOURCE_FILE);

		return self::fromJson(Filesystem::fileGetContents($path));
	}

	public static function fromJson(string $json) : self
	{
		$document = json_decode($json, true, self::JSON_MAX_DEPTH, JSON_THROW_ON_ERROR);
		if (!is_array($document) || ($document['format'] ?? null) !== self::FORMAT || !isset($document['blocks'])) {
			throw new RuntimeException('Unsupported v84 block table format');
		}

		$entries = [];
		foreach ($document['blocks'] as $name => $entry) {
			$entries[$name] = self::compileEntry($entry);
		}

		$families = [];
		foreach ($document['families'] ?? [] as $family) {
			if (!is_array($family) || count($family) !== 2 || !isset($entries[$family[1]])) {
				throw new RuntimeException('Invalid v84 block family rule');
			}
			$families[] = [$family[0], $family[1]];
		}

		return new self($entries, $families);
	}

	public function lookup(string $name, array $states) : ?int
	{
		$entry = $this->entries[$name] ?? $this->familyEntry($name);
		if ($entry === null) {
			return null;
		}

		return is_int($entry) ? $entry : $entry->resolve($states);
	}

	private function familyEntry(string $name) : int|v84BlockVariants|null
	{
		foreach ($this->families as [$suffix, $target]) {
			if (str_ends_with($name, $suffix)) {
				return $this->entries[$target];
			}
		}

		return null;
	}

	private static function compileEntry(mixed $entry) : int|v84BlockVariants
	{
		if (!is_array($entry)) {
			throw new RuntimeException('Invalid v84 block entry');
		}

		if (!isset($entry['p'])) {
			return self::packPair($entry);
		}

		$variants = [];
		foreach ($entry['t'] as $key => $pair) {
			$variants[$key] = self::packPair($pair);
		}

		return new v84BlockVariants($entry['p'], $variants, self::packPair($entry['d']));
	}

	private static function packPair(mixed $pair) : int
	{
		if (!is_array($pair) || count($pair) !== 2 || !is_int($pair[0]) || !is_int($pair[1])) {
			throw new RuntimeException('Invalid v84 legacy block pair');
		}

		if ($pair[0] < 0 || $pair[0] > v84LegacyBlock::ID_MAX || $pair[1] < 0 || $pair[1] > self::META_MAX) {
			throw new RuntimeException('v84 legacy block pair out of range');
		}

		return v84LegacyBlock::pack($pair[0], $pair[1]);
	}
}
