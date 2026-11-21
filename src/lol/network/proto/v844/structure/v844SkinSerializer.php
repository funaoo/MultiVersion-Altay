<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use Ramsey\Uuid\Uuid;
use function array_search;
use function count;
use function dechex;
use function hexdec;
use function str_pad;
use function strlen;
use function strspn;
use function substr;
use const STR_PAD_LEFT;

final class v844SkinSerializer
{

	private const ARM_SIZES = [
		SkinData::ARM_SIZE_SLIM => "slim",
		SkinData::ARM_SIZE_WIDE => "wide",
	];

	private const PIECE_TYPES = [
		PersonaSkinPiece::PIECE_TYPE_UNKNOWN => "persona_unknown",
		PersonaSkinPiece::PIECE_TYPE_SKELETON => "persona_skeleton",
		PersonaSkinPiece::PIECE_TYPE_BODY => "persona_body",
		PersonaSkinPiece::PIECE_TYPE_SKIN => "persona_skin",
		PersonaSkinPiece::PIECE_TYPE_BOTTOM => "persona_bottom",
		PersonaSkinPiece::PIECE_TYPE_FEET => "persona_feet",
		PersonaSkinPiece::PIECE_TYPE_DRESS => "persona_dress",
		PersonaSkinPiece::PIECE_TYPE_TOP => "persona_top",
		PersonaSkinPiece::PIECE_TYPE_HIGH_PANTS => "persona_high_pants",
		PersonaSkinPiece::PIECE_TYPE_HANDS => "persona_hand",
		PersonaSkinPiece::PIECE_TYPE_OUTERWEAR => "persona_outerwear",
		PersonaSkinPiece::PIECE_TYPE_FACIAL_HAIR => "persona_facial_hair",
		PersonaSkinPiece::PIECE_TYPE_MOUTH => "persona_mouth",
		PersonaSkinPiece::PIECE_TYPE_EYES => "persona_eyes",
		PersonaSkinPiece::PIECE_TYPE_HAIR => "persona_hair",
		PersonaSkinPiece::PIECE_TYPE_HOOD => "persona_hood",
		PersonaSkinPiece::PIECE_TYPE_BACK => "persona_back",
		PersonaSkinPiece::PIECE_TYPE_FACE_ACCESSORY => "persona_face_accessory",
		PersonaSkinPiece::PIECE_TYPE_HEAD => "persona_head",
		PersonaSkinPiece::PIECE_TYPE_LEGS => "persona_legs",
		PersonaSkinPiece::PIECE_TYPE_LEFT_LEG => "persona_left_leg",
		PersonaSkinPiece::PIECE_TYPE_RIGHT_LEG => "persona_right_leg",
		PersonaSkinPiece::PIECE_TYPE_ARMS => "persona_arms",
		PersonaSkinPiece::PIECE_TYPE_LEFT_ARM => "persona_left_arm",
		PersonaSkinPiece::PIECE_TYPE_RIGHT_ARM => "persona_right_arm",
		PersonaSkinPiece::PIECE_TYPE_CAPES => "persona_capes",
		PersonaSkinPiece::PIECE_TYPE_CLASSIC_SKIN => "persona_classic_skin",
		PersonaSkinPiece::PIECE_TYPE_EMOTE => "persona_emote",
		PersonaSkinPiece::PIECE_TYPE_UNSUPPORTED => "persona_unsupported",
	];

	private function __construct()
	{

	}

	private static function writeColorString(ByteBufferWriter $out, int $color) : void
	{
		CommonTypes::putString($out, "#" . str_pad(dechex($color & 0xffffffff), 8, "0", STR_PAD_LEFT));
	}

	private static function readColorString(ByteBufferReader $in) : int
	{
		$raw = CommonTypes::getString($in);
		$hex = $raw !== "" && $raw[0] === "#" ? substr($raw, 1) : "";
		if ($hex === "" || strspn($hex, "0123456789abcdefABCDEF") !== strlen($hex)) {
			throw new PacketDecodeException("Invalid colour string \"$raw\"");
		}

		$value = ((int) hexdec($hex)) & 0xffffffff;
		return $value >= 0x80000000 ? $value - 0x100000000 : $value;
	}

	private static function armSizeToString(int $armSize) : string
	{
		return self::ARM_SIZES[$armSize] ?? self::ARM_SIZES[SkinData::ARM_SIZE_WIDE];
	}

	private static function armSizeFromString(string $armSize) : int
	{
		$id = array_search($armSize, self::ARM_SIZES, true);
		return $id === false ? SkinData::ARM_SIZE_WIDE : $id;
	}

	private static function pieceTypeToString(int $pieceType) : string
	{
		return self::PIECE_TYPES[$pieceType] ?? self::PIECE_TYPES[PersonaSkinPiece::PIECE_TYPE_UNKNOWN];
	}

	private static function pieceTypeFromString(string $pieceType) : int
	{
		$id = array_search($pieceType, self::PIECE_TYPES, true);
		return $id === false ? PersonaSkinPiece::PIECE_TYPE_UNKNOWN : $id;
	}

	private static function getSkinImage(ByteBufferReader $in) : SkinImage
	{
		$width = LE::readUnsignedInt($in);
		$height = LE::readUnsignedInt($in);
		$data = CommonTypes::getString($in);

		return new SkinImage($height, $width, $data);
	}

	private static function putSkinImage(ByteBufferWriter $out, SkinImage $image) : void
	{
		LE::writeUnsignedInt($out, $image->getWidth());
		LE::writeUnsignedInt($out, $image->getHeight());
		CommonTypes::putString($out, $image->getData());
	}

	public static function putSkin(ByteBufferWriter $out, SkinData $skin) : void
	{
		CommonTypes::putString($out, $skin->getSkinId());
		CommonTypes::putString($out, "");
		CommonTypes::putString($out, $skin->getResourcePatch());
		self::putSkinImage($out, $skin->getSkinImage());

		LE::writeUnsignedInt($out, count($skin->getAnimations()));
		foreach ($skin->getAnimations() as $animation) {
			self::putSkinImage($out, $animation->getImage());
			LE::writeUnsignedInt($out, $animation->getType());
			LE::writeFloat($out, $animation->getFrames());
			LE::writeUnsignedInt($out, $animation->getExpressionType());
		}

		self::putSkinImage($out, $skin->getCapeImage());
		CommonTypes::putString($out, $skin->getGeometryData());
		CommonTypes::putString($out, $skin->getGeometryDataEngineVersion());
		CommonTypes::putString($out, $skin->getAnimationData());
		CommonTypes::putString($out, $skin->getCapeId());
		CommonTypes::putString($out, $skin->getFullSkinId());

		CommonTypes::putString($out, self::armSizeToString($skin->getArmSize()));
		self::writeColorString($out, $skin->getSkinColor());

		LE::writeUnsignedInt($out, count($skin->getPersonaPieces()));
		foreach ($skin->getPersonaPieces() as $piece) {
			CommonTypes::putString($out, $piece->getPieceId());
			CommonTypes::putString($out, self::pieceTypeToString($piece->getPieceType()));
			CommonTypes::putString($out, $piece->getPackId()->toString());
			CommonTypes::putBool($out, $piece->isDefaultPiece());
			CommonTypes::putString($out, $piece->getProductId());
		}

		LE::writeUnsignedInt($out, count($skin->getPieceTintColors()));
		foreach ($skin->getPieceTintColors() as $tint) {
			CommonTypes::putString($out, $tint->getPieceType());
			LE::writeUnsignedInt($out, count($tint->getColors()));
			foreach ($tint->getColors() as $color) {
				self::writeColorString($out, $color);
			}
		}

		CommonTypes::putBool($out, $skin->isPremium());
		CommonTypes::putBool($out, $skin->isPersona());
		CommonTypes::putBool($out, $skin->isPersonaCapeOnClassic());
		CommonTypes::putBool($out, $skin->isPrimaryUser());
		CommonTypes::putBool($out, $skin->isOverride());
	}

	public static function getSkin(ByteBufferReader $in) : SkinData
	{
		$skinId = CommonTypes::getString($in);
		CommonTypes::getString($in);
		$resourcePatch = CommonTypes::getString($in);
		$skinImage = self::getSkinImage($in);

		$animations = [];
		for ($i = 0, $count = LE::readUnsignedInt($in); $i < $count; ++$i) {
			$image = self::getSkinImage($in);
			$type = LE::readUnsignedInt($in);
			$frames = LE::readFloat($in);
			$expressionType = LE::readUnsignedInt($in);
			$animations[] = new SkinAnimation($image, $type, $frames, $expressionType);
		}

		$capeImage = self::getSkinImage($in);
		$geometryData = CommonTypes::getString($in);
		$geometryDataEngineVersion = CommonTypes::getString($in);
		$animationData = CommonTypes::getString($in);
		$capeId = CommonTypes::getString($in);
		$fullSkinId = CommonTypes::getString($in);

		$armSize = self::armSizeFromString(CommonTypes::getString($in));
		$skinColor = self::readColorString($in);

		$personaPieces = [];
		for ($i = 0, $count = LE::readUnsignedInt($in); $i < $count; ++$i) {
			$pieceId = CommonTypes::getString($in);
			$pieceType = self::pieceTypeFromString(CommonTypes::getString($in));
			$packId = CommonTypes::getString($in);
			if (!Uuid::isValid($packId)) {
				throw new PacketDecodeException("Invalid Persona skin piece pack ID \"$packId\"");
			}
			$isDefaultPiece = CommonTypes::getBool($in);
			$productId = CommonTypes::getString($in);
			$personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, Uuid::fromString($packId), $isDefaultPiece, $productId);
		}

		$pieceTintColors = [];
		for ($i = 0, $count = LE::readUnsignedInt($in); $i < $count; ++$i) {
			$pieceType = CommonTypes::getString($in);
			$colorCount = LE::readUnsignedInt($in);
			if ($colorCount !== PersonaPieceTintColor::COLOR_COUNT) {
				throw new PacketDecodeException("Expected " . PersonaPieceTintColor::COLOR_COUNT . " tint colours, got $colorCount");
			}
			$colors = [];
			for ($j = 0; $j < $colorCount; ++$j) {
				$colors[] = self::readColorString($in);
			}
			$pieceTintColors[] = new PersonaPieceTintColor($pieceType, $colors);
		}

		$premium = CommonTypes::getBool($in);
		$persona = CommonTypes::getBool($in);
		$capeOnClassic = CommonTypes::getBool($in);
		$isPrimaryUser = CommonTypes::getBool($in);
		$override = CommonTypes::getBool($in);

		return new SkinData(
			$skinId,
			$resourcePatch,
			$skinImage,
			$animations,
			$capeImage,
			$geometryData,
			$geometryDataEngineVersion,
			$animationData,
			$capeId,
			$fullSkinId,
			$armSize,
			$skinColor,
			$personaPieces,
			$pieceTintColors,
			true,
			$premium,
			$persona,
			$capeOnClassic,
			$isPrimaryUser,
			$override,
			SkinData::TRUSTED_SKIN_FLAG_TRUE
		);
	}
}
