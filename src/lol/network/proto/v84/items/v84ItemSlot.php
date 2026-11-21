<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use function strlen;

final class v84ItemSlot
{

	public const AIR = "\x00\x00";

	private const AIR_ID = 0;

	private const DAMAGE_MASK = 0xffff;

	private const ID_SIGNED_MAX = 0x7fff;

	private function __construct()
	{
	}

	public static function encode(int $id, int $count, int $damage, string $compoundTag) : string
	{
		if ($id === self::AIR_ID) {
			return self::AIR;
		}

		$out = new ByteBufferWriter();
		BE::writeUnsignedShort($out, $id);
		Byte::writeUnsigned($out, $count);
		BE::writeUnsignedShort($out, $damage & self::DAMAGE_MASK);
		LE::writeUnsignedShort($out, strlen($compoundTag));
		$out->writeByteArray($compoundTag);
		return $out->getData();
	}

	public static function decode(ByteBufferReader $in) : v84ItemSlotData
	{
		$id = BE::readUnsignedShort($in);
		if ($id === self::AIR_ID || $id > self::ID_SIGNED_MAX) {
			return v84ItemSlotData::air();
		}

		$count = Byte::readUnsigned($in);
		$damage = BE::readUnsignedShort($in);
		$tagLength = LE::readUnsignedShort($in);

		return new v84ItemSlotData($id, $count, $damage, $in->readByteArray($tagLength));
	}

	public static function read(ByteBufferReader $in) : string
	{
		$out = new ByteBufferWriter();
		$id = BE::readUnsignedShort($in);
		BE::writeUnsignedShort($out, $id);
		if ($id === self::AIR_ID) {
			return $out->getData();
		}

		Byte::writeUnsigned($out, Byte::readUnsigned($in));
		BE::writeUnsignedShort($out, BE::readUnsignedShort($in));
		$tagLength = LE::readUnsignedShort($in);
		LE::writeUnsignedShort($out, $tagLength);
		$out->writeByteArray($in->readByteArray($tagLength));
		return $out->getData();
	}
}
