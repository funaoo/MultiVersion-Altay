<?php

declare(strict_types=1);

namespace lol\network\proto\latest;

use lol\network\global\MVTypeConverter;
use lol\network\mcpe\MVBlockStateDictionary;
use lol\network\mcpe\MVBlockTranslator;
use lol\network\mcpe\MVItemIdMetaDowngrader;
use lol\network\mcpe\MVItemTranslator;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\network\mcpe\convert\ItemTypeDictionaryFromDataHelper;
use lol\network\global\MVSkinAdapter;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class LatestTypeConverter
{
	use SingletonTrait;

	private MVTypeConverter $converter;

	public function __construct() {
		$this->converter = new MVTypeConverter(
			$blockItemIdMap = BlockItemIdMap::getInstance(),
			$blockTranslator = new MVBlockTranslator(
				MVBlockStateDictionary::loadFromNetworkPalette(Filesystem::fileGetContents(BedrockDataFiles::BLOCK_PALETTE_NBT), Filesystem::fileGetContents(BedrockDataFiles::BLOCK_STATE_META_MAP_JSON)),
				GlobalBlockStateHandlers::getSerializer(),
			),
			$itemTypeDictionary = ItemTypeDictionaryFromDataHelper::loadFromString(
				Filesystem::fileGetContents(BedrockDataFiles::ITEM_PALETTE_JSON),
				Filesystem::fileGetContents(BedrockDataFiles::ITEM_COMPONENTS_NBT)
			),
			new MVItemTranslator(
				$itemTypeDictionary,
				$blockTranslator->getBlockStateDictionary(),
				GlobalItemDataHandlers::getSerializer(),
				GlobalItemDataHandlers::getDeserializer(),
				$blockItemIdMap,
				new MVItemIdMetaDowngrader($itemTypeDictionary, 181)
			),
			new MVSkinAdapter(),
		);
	}

	public function getConverter() : MVTypeConverter
	{
		return $this->converter;
	}

}
