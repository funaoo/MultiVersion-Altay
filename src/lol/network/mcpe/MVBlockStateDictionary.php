<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use InvalidArgumentException;
use JsonException;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use ReflectionClass;
use function array_key_first;
use function array_keys;
use function array_map;
use function count;
use function get_debug_type;
use function implode;
use function is_array;
use function is_int;
use function is_null;
use function is_string;
use function json_decode;
use function zlib_decode;
use const JSON_THROW_ON_ERROR;

class MVBlockStateDictionary
{

	public const NO_LEGACY_META = -1;

	private array $stateDataToStateIdLookup = [];

	private ?array $idMetaToStateIdLookupCache = null;

	private array $stateIdToLegacyBlockId = [];

	private array $stateIdToChunkIdMeta = [];

	private MVBlockStateDowngrader $downgrader;

	public function __construct(
		private array $states
	)
	{
		$table = [];
		$knownProperties = [];
		$propertyValueSamples = [];
		foreach ($this->states as $stateId => $stateNbt) {
			$table[$stateNbt->getStateName()][$stateNbt->getRawStateProperties()] = $stateId;

			$name = $stateNbt->getStateName();
			if (!isset($knownProperties[$name])) {
				$knownProperties[$name] = [];
				$propertyValueSamples[$name] = [];
			}
			foreach (MVBlockStateDictionaryEntry::decodeStateProperties($stateNbt->getRawStateProperties()) as $propertyName => $value) {
				$knownProperties[$name][$propertyName] = true;
				$propertyValueSamples[$name][$propertyName][MVBlockStateDictionaryEntry::encodeStateProperties(["v" => $value])] = $value;
			}
		}

		$invariantValues = [];
		foreach (Utils::stringifyKeys($propertyValueSamples) as $name => $properties) {
			foreach (Utils::stringifyKeys($properties) as $propertyName => $values) {
				if (count($values) === 1) {
					$invariantValues[$name][$propertyName] = $values[array_key_first($values)];
				}
			}
		}

		$this->downgrader = new MVBlockStateDowngrader($knownProperties, $invariantValues);

		foreach (Utils::stringifyKeys($table) as $name => $stateIds) {
			if (count($stateIds) === 1) {
				$this->stateDataToStateIdLookup[$name] = $stateIds[array_key_first($stateIds)];
			} else {
				$this->stateDataToStateIdLookup[$name] = $stateIds;
			}
		}
	}

	public static function loadFromString(string $blockPaletteContents, string $metaMapContents, ?string $blockIdMapContents = null, ?string $chunkMapContents = null) : self {
		$upgrader = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader();
		$metaMap = json_decode($metaMapContents, flags: JSON_THROW_ON_ERROR);
		if (!is_array($metaMap)) {
			throw new InvalidArgumentException("Invalid metaMap, expected array for root type, got " . get_debug_type($metaMap));
		}

		$blockIdMap = null;
		if($blockIdMapContents !== null){
			$blockIdMap = json_decode($blockIdMapContents, true, flags: JSON_THROW_ON_ERROR);

			if(!is_array($blockIdMap)){
				throw new InvalidArgumentException("Invalid blockIdMap, expected array for root type, got " . get_debug_type($blockIdMap));
			}
		}

		$chunkMap = null;
		if($chunkMapContents !== null){
			$chunkMap = json_decode($chunkMapContents, true, flags: JSON_THROW_ON_ERROR);

			if(!is_array($chunkMap)){
				throw new InvalidArgumentException("Invalid chunkMap, expected array for root type, got " . get_debug_type($chunkMap));
			}
		}

		$entries = [];
		$stateIdToLegacyBlockId = [];
		$stateIdToChunkIdMeta = [];
		$uniqueNames = [];

		foreach ((new ReflectionClass(BlockTypeNames::class))->getConstants() as $value) {
			if (is_string($value)) {
				$uniqueNames[$value] = $value;
			}
		}

		$logger = \GlobalLogger::get();
		$statesWithoutId = 0;
		$statesWithoutMeta = 0;
		$statesWithChunkEquivalent = 0;
		$namesWithoutId = [];

		$palette = self::loadPaletteFromString($blockPaletteContents);
		if (count($metaMap) !== count($palette)) {
			throw new InvalidArgumentException("Block state meta map has " . count($metaMap) . " entries but the block palette has " . count($palette) . " states");
		}
		if ($chunkMap !== null && count($chunkMap) !== count($palette)) {
			throw new InvalidArgumentException("Block state chunk map has " . count($chunkMap) . " entries but the block palette has " . count($palette) . " states");
		}

		foreach ($palette as $i => $state) {
			$meta = $metaMap[$i] ?? null;
			if ($meta === null) {
				throw new InvalidArgumentException("Missing associated meta value for state $i (" . $state->toNbt() . ")");
			}
			if (!is_int($meta)) {
				throw new InvalidArgumentException("Invalid metaMap offset $i, expected int, got " . get_debug_type($meta));
			}
			if ($meta < self::NO_LEGACY_META) {
				throw new InvalidArgumentException("Invalid metaMap offset $i, expected a value of at least " . self::NO_LEGACY_META . ", got $meta");
			}
			$newState = $upgrader->upgrade($state);
			$uniqueName = $uniqueNames[$newState->getName()] ??= $newState->getName();
			$entries[$i] = new MVBlockStateDictionaryEntry($uniqueName, $newState->getStates(), $meta, $newState->equals($state) ? null : $state);

			if ($blockIdMap !== null) {
				$legacyBlockId = $blockIdMap[$state->getName()] ?? $blockIdMap[$uniqueName] ?? null;
				if ($legacyBlockId === null) {
					++$statesWithoutId;
					$namesWithoutId[$state->getName()] = true;
				} elseif (!is_int($legacyBlockId)) {
					throw new InvalidArgumentException("Invalid blockIdMap value for " . $state->getName() . ", expected int, got " . get_debug_type($legacyBlockId));
				} elseif ($meta === self::NO_LEGACY_META) {
					++$statesWithoutMeta;
				} else {
					$stateIdToLegacyBlockId[$i] = $legacyBlockId;
				}
			}

			$chunkPair = $chunkMap[$i] ?? null;
			if ($chunkPair !== null) {
				if (!is_array($chunkPair) || count($chunkPair) !== 2 || !is_int($chunkPair[0] ?? null) || !is_int($chunkPair[1] ?? null)) {
					throw new InvalidArgumentException("Invalid chunkMap offset $i, expected [id, meta]");
				}
				if ($chunkPair[0] < 0 || $chunkPair[0] > MVBlockTranslator::LEGACY_BLOCK_ID_MAX || $chunkPair[1] < 0 || $chunkPair[1] > MVBlockTranslator::LEGACY_META_MAX) {
					throw new InvalidArgumentException("Invalid chunkMap offset $i, pair out of range for the legacy chunk format");
				}
				if (isset($stateIdToLegacyBlockId[$i]) && $stateIdToLegacyBlockId[$i] <= MVBlockTranslator::LEGACY_BLOCK_ID_MAX) {
					throw new InvalidArgumentException("Invalid chunkMap offset $i, the state already has a verified legacy pair");
				}
				$stateIdToChunkIdMeta[$i] = [$chunkPair[0], $chunkPair[1]];
				++$statesWithChunkEquivalent;
			}
		}

		if ($statesWithoutId > 0 || $statesWithoutMeta > 0) {
			$logger->debug("Block palette has $statesWithoutId states without a legacy block id (" . implode(", ", array_keys($namesWithoutId)) . ") and $statesWithoutMeta states without a verified legacy meta");
		}
		if ($statesWithChunkEquivalent > 0) {
			$logger->debug("Block palette has $statesWithChunkEquivalent states with a chunk-only legacy equivalent");
		}
		$dictionary = new self($entries);
		$dictionary->stateIdToLegacyBlockId = $stateIdToLegacyBlockId;
		$dictionary->stateIdToChunkIdMeta = $stateIdToChunkIdMeta;
		return $dictionary;
	}

	private static function loadBlocksFromString(string $blockPaletteContents) : ListTag
	{
		$paletteRaw = zlib_decode($blockPaletteContents);
		if ($paletteRaw === false) {
			throw new InvalidArgumentException("Failed to decompress block palette");
		}
		return (new BigEndianNbtSerializer())->read($paletteRaw)->mustGetCompoundTag()->getListTag("blocks") ??
			throw new InvalidArgumentException("Missing \"blocks\" list in block palette");
	}

	public static function loadFromNetworkPalette(string $blockPaletteContents, string $metaMapContents) : self {
		$metaMap = json_decode($metaMapContents, flags: JSON_THROW_ON_ERROR);
		if (!is_array($metaMap)) {
			throw new InvalidArgumentException("Invalid metaMap, expected array for root type, got " . get_debug_type($metaMap));
		}

		$entries = [];
		$uniqueNames = [];

		foreach ((new ReflectionClass(BlockTypeNames::class))->getConstants() as $value) {
			if (is_string($value)) {
				$uniqueNames[$value] = $value;
			}
		}

		foreach (self::loadBlocksFromString($blockPaletteContents) as $i => $blockTag) {
			if (!($blockTag instanceof CompoundTag)) {
				throw new InvalidArgumentException("Invalid block palette entry at offset $i, expected TAG_Compound, got " . get_debug_type($blockTag));
			}
			$meta = $metaMap[$i] ?? null;
			if ($meta === null) {
				throw new InvalidArgumentException("Missing associated meta value for state $i (" . $blockTag . ")");
			}
			if (!is_int($meta)) {
				throw new InvalidArgumentException("Invalid metaMap offset $i, expected int, got " . get_debug_type($meta));
			}
			$networkId = $blockTag->getInt("network_id");
			$name = $blockTag->getString(BlockStateData::TAG_NAME);
			$states = $blockTag->getCompoundTag(BlockStateData::TAG_STATES) ??
				throw new InvalidArgumentException("Missing states for palette entry $i");
			$uniqueName = $uniqueNames[$name] ??= $name;
			$entries[$networkId] = new MVBlockStateDictionaryEntry($uniqueName, $states->getValue(), $meta, null);
		}

		return new self($entries);
	}

	public static function loadPaletteFromString(string $blockPaletteContents) : array
	{
		return array_map(
			fn(TreeRoot $root) => BlockStateData::fromNbt($root->mustGetCompoundTag()),
			(new NetworkNbtSerializer())->readMultiple($blockPaletteContents)
		);
	}

	public function generateDataFromStateId(int $networkRuntimeId) : ?BlockStateData
	{
		if (!isset($this->states[$networkRuntimeId])) {
			return null;
		}

		return $this->states[$networkRuntimeId]->generateStateData();
	}

	public function generateCurrentDataFromStateId(int $networkRuntimeId) : ?BlockStateData
	{
		if (!isset($this->states[$networkRuntimeId])) {
			return null;
		}

		return $this->states[$networkRuntimeId]->generateCurrentStateData();
	}

	public function lookupStateIdFromData(BlockStateData $data) : ?int
	{
		$stateId = $this->lookupExact($data);
		if ($stateId !== null) {
			return $stateId;
		}

		$downgraded = $this->downgrader->downgrade($data);
		if ($downgraded !== null) {
			return $this->lookupExact($downgraded);
		}

		return null;
	}

	private function lookupExact(BlockStateData $data) : ?int
	{
		$lookup = $this->stateDataToStateIdLookup[$data->getName()] ?? null;
		return match (true) {
			is_null($lookup) => null,
			is_int($lookup) => $lookup,
			is_array($lookup) => $lookup[MVBlockStateDictionaryEntry::encodeStateProperties($data->getStates())] ?? null,
			default => null,
		};
	}

	public function getDowngrader() : MVBlockStateDowngrader
	{
		return $this->downgrader;
	}

	public function getStates() : array
	{
		return $this->states;
	}

	public function getLegacyBlockIdFromStateId(int $networkRuntimeId) : ?int
	{
		return $this->stateIdToLegacyBlockId[$networkRuntimeId] ?? null;
	}

	public function getChunkIdMetaFromStateId(int $networkRuntimeId) : ?array
	{
		return $this->stateIdToChunkIdMeta[$networkRuntimeId] ?? null;
	}

	public function getMetaFromStateId(int $networkRuntimeId) : ?int
	{
		if (!isset($this->states[$networkRuntimeId])) {
			return null;
		}

		$meta = $this->states[$networkRuntimeId]->getMeta();

		return $meta === self::NO_LEGACY_META ? null : $meta;
	}

	public function lookupStateIdFromIdMeta(string $id, int $meta) : ?int
	{
		$metas = $this->getIdMetaToStateIdLookup()[$id] ?? null;
		if ($metas === null) {
			return null;
		}

		return match (true) {
			is_int($metas) => $metas,
			is_array($metas) => $metas[$meta] ?? null,
			default => null
		};
	}

	private function getIdMetaToStateIdLookup() : array
	{
		if ($this->idMetaToStateIdLookupCache === null) {
			$table = [];
			$unverified = [];

			foreach ($this->states as $i => $state) {
				if ($state->getMeta() === self::NO_LEGACY_META) {
					$unverified[$state->getStateName()] ??= $i;
					continue;
				}
				$table[$state->getStateName()][$state->getMeta()] = $i;
			}

			foreach (Utils::stringifyKeys($unverified) as $name => $i) {
				$table[$name][0] ??= $i;
			}

			$this->idMetaToStateIdLookupCache = [];
			foreach (Utils::stringifyKeys($table) as $name => $metaToStateId) {
				if (count($metaToStateId) === 1) {
					$this->idMetaToStateIdLookupCache[$name] = $metaToStateId[array_key_first($metaToStateId)];
				} else {
					$this->idMetaToStateIdLookupCache[$name] = $metaToStateId;
				}
			}
		}

		return $this->idMetaToStateIdLookupCache;
	}
}
