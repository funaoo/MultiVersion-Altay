<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use lol\network\proto\v84\blocks\v84BlockTable;
use pocketmine\nbt\tag\CompoundTag;

final readonly class v84TileTable
{

	private function __construct(
		private array $translators
	)
	{
	}

	public static function create(v84BlockTable $blocks, ?v84TileItems $items = null) : self
	{
		return new self([
			v84TileId::SIGN => new v84SignTileTranslator(),
			v84TileId::CHEST => new v84ChestTileTranslator(),
			v84TileId::FURNACE => new v84FurnaceTileTranslator(),
			v84TileId::FLOWER_POT => new v84FlowerPotTileTranslator($blocks),
			v84TileId::MOB_SPAWNER => new v84MobSpawnerTileTranslator(),
			v84TileId::SKULL => new v84SkullTileTranslator(),
			v84TileId::BREWING_STAND => new v84BrewingStandTileTranslator($items),
			v84TileId::ITEM_FRAME => new v84ItemFrameTileTranslator($items),
			v84TileId::ENCHANT_TABLE => new v84NamedTileTranslator(v84TileId::ENCHANT_TABLE),
			v84TileId::DISPENSER => new v84NamedTileTranslator(v84TileId::DISPENSER),
			v84TileId::DROPPER => new v84NamedTileTranslator(v84TileId::DROPPER),
			v84TileId::HOPPER => new v84NamedTileTranslator(v84TileId::HOPPER),
			v84TileId::DAY_LIGHT_DETECTOR => new v84NamedTileTranslator(v84TileId::DAY_LIGHT_DETECTOR),
		]);
	}

	public function translatorFor(CompoundTag $modern) : ?v84TileTranslator
	{
		return $this->translators[v84TileFields::stringOf($modern, v84TileTag::ID, '')] ?? null;
	}
}
