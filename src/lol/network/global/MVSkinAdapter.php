<?php

declare(strict_types=1);

namespace lol\network\global;

use pocketmine\entity\InvalidSkinException;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\convert\LegacySkinAdapter;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use WeakMap;
use function is_array;
use function is_string;
use function json_decode;
use function strlen;

final class MVSkinAdapter extends LegacySkinAdapter
{

	private const CAPE_DATA_SIZE = 8192;

	private static ?WeakMap $origins = null;

	private static function origins() : WeakMap
	{
		return self::$origins ??= new WeakMap();
	}

	public function fromSkinData(SkinData $data) : Skin
	{
		if ($data->isPersona()) {
			return parent::fromSkinData($data);
		}

		$resourcePatch = json_decode($data->getResourcePatch(), true);
		$geometryName = is_array($resourcePatch) && is_string($resourcePatch["geometry"]["default"] ?? null) ? $resourcePatch["geometry"]["default"] : throw new InvalidSkinException("Missing geometry name field");
		$capeData = $data->getCapeImage()->getData();

		$skin = new Skin(
			$data->getSkinId(),
			$data->getSkinImage()->getData(),
			strlen($capeData) === self::CAPE_DATA_SIZE ? $capeData : "",
			$geometryName,
			$data->getGeometryData()
		);
		self::origins()[$skin] = $data;

		return $skin;
	}

	public function toSkinData(Skin $skin) : SkinData
	{
		$data = parent::toSkinData($skin);
		$origin = self::origins()[$skin] ?? null;
		if ($origin === null) {
			return $data;
		}

		$capeOnClassic = $origin->isPersonaCapeOnClassic();

		return new SkinData(
			$data->getSkinId(),
			$data->getResourcePatch(),
			$data->getSkinImage(),
			$data->getAnimations(),
			$origin->getCapeImage(),
			$data->getGeometryData(),
			$data->getGeometryDataEngineVersion(),
			$data->getAnimationData(),
			$origin->getCapeId(),
			$data->getFullSkinId(),
			$data->getArmSize(),
			$data->getSkinColor(),
			$capeOnClassic ? $origin->getPersonaPieces() : $data->getPersonaPieces(),
			$capeOnClassic ? $origin->getPieceTintColors() : $data->getPieceTintColors(),
			$data->isVerified(),
			$data->isPremium(),
			$data->isPersona(),
			$capeOnClassic,
			$data->isPrimaryUser(),
			$data->isOverride(),
			$data->getTrustedSkinFlag(),
			$data->getProfileHash()
		);
	}
}
