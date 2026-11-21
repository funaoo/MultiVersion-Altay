<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\Utils;
use function count;
use function ksort;
use const SORT_STRING;

class MVBlockStateDictionaryEntry
{
	private static array $uniqueRawStates = [];

	private string $rawStateProperties;

	public function __construct(
		private string          $stateName,
		array                   $stateProperties,
		private int             $meta,
		private ?BlockStateData $oldBlockStateData
	)
	{
		$rawStateProperties = self::encodeStateProperties($stateProperties);
		$this->rawStateProperties = self::$uniqueRawStates[$rawStateProperties] ??= $rawStateProperties;
	}

	public static function encodeStateProperties(array $properties) : string
	{
		if (count($properties) === 0) {
			return "";
		}
		ksort($properties, SORT_STRING);
		$tag = new CompoundTag();
		foreach (Utils::stringifyKeys($properties) as $k => $v) {
			$tag->setTag($k, $v);
		}
		return (new LittleEndianNbtSerializer())->write(new TreeRoot($tag));
	}

	public function getStateName() : string
	{
		return $this->stateName;
	}

	public function getRawStateProperties() : string
	{
		return $this->rawStateProperties;
	}

	public function generateStateData() : BlockStateData
	{
		return $this->oldBlockStateData ?? $this->generateCurrentStateData();
	}

	public function generateCurrentStateData() : BlockStateData
	{
		return new BlockStateData(
			$this->stateName,
			self::decodeStateProperties($this->rawStateProperties),
			BlockStateData::CURRENT_VERSION
		);
	}

	public static function decodeStateProperties(string $rawProperties) : array
	{
		if ($rawProperties === "") {
			return [];
		}
		return (new LittleEndianNbtSerializer())->read($rawProperties)->mustGetCompoundTag()->getValue();
	}

	public function getMeta() : int
	{
		return $this->meta;
	}
}
