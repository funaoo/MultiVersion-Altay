<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use pocketmine\utils\AssumptionFailedError;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function max;
use const JSON_THROW_ON_ERROR;

final class MVParticleTypeDictionary
{

	private const COUNT_KEY = "_count";

	private function __construct(
		private array $idToName,
		private array $nameToId,
		private int   $count
	)
	{

	}

	public static function loadFromString(string $contents) : self
	{
		$decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($decoded) || !isset($decoded["values"]) || !is_array($decoded["values"])) {
			throw new AssumptionFailedError("Invalid ParticleType enum dump: missing \"values\"");
		}

		$idToName = [];
		$nameToId = [];
		$count = null;
		$highest = -1;

		foreach ($decoded["values"] as $name => $id) {
			if (!is_string($name) || !is_int($id)) {
				throw new AssumptionFailedError("Invalid ParticleType enum dump: entries must be string => int");
			}
			if ($name === self::COUNT_KEY) {
				$count = $id;
				continue;
			}
			$idToName[$id] = $name;
			$nameToId[$name] = $id;
			$highest = max($highest, $id);
		}

		return new self($idToName, $nameToId, $count ?? $highest + 1);
	}

	public function getCount() : int
	{
		return $this->count;
	}

	public function fromId(int $id) : ?string
	{
		return $this->idToName[$id] ?? null;
	}

	public function toId(string $name) : ?int
	{
		return $this->nameToId[$name] ?? null;
	}
}
