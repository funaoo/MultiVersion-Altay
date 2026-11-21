<?php

declare(strict_types=1);

namespace lol\network\proto\v84\blocks;

use GlobalLogger;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockStateSerializeException;
use pocketmine\data\bedrock\block\BlockStateSerializer;
use function is_scalar;

final class v84BlockResolver
{

	private array $cache = [];

	private array $reported = [];

	public function __construct(
		private readonly v84BlockTable $table,
		private readonly BlockStateSerializer $serializer
	)
	{
	}

	public static function load(BlockStateSerializer $serializer) : self
	{
		return new self(v84BlockTable::load(), $serializer);
	}

	public function resolveInternalId(int $internalStateId) : int
	{
		return $this->cache[$internalStateId] ??= $this->computeInternalId($internalStateId);
	}

	public function resolveStateData(BlockStateData $data) : int
	{
		$states = [];
		foreach ($data->getStates() as $property => $tag) {
			$value = $tag->getValue();
			$states[$property] = is_scalar($value) ? (string) $value : '';
		}

		$packed = $this->table->lookup($data->getName(), $states);
		if ($packed === null) {
			$this->reportOnce('name:' . $data->getName(), 'Block ' . $data->getName() . ' has no 0.15.10 equivalent, it is sent as air');

			return v84LegacyBlock::AIR;
		}

		return $packed;
	}

	private function computeInternalId(int $internalStateId) : int
	{
		try {
			$data = $this->serializer->serialize($internalStateId);
		} catch (BlockStateSerializeException $e) {
			$this->reportOnce('internal:' . $internalStateId, 'Internal block state ' . $internalStateId . ' cannot be serialized, it is sent as air: ' . $e->getMessage());

			return v84LegacyBlock::AIR;
		}

		return $this->resolveStateData($data);
	}

	private function reportOnce(string $key, string $message) : void
	{
		if (isset($this->reported[$key])) {
			return;
		}

		$this->reported[$key] = true;
		GlobalLogger::get()->debug($message);
	}
}
