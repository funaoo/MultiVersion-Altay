<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\Loader;
use lol\network\global\MVTypeConverter;
use lol\network\mcpe\MVParticleTypeDictionary;
use lol\network\mcpe\MVRuntimeIdToStateId;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;

final class v844ParticleTranslator
{
	use SingletonTrait;

	private const BLOCK_ID_MASK = 0xFFFFFF;

	private MVParticleTypeDictionary $latestDictionary;
	private MVParticleTypeDictionary $targetDictionary;

	public function __construct()
	{
		$this->latestDictionary = MVParticleTypeDictionary::loadFromString(Filesystem::fileGetContents(
			Path::join(BedrockDataFiles::ENUMS, "ParticleType.json")
		));
		$this->targetDictionary = MVParticleTypeDictionary::loadFromString(Filesystem::fileGetContents(
			Path::join(Loader::getPluginResourcePath(), "v844", "enums", "ParticleType.json")
		));
	}

	public function translate(LevelEventPacket $packet, MVTypeConverter $converter) : ?LevelEventPacket
	{
		if (($packet->eventId & LevelEvent::ADD_PARTICLE_MASK) !== 0) {
			return $this->translateStandardParticle($packet, $converter);
		}

		return match ($packet->eventId) {
			LevelEvent::PARTICLE_DESTROY, LevelEvent::PARTICLE_DESTROY_NO_SOUND => $this->rebuild(
				$packet,
				$packet->eventId,
				$this->translateBlockRuntimeId($packet->eventData, $converter)
			),
			LevelEvent::PARTICLE_PUNCH_BLOCK => $this->rebuild(
				$packet,
				$packet->eventId,
				$this->translateBlockRuntimeId($packet->eventData & self::BLOCK_ID_MASK, $converter)
					| ($packet->eventData & ~self::BLOCK_ID_MASK)
			),
			default => $packet
		};
	}

	private function translateStandardParticle(LevelEventPacket $packet, MVTypeConverter $converter) : ?LevelEventPacket
	{
		$latestParticleId = $packet->eventId & ~LevelEvent::ADD_PARTICLE_MASK;

		$name = $this->latestDictionary->fromId($latestParticleId);
		if ($name === null) {
			return null;
		}

		$targetParticleId = $this->targetDictionary->toId($name);
		if ($targetParticleId === null) {
			return null;
		}

		$eventId = LevelEvent::ADD_PARTICLE_MASK | $targetParticleId;

		if ($latestParticleId === ParticleIds::TERRAIN) {
			return $this->rebuild($packet, $eventId, $this->translateBlockRuntimeId($packet->eventData, $converter));
		}

		if ($latestParticleId === ParticleIds::ITEM_BREAK) {
			$eventData = v844ItemIdMetaTranslator::getInstance()->translate($packet->eventData, $converter);
			if ($eventData === null) {
				return null;
			}
			return $this->rebuild($packet, $eventId, $eventData);
		}

		return $this->rebuild($packet, $eventId, $packet->eventData);
	}

	private function translateBlockRuntimeId(int $latestRuntimeId, MVTypeConverter $converter) : int
	{
		return $converter->getMVBlockTranslator()->internalIdToNetworkId(
			MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($latestRuntimeId)
		);
	}

	private function rebuild(LevelEventPacket $packet, int $eventId, int $eventData) : LevelEventPacket
	{
		if ($eventId === $packet->eventId && $eventData === $packet->eventData) {
			return $packet;
		}

		return LevelEventPacket::create($eventId, $eventData, $packet->position);
	}
}
