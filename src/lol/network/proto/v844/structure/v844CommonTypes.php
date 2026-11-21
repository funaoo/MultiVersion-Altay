<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\network\assemble\CommandOriginData;
use LogicException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\IntGameRule;
use lol\network\proto\v844\packets\types\recipe\v844IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\MolangItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\NameItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;
use function count;
use function max;
use function min;

final class v844CommonTypes
{

	private function __construct()
	{

	}

	static public function getCommandOriginData(ByteBufferReader $in) : CommandOriginData {
		$result = new CommandOriginData();
		$result->type = VarInt::readUnsignedInt($in);
		$result->uuid = CommonTypes::getUUID($in);
		$result->requestId = CommonTypes::getString($in);
		if ($result->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $result->type === CommandOriginData::ORIGIN_TEST) {
			$result->playerActorUniqueId = VarInt::readSignedLong($in);
		}
		return $result;
	}

	static public function putCommandOriginData(ByteBufferWriter $out, CommandOriginData $data) : void
	{
		VarInt::writeUnsignedInt($out, $data->type);
		CommonTypes::putUUID($out, $data->uuid);
		CommonTypes::putString($out, $data->requestId);

		if ($data->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $data->type === CommandOriginData::ORIGIN_TEST) {
			VarInt::writeSignedLong($out, $data->playerActorUniqueId);
		}
	}

	static public function putEntityMetadata(ByteBufferWriter $out, array $metadata) : void
	{
		VarInt::writeUnsignedInt($out, count($metadata));
		foreach ($metadata as $key => $d) {
			VarInt::writeUnsignedInt($out, $key);
			VarInt::writeUnsignedInt($out, $d->getTypeId());
			$d->write($out);
		}
	}

	static public function getEntityMetadata(ByteBufferReader $in) : array
	{
		$count = VarInt::readUnsignedInt($in);
		$data = [];
		for ($i = 0; $i < $count; ++$i) {
			$key = VarInt::readUnsignedInt($in);
			$type = VarInt::readUnsignedInt($in);

			$data[$key] = self::readMetadataProperty($in, $type);
		}

		return $data;
	}

	static private function readMetadataProperty(ByteBufferReader $in, int $type) : MetadataProperty
	{
		return match ($type) {
			ByteMetadataProperty::ID => ByteMetadataProperty::read($in),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($in),
			IntMetadataProperty::ID => IntMetadataProperty::read($in),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($in),
			StringMetadataProperty::ID => StringMetadataProperty::read($in),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($in),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($in),
			LongMetadataProperty::ID => LongMetadataProperty::read($in),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($in),
			default => throw new PacketDecodeException("Unknown entity metadata type " . $type),
		};
	}

	static public function putGameRules(ByteBufferWriter $out, array $rules, bool $isStartGame) : void
	{
		VarInt::writeUnsignedInt($out, count($rules));
		foreach ($rules as $name => $rule) {
			CommonTypes::putString($out, (string) $name);
			CommonTypes::putBool($out, $rule->isPlayerModifiable());
			VarInt::writeUnsignedInt($out, $rule->getTypeId());
			if ($isStartGame && $rule instanceof IntGameRule) {
				VarInt::writeUnsignedInt($out, $rule->getValue());
			} else {
				$rule->encode($out, $isStartGame);
			}
		}
	}

	static public function getGameRules(ByteBufferReader $in, bool $isStartGame) : array
	{
		$count = VarInt::readUnsignedInt($in);
		$rules = [];
		for ($i = 0; $i < $count; ++$i) {
			$name = CommonTypes::getString($in);
			$isPlayerModifiable = CommonTypes::getBool($in);
			$type = VarInt::readUnsignedInt($in);
			if ($isStartGame && $type === IntGameRule::ID) {
				$rules[$name] = new IntGameRule(VarInt::readUnsignedInt($in), $isPlayerModifiable);
				continue;
			}
			$rules[$name] = match ($type) {
				BoolGameRule::ID => BoolGameRule::decode($in, $isPlayerModifiable),
				IntGameRule::ID => IntGameRule::decode($in, $isPlayerModifiable, $isStartGame),
				FloatGameRule::ID => FloatGameRule::decode($in, $isPlayerModifiable),
				default => throw new PacketDecodeException("Unknown gamerule type $type"),
			};
		}

		return $rules;
	}

	public const ITEM_DESCRIPTOR_EMPTY = 0;
	public const ITEM_DESCRIPTOR_INT_ID_META = 1;
	public const ITEM_DESCRIPTOR_MOLANG = 2;
	public const ITEM_DESCRIPTOR_TAG = 3;
	public const ITEM_DESCRIPTOR_STRING_ID_META = 4;
	public const ITEM_DESCRIPTOR_COMPLEX_ALIAS = 5;

	static public function getRecipeIngredient(ByteBufferReader $in) : RecipeIngredient
	{
		$descriptorType = Byte::readUnsigned($in);

		switch ($descriptorType) {
			case self::ITEM_DESCRIPTOR_EMPTY:
				$descriptor = null;
				break;
			case self::ITEM_DESCRIPTOR_INT_ID_META:
				$id = LE::readSignedShort($in);
				if ($id !== 0) {
					LE::readSignedShort($in);
				}
				$descriptor = null;
				v844Debug::logOnce(
					"recipe:descriptor:intidmeta",
					"CraftingData",
					"received an int-id-meta recipe ingredient descriptor: no equivalent class exists in the vendored types, treating it as empty"
				);
				break;
			case self::ITEM_DESCRIPTOR_MOLANG:
				$expression = CommonTypes::getString($in);
				$version = Byte::readUnsigned($in);
				$descriptor = new MolangItemDescriptor($expression, $version);
				break;
			case self::ITEM_DESCRIPTOR_TAG:
				$descriptor = new TagItemDescriptor(CommonTypes::getString($in));
				break;
			case self::ITEM_DESCRIPTOR_STRING_ID_META:
				$name = CommonTypes::getString($in);
				$meta = LE::readUnsignedShort($in);
				$descriptor = new NameItemDescriptor($name, $meta);
				break;
			case self::ITEM_DESCRIPTOR_COMPLEX_ALIAS:
				CommonTypes::getString($in);
				$descriptor = null;
				v844Debug::logOnce(
					"recipe:descriptor:complexalias",
					"CraftingData",
					"received a complex-alias recipe ingredient descriptor: no equivalent class exists in the vendored types, treating it as empty"
				);
				break;
			default:
				throw new PacketDecodeException("Unknown recipe ingredient descriptor type $descriptorType");
		}

		$count = VarInt::readSignedInt($in);

		return new RecipeIngredient($descriptor, $count);
	}

	static public function putRecipeIngredient(ByteBufferWriter $out, RecipeIngredient $ingredient) : void
	{
		$descriptor = $ingredient->getDescriptor();

		if ($descriptor === null) {
			Byte::writeUnsigned($out, self::ITEM_DESCRIPTOR_EMPTY);
		} elseif ($descriptor instanceof v844IntIdMetaItemDescriptor) {
			Byte::writeUnsigned($out, self::ITEM_DESCRIPTOR_INT_ID_META);
			LE::writeSignedShort($out, $descriptor->getId());
			if ($descriptor->getId() !== 0) {
				LE::writeSignedShort($out, $descriptor->getMeta());
			}
		} elseif ($descriptor instanceof NameItemDescriptor) {
			Byte::writeUnsigned($out, self::ITEM_DESCRIPTOR_STRING_ID_META);
			CommonTypes::putString($out, $descriptor->getName());
			LE::writeUnsignedShort($out, max(0, min(0xffff, $descriptor->getAuxValue())));
		} elseif ($descriptor instanceof MolangItemDescriptor) {
			Byte::writeUnsigned($out, self::ITEM_DESCRIPTOR_MOLANG);
			CommonTypes::putString($out, $descriptor->getMolangExpression());
			Byte::writeUnsigned($out, max(0, min(0xff, $descriptor->getMolangVersion())));
		} elseif ($descriptor instanceof TagItemDescriptor) {
			Byte::writeUnsigned($out, self::ITEM_DESCRIPTOR_TAG);
			CommonTypes::putString($out, $descriptor->getTag());
		} else {
			throw new LogicException("Unknown item descriptor type " . $descriptor::class);
		}

		VarInt::writeSignedInt($out, $ingredient->getCount());
	}
}
