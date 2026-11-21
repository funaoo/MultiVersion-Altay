<?php

declare(strict_types=1);

namespace lol\network\proto\latest;

use lol\Loader;
use lol\network\global\MVSkinHelper;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function md5;
use function str_contains;
use const JSON_THROW_ON_ERROR;

class LatestSkinHelper implements MVSkinHelper
{

	private const GEOMETRY_WIDE = "geometry.humanoid.custom";

	private const GEOMETRY_SLIM = "geometry.humanoid.customSlim";

	public function adaptSkinData(SkinData &$skin) : void
	{
		$resourcePatch = $skin->getResourcePatch();
		$geometryData = $skin->getGeometryData();

		if (!str_contains($geometryData, "format_version")) {
			$geometryData = Loader::getDefaultSkinFormat();
			$resourcePatch = $this->resolveResourcePatch($resourcePatch, $skin->getArmSize());
		}

		$capeId = $skin->getCapeId();
		if ($capeId === "" && $skin->getCapeImage()->getData() !== "") {
			$capeId = md5($skin->getCapeImage()->getData());
		}

		$skin = new SkinData(
			$skin->getSkinId(),
			$resourcePatch,
			$skin->getSkinImage(),
			$skin->getAnimations(),
			$skin->getCapeImage(),
			$geometryData,
			$skin->getGeometryDataEngineVersion(),
			$skin->getAnimationData(),
			$capeId,
			$skin->getFullSkinId(),
			$skin->getArmSize(),
			$skin->getSkinColor(),
			$skin->getPersonaPieces(),
			$skin->getPieceTintColors(),
			$skin->isVerified(),
			$skin->isPremium(),
			$skin->isPersona(),
			$skin->isPersonaCapeOnClassic(),
			$skin->isPrimaryUser(),
			$skin->isOverride(),
			$skin->getTrustedSkinFlag(),
			$skin->getProfileHash()
		);
	}

	private function resolveResourcePatch(string $resourcePatch, int $armSize) : string
	{
		$decoded = json_decode($resourcePatch, true);
		$name = is_array($decoded) && is_string($decoded["geometry"]["default"] ?? null) ? $decoded["geometry"]["default"] : "";

		if ($name === self::GEOMETRY_WIDE || $name === self::GEOMETRY_SLIM) {
			return $resourcePatch;
		}

		return json_encode(["geometry" => ["default" => $armSize === SkinData::ARM_SIZE_SLIM ? self::GEOMETRY_SLIM : self::GEOMETRY_WIDE]], JSON_THROW_ON_ERROR);
	}
}
