<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\inventory\stackrequest;

use lol\network\proto\v844\structure\v844Debug;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\MolangItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\NameItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;

final class v844StackRequestIngredient
{

	private const DESCRIPTOR_EMPTY = 0;
	private const DESCRIPTOR_INT_ID_META = 1;
	private const DESCRIPTOR_MOLANG = 2;
	private const DESCRIPTOR_TAG = 3;
	private const DESCRIPTOR_STRING_ID_META = 4;
	private const DESCRIPTOR_COMPLEX_ALIAS = 5;

	private function __construct()
	{

	}

	public static function read(ByteBufferReader $in) : RecipeIngredient
	{
		$descriptorTypeId = Byte::readUnsigned($in);

		switch ($descriptorTypeId) {
			case self::DESCRIPTOR_EMPTY:
				$descriptor = null;
				break;
			case self::DESCRIPTOR_INT_ID_META:
				$id = LE::readSignedShort($in);
				if ($id !== 0) {
					LE::readSignedShort($in);
				}
				$descriptor = null;
				v844Debug::logOnce(
					"stackrequest:descriptor:intidmeta",
					"ItemStackRequest",
					"received an int-id-meta ingredient descriptor: removed in 1.26.40, no equivalent class exists in the vendored types, treating it as empty"
				);
				break;
			case self::DESCRIPTOR_MOLANG:
				$expression = CommonTypes::getString($in);
				$version = Byte::readUnsigned($in);
				$descriptor = new MolangItemDescriptor($expression, $version);
				break;
			case self::DESCRIPTOR_TAG:
				$descriptor = new TagItemDescriptor(CommonTypes::getString($in));
				break;
			case self::DESCRIPTOR_STRING_ID_META:
				$name = CommonTypes::getString($in);
				$meta = LE::readUnsignedShort($in);
				$descriptor = new NameItemDescriptor($name, $meta);
				break;
			case self::DESCRIPTOR_COMPLEX_ALIAS:
				CommonTypes::getString($in);
				$descriptor = null;
				v844Debug::logOnce(
					"stackrequest:descriptor:complexalias",
					"ItemStackRequest",
					"received a complex-alias ingredient descriptor: removed in 1.26.40, no equivalent class exists in the vendored types, treating it as empty"
				);
				break;
			default:
				throw new PacketDecodeException("Unknown item descriptor type $descriptorTypeId");
		}

		$count = VarInt::readSignedInt($in);

		return new RecipeIngredient($descriptor, $count);
	}

	public static function write(ByteBufferWriter $out, RecipeIngredient $ingredient) : void
	{
		$descriptor = $ingredient->getDescriptor();

		if ($descriptor === null) {
			Byte::writeUnsigned($out, self::DESCRIPTOR_EMPTY);
		} elseif ($descriptor instanceof NameItemDescriptor) {
			Byte::writeUnsigned($out, self::DESCRIPTOR_STRING_ID_META);
			CommonTypes::putString($out, $descriptor->getName());
			LE::writeUnsignedShort($out, $descriptor->getAuxValue());
		} elseif ($descriptor instanceof MolangItemDescriptor) {
			Byte::writeUnsigned($out, self::DESCRIPTOR_MOLANG);
			CommonTypes::putString($out, $descriptor->getMolangExpression());
			Byte::writeUnsigned($out, $descriptor->getMolangVersion());
		} elseif ($descriptor instanceof TagItemDescriptor) {
			Byte::writeUnsigned($out, self::DESCRIPTOR_TAG);
			CommonTypes::putString($out, $descriptor->getTag());
		} else {
			throw new \InvalidArgumentException("Unknown item descriptor type " . $descriptor::class);
		}

		VarInt::writeSignedInt($out, $ingredient->getCount());
	}
}
