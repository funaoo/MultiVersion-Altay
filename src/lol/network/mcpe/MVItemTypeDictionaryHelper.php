<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use function is_array;
use function is_bool;
use function is_int;
use function json_decode;
use function zlib_decode;
use const JSON_THROW_ON_ERROR;

final class MVItemTypeDictionaryHelper
{

	private function __construct()
	{

	}

	public static function loadFromRequiredItemList(string $data, ?string $componentData = null) : ItemTypeDictionary
	{
		$table = json_decode($data, true, flags: JSON_THROW_ON_ERROR);
		if (!is_array($table)) {
			throw new AssumptionFailedError("Invalid required_item_list format, expected object for root type");
		}

		$components = self::decodeComponents($componentData);
		$emptyNbt = new CacheableNbt(new CompoundTag());

		$params = [];
		foreach (Utils::stringifyKeys($table) as $name => $entry) {
			if (!is_array($entry) || !isset($entry["runtime_id"]) || !is_int($entry["runtime_id"])) {
				throw new AssumptionFailedError("Invalid required_item_list entry for $name");
			}

			$componentBased = $entry["component_based"] ?? false;
			$version = $entry["version"] ?? 0;
			if (!is_bool($componentBased) || !is_int($version)) {
				throw new AssumptionFailedError("Invalid required_item_list entry for $name");
			}

			$componentNbt = $components[$name] ?? null;

			$params[] = new ItemTypeEntry(
				$name,
				$entry["runtime_id"],
				$componentBased,
				$version,
				$componentNbt === null ? $emptyNbt : new CacheableNbt($componentNbt)
			);
		}

		return new ItemTypeDictionary($params);
	}

	private static function decodeComponents(?string $componentData) : array
	{
		if ($componentData === null) {
			return [];
		}

		$raw = zlib_decode($componentData);
		if ($raw === false) {
			throw new AssumptionFailedError("Failed to decompress item components");
		}

		$components = [];
		foreach ((new BigEndianNbtSerializer())->read($raw)->mustGetCompoundTag() as $itemName => $tag) {
			if ($tag instanceof CompoundTag) {
				$components[$itemName] = $tag;
			}
		}

		return $components;
	}
}
