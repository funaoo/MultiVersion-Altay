<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

final class v844ItemStackSerializer
{

	private function __construct()
	{

	}

	private static function getItemStackHeader(ByteBufferReader $in) : array
	{
		$id = VarInt::readSignedInt($in);
		if ($id === 0) {
			return [0, 0, 0];
		}

		$count = LE::readUnsignedShort($in);
		$meta = VarInt::readUnsignedInt($in);

		return [$id, $count, $meta];
	}

	private static function putItemStackHeader(ByteBufferWriter $out, ItemStack $itemStack) : bool
	{
		if ($itemStack->getId() === 0) {
			VarInt::writeSignedInt($out, 0);
			return false;
		}

		VarInt::writeSignedInt($out, $itemStack->getId());
		LE::writeUnsignedShort($out, $itemStack->getCount());
		VarInt::writeUnsignedInt($out, $itemStack->getMeta());

		return true;
	}

	private static function getItemStackFooter(ByteBufferReader $in, int $id, int $meta, int $count) : ItemStack
	{
		$blockRuntimeId = VarInt::readSignedInt($in);
		$rawExtraData = CommonTypes::getString($in);

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $rawExtraData);
	}

	private static function putItemStackFooter(ByteBufferWriter $out, ItemStack $itemStack) : void
	{
		VarInt::writeSignedInt($out, $itemStack->getBlockRuntimeId());
		CommonTypes::putString($out, $itemStack->getRawExtraData());
	}

	public static function getItemStackWithoutStackId(ByteBufferReader $in) : ItemStack
	{
		[$id, $count, $meta] = self::getItemStackHeader($in);

		return $id !== 0 ? self::getItemStackFooter($in, $id, $meta, $count) : ItemStack::null();
	}

	public static function putItemStackWithoutStackId(ByteBufferWriter $out, ItemStack $itemStack) : void
	{
		if (self::putItemStackHeader($out, $itemStack)) {
			self::putItemStackFooter($out, $itemStack);
		}
	}

	public static function getItemStackWrapper(ByteBufferReader $in) : ItemStackWrapper
	{
		[$id, $count, $meta] = self::getItemStackHeader($in);
		if ($id === 0) {
			return new ItemStackWrapper(0, ItemStack::null());
		}

		$hasNetId = CommonTypes::getBool($in);
		$stackId = $hasNetId ? CommonTypes::readServerItemStackId($in) : 0;

		return new ItemStackWrapper($stackId, self::getItemStackFooter($in, $id, $meta, $count));
	}

	public static function putItemStackWrapper(ByteBufferWriter $out, ItemStackWrapper $itemStackWrapper) : void
	{
		$itemStack = $itemStackWrapper->getItemStack();
		if (self::putItemStackHeader($out, $itemStack)) {
			CommonTypes::putBool($out, $hasNetId = $itemStackWrapper->getStackId() !== 0);
			if ($hasNetId) {
				CommonTypes::writeServerItemStackId($out, $itemStackWrapper->getStackId());
			}

			self::putItemStackFooter($out, $itemStack);
		}
	}

}
