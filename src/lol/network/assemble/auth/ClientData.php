<?php

declare(strict_types=1);

namespace lol\network\assemble\auth;

use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientDataAnimationFrame;

final class ClientData
{
	public array $AnimatedImageData;

	public string $ArmSize;

	public string $CapeData;

	public string $CapeId;

	public int $CapeImageHeight;

	public int $CapeImageWidth;

	public bool $CapeOnClassicSkin;

	public int $ClientRandomId;

	public bool $CompatibleWithClientSideChunkGen;

	public int $CurrentInputMode;

	public int $DefaultInputMode;

	public string $DeviceId;

	public string $DeviceModel;

	public int $DeviceOS;

	public string $GameVersion;

	public int $GraphicsMode;

	public int $GuiScale;

	public bool $IsEditorMode;

	public string $LanguageCode;

	public int $MaxViewDistance;

	public int $MemoryTier;

	public bool $OverrideSkin;

	public array $PersonaPieces;

	public bool $PersonaSkin;

	public array $PieceTintColors;

	public string $PlatformOfflineId;

	public string $PlatformOnlineId;

	public int $PlatformType;

	public string $PlatformUserId = "";

	public string $PlayFabId;

	public bool $PremiumSkin = false;

	public string $SelfSignedId;

	public string $ServerAddress;

	public string $SkinAnimationData;

	public string $SkinColor;

	public string $SkinData;

	public string $SkinGeometryData;

	public string $SkinGeometryDataEngineVersion;

	public string $SkinId;

	public int $SkinImageHeight;

	public int $SkinImageWidth;

	public string $SkinResourcePatch;

	public string $ThirdPartyName;

	public bool $TrustedSkin;

	public int $UIProfile;
}
