<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use lol\network\proto\v84\blocks\v84BlockTable;
use lol\network\proto\v84\blocks\v84LegacyBlock;
use pocketmine\nbt\tag\CompoundTag;
use function is_scalar;

final readonly class v84FlowerPotTileTranslator implements v84TileTranslator
{

	public function __construct(
		private v84BlockTable $blocks
	)
	{
	}

	public function translate(CompoundTag $modern) : CompoundTag
	{
		$packed = $this->plant($modern);

		return v84TileFields::header(v84TileId::FLOWER_POT, $modern)
			->setShort(v84TileTag::ITEM, v84LegacyBlock::id($packed))
			->setInt(v84TileTag::M_DATA, v84LegacyBlock::meta($packed));
	}

	private function plant(CompoundTag $modern) : int
	{
		$plant = $modern->getTag(v84TileTag::PLANT_BLOCK);
		if (!$plant instanceof CompoundTag) {
			return v84LegacyBlock::AIR;
		}

		$states = [];
		$stateTags = $plant->getTag(v84TileTag::STATES);
		if ($stateTags instanceof CompoundTag) {
			foreach ($stateTags->getValue() as $property => $tag) {
				$value = $tag->getValue();
				$states[$property] = is_scalar($value) ? (string) $value : '';
			}
		}

		return $this->blocks->lookup(v84TileFields::stringOf($plant, v84TileTag::NAME, ''), $states) ?? v84LegacyBlock::AIR;
	}
}
