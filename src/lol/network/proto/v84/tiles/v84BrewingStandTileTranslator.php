<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\block\tile\BrewingStand;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

final readonly class v84BrewingStandTileTranslator implements v84ItemTileTranslator
{

	private const SLOT_COUNT = 4;

	public function __construct(
		private ?v84TileItems $items = null
	)
	{
	}

	public function translate(CompoundTag $modern) : CompoundTag
	{
		return $this->stand($modern, []);
	}

	public function translateTile(Spawnable $tile, CompoundTag $modern) : CompoundTag
	{
		if ($this->items === null || !$tile instanceof BrewingStand) {
			return $this->translate($modern);
		}

		$inventory = $tile->getInventory();
		$entries = [];
		for ($slot = 0; $slot < self::SLOT_COUNT; ++$slot) {
			$entry = $this->items->compound($inventory->getItem($slot), $slot);
			if ($entry !== null) {
				$entries[] = $entry;
			}
		}

		return $this->stand($modern, $entries);
	}

	private function stand(CompoundTag $modern, array $entries) : CompoundTag
	{
		$stand = v84TileFields::header(v84TileId::BREWING_STAND, $modern)
			->setShort(v84TileTag::COOK_TIME, v84TileFields::intOf($modern, v84TileTag::COOK_TIME, 0))
			->setTag(v84TileTag::ITEMS, new ListTag($entries, NBT::TAG_Compound));

		return v84TileFields::copyCustomName($stand, $modern);
	}
}
