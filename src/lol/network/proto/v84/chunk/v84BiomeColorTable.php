<?php

declare(strict_types=1);

namespace lol\network\proto\v84\chunk;

use lol\Loader;
use lol\network\proto\v84\blocks\v84BlockTable;
use pocketmine\utils\Filesystem;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function count;
use function is_array;
use function is_int;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class v84BiomeColorTable
{

	public const RESOURCE_FILE = 'biome_colors.json';

	public const FORMAT = 1;

	private const JSON_MAX_DEPTH = 8;

	private const BIOME_ID_MAX = 255;

	private const RGB_MAX = 0xffffff;

	private const BIOME_ID_SHIFT = 24;

	private function __construct(
		private readonly array $colors,
		private readonly int $fallback
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
		if (!is_array($document) || ($document['format'] ?? null) !== self::FORMAT || !isset($document['default'], $document['biomes'])) {
			throw new RuntimeException('Unsupported v84 biome color table format');
		}

		$colors = [];
		foreach ($document['biomes'] as $modernId => $pair) {
			$colors[(int) $modernId] = self::packPair($pair);
		}

		return new self($colors, self::packPair($document['default']));
	}

	public function colorOf(int $modernBiomeId) : int
	{
		return $this->colors[$modernBiomeId] ?? $this->fallback;
	}

	private static function packPair(mixed $pair) : int
	{
		if (!is_array($pair) || count($pair) !== 2 || !is_int($pair[0]) || !is_int($pair[1])) {
			throw new RuntimeException('Invalid v84 biome color entry');
		}

		if ($pair[0] < 0 || $pair[0] > self::BIOME_ID_MAX || $pair[1] < 0 || $pair[1] > self::RGB_MAX) {
			throw new RuntimeException('v84 biome color entry out of range');
		}

		return ($pair[0] << self::BIOME_ID_SHIFT) | $pair[1];
	}
}
