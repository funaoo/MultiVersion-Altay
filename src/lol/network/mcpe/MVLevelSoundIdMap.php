<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use pocketmine\utils\AssumptionFailedError;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class MVLevelSoundIdMap
{

	private function __construct(
		private array $idToName,
		private array $nameToId
	)
	{

	}

	public static function loadFromString(string $contents) : self
	{
		$decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($decoded)) {
			throw new AssumptionFailedError("Invalid level sound ID map: expected an object");
		}

		$idToName = [];
		$nameToId = [];

		foreach ($decoded as $name => $id) {
			if (!is_string($name) || !is_int($id)) {
				throw new AssumptionFailedError("Invalid level sound ID map: entries must be string => int");
			}
			$idToName[$id] = $name;
			$nameToId[$name] = $id;
		}

		return new self($idToName, $nameToId);
	}

	public function toId(string $name) : ?int
	{
		return $this->nameToId[$name] ?? null;
	}

	public function toName(int $id) : ?string
	{
		return $this->idToName[$id] ?? null;
	}
}
