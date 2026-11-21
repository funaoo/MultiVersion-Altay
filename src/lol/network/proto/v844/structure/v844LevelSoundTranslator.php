<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\Loader;
use lol\network\global\MVTypeConverter;
use lol\network\mcpe\MVLevelSoundIdMap;
use lol\network\mcpe\MVRuntimeIdToStateId;
use lol\network\proto\v844\packets\v844LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;

final class v844LevelSoundTranslator
{
	use SingletonTrait;

	private const NO_EXTRA_DATA = -1;

	private const BLOCK_EXTRA_DATA_SOUNDS = [
		LevelSoundEvent::BREAK => true,
		LevelSoundEvent::PLACE => true,
		LevelSoundEvent::HIT => true,
		LevelSoundEvent::LAND => true,
		LevelSoundEvent::ITEM_USE_ON => true,
	];

	private MVLevelSoundIdMap $map;

	public function __construct()
	{
		$this->map = MVLevelSoundIdMap::loadFromString(Filesystem::fileGetContents(
			Path::join(Loader::getPluginResourcePath(), "v844", "level_sound_id_map.json")
		));
	}

	public function getMap() : MVLevelSoundIdMap
	{
		return $this->map;
	}

	public function translate(LevelSoundEventPacket $packet, MVTypeConverter $converter) : ?LevelSoundEventPacket
	{
		if ($this->map->toId($packet->sound) === null) {
			return null;
		}

		$translated = v844LevelSoundEventPacket::fromLatest($packet);

		if ($packet->extraData !== self::NO_EXTRA_DATA && isset(self::BLOCK_EXTRA_DATA_SOUNDS[$packet->sound])) {
			$translated->extraData = $converter->getMVBlockTranslator()->internalIdToNetworkId(
				MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->extraData)
			);
		}

		return $translated;
	}
}
