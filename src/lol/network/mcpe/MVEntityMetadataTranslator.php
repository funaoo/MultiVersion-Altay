<?php

declare(strict_types=1);

namespace lol\network\mcpe;

use lol\network\global\MVTypeConverter;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;

final class MVEntityMetadataTranslator{

	private const BLOCK_RUNTIME_ID_PROPERTIES = [
		EntityIds::FALLING_BLOCK => EntityMetadataProperties::VARIANT,
		EntityIds::MINECART => EntityMetadataProperties::MINECART_DISPLAY_BLOCK,
		EntityIds::CHEST_MINECART => EntityMetadataProperties::MINECART_DISPLAY_BLOCK,
		EntityIds::COMMAND_BLOCK_MINECART => EntityMetadataProperties::MINECART_DISPLAY_BLOCK,
		EntityIds::HOPPER_MINECART => EntityMetadataProperties::MINECART_DISPLAY_BLOCK,
		EntityIds::TNT_MINECART => EntityMetadataProperties::MINECART_DISPLAY_BLOCK,
	];

	private function __construct(){

	}

	public static function translateBlockRuntimeIds(string $entityType, array $metadata, MVTypeConverter $converter) : array{
		$key = self::BLOCK_RUNTIME_ID_PROPERTIES[$entityType] ?? null;
		$property = $key !== null ? ($metadata[$key] ?? null) : null;
		if(!($property instanceof IntMetadataProperty)){
			return $metadata;
		}

		$metadata[$key] = new IntMetadataProperty(
			$converter->getMVBlockTranslator()->internalIdToNetworkId(
				MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($property->getValue())
			)
		);

		return $metadata;
	}
}
