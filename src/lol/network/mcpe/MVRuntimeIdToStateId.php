<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\utils\SingletonTrait;

class MVRuntimeIdToStateId
{
	use SingletonTrait;

	private const LOW_BITS_MASK = 0xFFFFFF;
	private const FACE_SHIFT = 24;
	private const FACE_COUNT = 6;

	private array $runtimeIdToStateId = [];

	private array $runtimeIdsByLowBits = [];

	public function __construct()
	{
		$blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
		foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state) {
			$blockRuntimeId = $blockTranslator->internalIdToNetworkId($stateId = $state->getStateId());
			$this->runtimeIdToStateId[$blockRuntimeId] = $stateId;
			$this->runtimeIdsByLowBits[$blockRuntimeId & self::LOW_BITS_MASK][$blockRuntimeId] = $blockRuntimeId;
		}
	}

	public function getStateIdFromRuntimeId(int $blockRuntimeId) : int
	{
		return $this->runtimeIdToStateId[$blockRuntimeId] ??= 0;
	}

	public function resolvePunchData(int $eventData) : ?array
	{
		foreach ($this->runtimeIdsByLowBits[$eventData & self::LOW_BITS_MASK] ?? [] as $blockRuntimeId) {
			for ($face = 0; $face < self::FACE_COUNT; ++$face) {
				if (($blockRuntimeId | ($face << self::FACE_SHIFT)) === $eventData) {
					return [$blockRuntimeId, $face];
				}
			}
		}

		return null;
	}
}
