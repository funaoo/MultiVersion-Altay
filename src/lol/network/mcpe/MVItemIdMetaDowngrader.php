<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use InvalidArgumentException;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use function is_array;

class MVItemIdMetaDowngrader {
	private array $renamedIds = [];
	private array $remappedMetas = [];

	public function __construct(ItemTypeDictionary $dictionary, int $schemaId)
	{
		$upgrader = GlobalItemDataHandlers::getUpgrader()->getIdMetaUpgrader();

		$networkIds = [];
		foreach ($upgrader->getSchemas() as $id => $schema) {
			if ($id <= $schemaId) {
				continue;
			}

			foreach (Utils::stringifyKeys($schema->getRenamedIds()) as $oldId => $newStringId) {
				if (isset($networkIds[$oldId])) {
					$networkIds[$newStringId] = $networkIds[$oldId];
				} else {
					try {
						$dictionary->fromStringId($oldId);
						$networkIds[$newStringId] = $oldId;
					} catch (InvalidArgumentException $e) {
					}
				}
			}

			foreach (Utils::stringifyKeys($schema->getRemappedMetas()) as $oldId => $metaToNewId) {
				if (isset($networkIds[$oldId])) {
					foreach ($metaToNewId as $oldMeta => $newStringId) {
						if (is_array($networkIds[$oldId])) {
							throw new AssumptionFailedError("Can't flatten IDs twice");
						} else {
							$networkIds[$newStringId] = [$networkIds[$oldId], $oldMeta];
						}
					}
				} else {
					try {
						$dictionary->fromStringId($oldId);
						foreach ($metaToNewId as $oldMeta => $newStringId) {
							$networkIds[$newStringId] = [$oldId, $oldMeta];
						}
					} catch (InvalidArgumentException $e) {
					}
				}
			}
		}

		foreach ($networkIds as $newStringId => $oldId) {
			if (is_array($oldId)) {
				$this->remappedMetas[$newStringId] = $oldId;
			} else {
				$this->renamedIds[$newStringId] = $oldId;
			}
		}
	}

	public function downgrade(string $id, int $meta) : array
	{
		$newId = $id;
		$newMeta = $meta;

		if (isset($this->remappedMetas[$newId])) {
			[$newId, $newMeta] = $this->remappedMetas[$newId];
		} elseif (isset($this->renamedIds[$newId])) {
			$newId = $this->renamedIds[$newId];
		}

		return [$newId, $newMeta];
	}
}
