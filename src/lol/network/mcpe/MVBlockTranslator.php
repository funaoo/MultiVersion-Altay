<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\BlockStateSerializer;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\utils\AssumptionFailedError;
use function array_key_exists;
use function implode;

class MVBlockTranslator
{

	public const LEGACY_BLOCK_ID_MAX = 255;
	public const LEGACY_META_MAX = 15;

	private array $networkIdCache = [];

	private array $legacyIdMetaCache = [];

	private array $chunkIdMetaCache = [];

	private array $chunkNetworkIdCache = [];

	private ?array $fallbackLegacyIdMeta = null;

	private array $reportedFailures = [];

	private BlockStateData $fallbackStateData;
	private int $fallbackStateId;

	public function __construct(
		private MVBlockStateDictionary $blockStateDictionary,
		private BlockStateSerializer   $blockStateSerializer
	)
	{
		$this->fallbackStateData = BlockStateData::current(BlockTypeNames::INFO_UPDATE, []);
		$this->fallbackStateId = $this->blockStateDictionary->lookupStateIdFromData($this->fallbackStateData) ??
			throw new AssumptionFailedError(BlockTypeNames::INFO_UPDATE . " should always exist");
	}

	public function internalIdToNetworkStateData(int $internalStateId) : BlockStateData
	{
		$networkRuntimeId = $this->internalIdToNetworkId($internalStateId);

		return $this->blockStateDictionary->generateDataFromStateId($networkRuntimeId) ?? throw new AssumptionFailedError("We just looked up this state ID, so it must exist");
	}

	public function internalIdToNetworkId(int $internalStateId) : int
	{
		if (isset($this->networkIdCache[$internalStateId])) {
			return $this->networkIdCache[$internalStateId];
		}

		try {
			$blockStateData = $this->blockStateSerializer->serialize($internalStateId);

			$networkId = $this->blockStateDictionary->lookupStateIdFromData($blockStateData);
			if ($networkId === null) {
				throw new BlockStateSerializeException("Unmapped blockstate returned by blockstate serializer: " . self::describeStateData($blockStateData));
			}
		} catch (BlockStateSerializeException $e) {
			$this->reportOnce("internal:$internalStateId", "Internal block state $internalStateId was replaced by " . BlockTypeNames::INFO_UPDATE . ": " . $e->getMessage());
			$networkId = $this->fallbackStateId;
		}

		return $this->networkIdCache[$internalStateId] = $networkId;
	}

	public function networkIdToLegacyIdMeta(int $networkRuntimeId) : ?array
	{
		if (array_key_exists($networkRuntimeId, $this->legacyIdMetaCache)) {
			return $this->legacyIdMetaCache[$networkRuntimeId];
		}

		return $this->legacyIdMetaCache[$networkRuntimeId] = $this->resolveLegacyIdMeta($networkRuntimeId);
	}

	public function internalIdToLegacyIdMeta(int $internalStateId) : ?array
	{
		return $this->networkIdToLegacyIdMeta($this->internalIdToNetworkId($internalStateId));
	}

	public function internalIdToChunkIdMeta(int $internalStateId) : array
	{
		return $this->chunkIdMetaCache[$internalStateId] ??= $this->networkIdToChunkIdMeta($this->internalIdToNetworkId($internalStateId));
	}

	public function networkIdToChunkIdMeta(int $networkRuntimeId) : array
	{
		return $this->chunkNetworkIdCache[$networkRuntimeId] ??= $this->blockStateDictionary->getChunkIdMetaFromStateId($networkRuntimeId) ??
			$this->networkIdToLegacyIdMeta($networkRuntimeId) ??
			$this->getFallbackLegacyIdMeta();
	}

	public function getFallbackLegacyIdMeta() : array
	{
		return $this->fallbackLegacyIdMeta ??= $this->networkIdToLegacyIdMeta($this->fallbackStateId) ??
			throw new AssumptionFailedError(BlockTypeNames::INFO_UPDATE . " should always have a verified legacy id and meta");
	}

	private function resolveLegacyIdMeta(int $networkRuntimeId) : ?array
	{
		$legacyId = $this->blockStateDictionary->getLegacyBlockIdFromStateId($networkRuntimeId);
		$meta = $this->blockStateDictionary->getMetaFromStateId($networkRuntimeId);

		if ($legacyId === null || $meta === null) {
			$this->reportOnce("legacy:$networkRuntimeId", "Network block state $networkRuntimeId " . $this->describeNetworkState($networkRuntimeId) . " has no verified legacy equivalent, chunks use " . BlockTypeNames::INFO_UPDATE . " instead");
			return null;
		}

		if ($legacyId < 0 || $legacyId > self::LEGACY_BLOCK_ID_MAX || $meta < 0 || $meta > self::LEGACY_META_MAX) {
			$this->reportOnce("legacy:$networkRuntimeId", "Network block state $networkRuntimeId " . $this->describeNetworkState($networkRuntimeId) . " maps to legacy id $legacyId meta $meta, which is out of range (id 0-" . self::LEGACY_BLOCK_ID_MAX . ", meta 0-" . self::LEGACY_META_MAX . "), chunks use " . BlockTypeNames::INFO_UPDATE . " instead");
			return null;
		}

		return [$legacyId, $meta];
	}

	private function describeNetworkState(int $networkRuntimeId) : string
	{
		$data = $this->blockStateDictionary->generateCurrentDataFromStateId($networkRuntimeId);

		return $data === null ? "(unknown state)" : self::describeStateData($data);
	}

	private static function describeStateData(BlockStateData $data) : string
	{
		$properties = [];
		foreach ($data->getStates() as $name => $tag) {
			$properties[] = $name . "=" . $tag->getValue();
		}

		return $data->getName() . "[" . implode(",", $properties) . "]";
	}

	private function reportOnce(string $key, string $message) : void
	{
		if (isset($this->reportedFailures[$key])) {
			return;
		}

		$this->reportedFailures[$key] = true;
		\GlobalLogger::get()->debug($message);
	}

	public function internalIdToCurrentNetworkStateData(int $internalStateId) : BlockStateData
	{
		$networkRuntimeId = $this->internalIdToNetworkId($internalStateId);

		return $this->blockStateDictionary->generateCurrentDataFromStateId($networkRuntimeId) ?? throw new AssumptionFailedError("We just looked up this state ID, so it must exist");
	}

	public function getBlockStateDictionary() : MVBlockStateDictionary
	{
		return $this->blockStateDictionary;
	}

	public function getFallbackStateData() : BlockStateData
	{
		return $this->fallbackStateData;
	}
}
