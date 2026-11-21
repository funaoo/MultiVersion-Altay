<?php

declare(strict_types=1);

namespace lol\network\proto\v84\recipes;

use lol\Loader;
use lol\network\proto\v84\blocks\v84BlockTable;
use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\items\v84LegacyItem;
use pocketmine\utils\Filesystem;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function array_values;
use function count;
use function ctype_xdigit;
use function hex2bin;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function json_decode;
use function strlen;
use const JSON_THROW_ON_ERROR;

final class v84RecipeTable
{

	public const RESOURCE_FILE = 'recipes.json';

	public const FORMAT = 1;

	private const JSON_MAX_DEPTH = 8;

	private const ID_HEX_LENGTH = 32;

	public const GRID_SIDE_MAX = 3;

	private const COUNT_MAX = 0xff;

	private function __construct(
		private readonly array $shaped,
		private readonly array $furnace
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
		if (!is_array($document) || ($document['format'] ?? null) !== self::FORMAT || !is_array($document['shaped'] ?? null) || !is_array($document['furnace'] ?? null)) {
			throw new RuntimeException('Unsupported v84 recipe table format');
		}

		return new self(self::compileShaped($document['shaped']), self::compileFurnace($document['furnace']));
	}

	public function shaped() : array
	{
		return $this->shaped;
	}

	public function furnace() : array
	{
		return $this->furnace;
	}

	private static function compileShaped(array $entries) : array
	{
		$recipes = [];
		foreach ($entries as $entry) {
			$recipe = self::compileShapedEntry($entry);
			if (isset($recipes[$recipe->id])) {
				throw new RuntimeException('Duplicate v84 recipe id');
			}
			$recipes[$recipe->id] = $recipe;
		}

		if ($recipes === []) {
			throw new RuntimeException('Empty v84 shaped recipe list');
		}

		return array_values($recipes);
	}

	private static function compileShapedEntry(mixed $entry) : v84ShapedRecipe
	{
		if (!is_array($entry) || !is_string($entry['id'] ?? null) || !is_int($entry['width'] ?? null) || !is_int($entry['height'] ?? null) || !is_array($entry['ingredients'] ?? null) || !is_bool($entry['table'] ?? null)) {
			throw new RuntimeException('Invalid v84 shaped recipe');
		}

		$width = $entry['width'];
		$height = $entry['height'];
		if ($width < 1 || $width > self::GRID_SIDE_MAX || $height < 1 || $height > self::GRID_SIDE_MAX || count($entry['ingredients']) !== $width * $height) {
			throw new RuntimeException('Invalid v84 shaped recipe grid');
		}

		$ingredients = [];
		$filled = false;
		foreach ($entry['ingredients'] as $pair) {
			$ingredient = self::compileIngredient($pair);
			$filled = $filled || !$ingredient->isAir();
			$ingredients[] = $ingredient;
		}

		if (!$filled) {
			throw new RuntimeException('v84 shaped recipe without ingredients');
		}

		return new v84ShapedRecipe(self::decodeId($entry['id']), $width, $height, $ingredients, self::compileResult($entry['result'] ?? null), $entry['table']);
	}

	private static function compileFurnace(array $entries) : array
	{
		$recipes = [];
		foreach ($entries as $entry) {
			if (!is_array($entry)) {
				throw new RuntimeException('Invalid v84 furnace recipe');
			}

			$input = self::compileIngredient($entry['input'] ?? null);
			if ($input->isAir()) {
				throw new RuntimeException('v84 furnace recipe without input');
			}

			$key = $input->id . ':' . $input->damage;
			if (isset($recipes[$key])) {
				throw new RuntimeException('Duplicate v84 furnace input');
			}
			$recipes[$key] = new v84FurnaceRecipe($input, self::compileResult($entry['result'] ?? null));
		}

		if ($recipes === []) {
			throw new RuntimeException('Empty v84 furnace recipe list');
		}

		return array_values($recipes);
	}

	private static function decodeId(string $hex) : string
	{
		$id = strlen($hex) === self::ID_HEX_LENGTH && ctype_xdigit($hex) ? hex2bin($hex) : false;
		if ($id === false) {
			throw new RuntimeException('Invalid v84 recipe id');
		}

		return $id;
	}

	private static function compileIngredient(mixed $pair) : v84ItemSlotData
	{
		[$id, $damage] = self::readPair($pair);
		if ($id === v84LegacyItem::AIR) {
			if ($damage !== 0) {
				throw new RuntimeException('v84 recipe air with damage');
			}

			return v84ItemSlotData::air();
		}

		return new v84ItemSlotData($id, 1, $damage, '');
	}

	private static function compileResult(mixed $triple) : v84ItemSlotData
	{
		if (!is_array($triple) || count($triple) !== 3 || !is_int($triple[2])) {
			throw new RuntimeException('Invalid v84 recipe result');
		}

		[$id, $damage] = self::readPair([$triple[0], $triple[1]]);
		if ($id === v84LegacyItem::AIR || $triple[2] < 1 || $triple[2] > self::COUNT_MAX) {
			throw new RuntimeException('v84 recipe result out of range');
		}

		return new v84ItemSlotData($id, $triple[2], $damage, '');
	}

	private static function readPair(mixed $pair) : array
	{
		if (!is_array($pair) || count($pair) !== 2 || !is_int($pair[0]) || !is_int($pair[1])) {
			throw new RuntimeException('Invalid v84 recipe item pair');
		}

		if ($pair[0] < 0 || $pair[0] > v84LegacyItem::ID_MAX || $pair[1] < 0 || $pair[1] > v84LegacyItem::DAMAGE_MASK) {
			throw new RuntimeException('v84 recipe item pair out of range');
		}

		return [$pair[0], $pair[1]];
	}
}
