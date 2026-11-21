<?php

declare(strict_types=1);

namespace lol\network\proto\v844;

use lol\Loader;
use lol\network\global\MVTypeConverter;
use lol\network\mcpe\MVBlockStateDictionary;
use lol\network\mcpe\MVBlockTranslator;
use lol\network\mcpe\MVItemIdMetaDowngrader;
use lol\network\mcpe\MVItemTranslator;
use lol\network\mcpe\MVItemTypeDictionaryHelper;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use lol\network\global\MVSkinAdapter;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use Symfony\Component\Filesystem\Path;

final class v844TypeConverter
{
	use SingletonTrait;

	private const ITEM_SCHEMA_ID = 251;

	private MVTypeConverter $converter;

	public function __construct()
	{
		$this->converter = new MVTypeConverter(
			$blockItemIdMap = BlockItemIdMap::getInstance(),
			$blockTranslator = new MVBlockTranslator(
				MVBlockStateDictionary::loadFromString(Filesystem::fileGetContents(
					Path::join(Loader::getPluginResourcePath(), "v844", "canonical_block_states.nbt")
				), Filesystem::fileGetContents(
					Path::join(Loader::getPluginResourcePath(), "v844", "block_state_meta_map.json")
				)),
				GlobalBlockStateHandlers::getSerializer(),
			),
			$itemTypeDictionary = MVItemTypeDictionaryHelper::loadFromRequiredItemList(
				Filesystem::fileGetContents(
					Path::join(Loader::getPluginResourcePath(), "v844", "required_item_list.json")
				),
				Filesystem::fileGetContents(BedrockDataFiles::ITEM_COMPONENTS_NBT)
			),
			new MVItemTranslator(
				$itemTypeDictionary,
				$blockTranslator->getBlockStateDictionary(),
				GlobalItemDataHandlers::getSerializer(),
				GlobalItemDataHandlers::getDeserializer(),
				$blockItemIdMap,
				new MVItemIdMetaDowngrader($itemTypeDictionary, self::ITEM_SCHEMA_ID)
			),
			new MVSkinAdapter(),
		);
	}

	public function getConverter() : MVTypeConverter
	{
		return $this->converter;
	}
}
