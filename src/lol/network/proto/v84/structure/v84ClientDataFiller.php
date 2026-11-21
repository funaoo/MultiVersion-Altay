<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use lol\Loader;
use function base64_decode;
use function base64_encode;
use function json_encode;
use function strlen;
use const JSON_THROW_ON_ERROR;

final class v84ClientDataFiller
{

	private const SKIN_BYTES_PER_PIXEL = 4;

	private const SKIN_WIDTH = 64;

	private const SKIN_HEIGHTS = [32, 64];

	private const DEFAULT_GEOMETRY_NAME = "geometry.humanoid.custom";

	private const DEFAULT_LANGUAGE = "en_US";

	private const DEFAULT_SKIN_COLOR = "#0";

	private const TOUCH_INPUT_MODE = 2;

	private function __construct()
	{
	}

	public static function fill(array &$clientData) : void
	{
		$clientData += self::defaults();

		[$width, $height] = self::resolveSkinDimensions((string) ($clientData["SkinData"] ?? ""));
		$clientData["SkinImageWidth"] = $width;
		$clientData["SkinImageHeight"] = $height;
	}

	private static function defaults() : array
	{
		return [
			"AnimatedImageData" => [],
			"ArmSize" => "",
			"CapeData" => "",
			"CapeId" => "",
			"CapeImageHeight" => 0,
			"CapeImageWidth" => 0,
			"CapeOnClassicSkin" => false,
			"CurrentInputMode" => self::TOUCH_INPUT_MODE,
			"DefaultInputMode" => self::TOUCH_INPUT_MODE,
			"DeviceId" => "",
			"DeviceModel" => "",
			"DeviceOS" => 0,
			"GameVersion" => v84ProtocolInfo::MINECRAFT_VERSION_NETWORK,
			"GuiScale" => 0,
			"LanguageCode" => self::DEFAULT_LANGUAGE,
			"OverrideSkin" => false,
			"PersonaPieces" => [],
			"PersonaSkin" => false,
			"PieceTintColors" => [],
			"PremiumSkin" => false,
			"SelfSignedId" => "",
			"SkinAnimationData" => "",
			"SkinColor" => self::DEFAULT_SKIN_COLOR,
			"SkinGeometryData" => base64_encode(Loader::getDefaultSkinFormat()),
			"SkinGeometryDataEngineVersion" => "",
			"SkinResourcePatch" => base64_encode(json_encode(["geometry" => ["default" => self::DEFAULT_GEOMETRY_NAME]], JSON_THROW_ON_ERROR)),
			"ThirdPartyName" => "",
			"ThirdPartyNameOnly" => false,
			"UIProfile" => 0,
			"IsEditorMode" => false,
			"PlayFabId" => "",
			"TrustedSkin" => true,
			"CompatibleWithClientSideChunkGen" => false,
		];
	}

	private static function resolveSkinDimensions(string $skinData) : array
	{
		$raw = base64_decode($skinData, true);
		if ($raw === false) {
			return [0, 0];
		}

		foreach (self::SKIN_HEIGHTS as $height) {
			if (strlen($raw) === self::SKIN_WIDTH * $height * self::SKIN_BYTES_PER_PIXEL) {
				return [self::SKIN_WIDTH, $height];
			}
		}

		return [0, 0];
	}
}
