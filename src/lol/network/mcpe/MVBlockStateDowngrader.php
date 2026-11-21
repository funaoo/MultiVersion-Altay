<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\tag\Tag;
use function count;

final class MVBlockStateDowngrader
{

	private array $knownProperties = [];

	private array $invariantValues = [];

	public function __construct(array $knownProperties, array $invariantValues = [])
	{
		$this->knownProperties = $knownProperties;
		$this->invariantValues = $invariantValues;
	}

	public function downgrade(BlockStateData $data) : ?BlockStateData
	{
		$name = $data->getName();
		$known = $this->knownProperties[$name] ?? null;
		if ($known === null) {
			return null;
		}

		$fixedValues = $this->invariantValues[$name] ?? [];
		$states = $data->getStates();
		$filtered = [];
		$changed = false;

		foreach ($states as $propertyName => $value) {
			if (!isset($known[$propertyName])) {
				$changed = true;
				continue;
			}

			$fixed = $fixedValues[$propertyName] ?? null;
			if ($fixed !== null) {
				if (!$value->equals($fixed)) {
					$changed = true;
				}
				$filtered[$propertyName] = $fixed;
			} else {
				$filtered[$propertyName] = $value;
			}
		}

		if (!$changed) {
			return null;
		}

		if (count($filtered) !== count($known)) {
			return null;
		}

		return new BlockStateData($name, $filtered, $data->getVersion());
	}
}
