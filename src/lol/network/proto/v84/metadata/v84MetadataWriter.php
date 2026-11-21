<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function strlen;

final class v84MetadataWriter
{

	private const TYPE_SHIFT = 5;

	private const KEY_MASK = 0x1f;

	private const TERMINATOR = 0x7f;

	private const SLOT_ID = 0;

	private const SLOT_COUNT = 1;

	private const SLOT_DAMAGE = 2;

	private const POSITION_X = 0;

	private const POSITION_Y = 1;

	private const POSITION_Z = 2;

	private function __construct()
	{
	}

	public static function encode(array $entries) : string
	{
		$out = new ByteBufferWriter();
		foreach ($entries as $key => $entry) {
			if (!$entry instanceof v84MetadataEntry) {
				continue;
			}
			Byte::writeUnsigned($out, ($entry->type << self::TYPE_SHIFT) | ($key & self::KEY_MASK));
			self::writeValue($out, $entry);
		}
		Byte::writeUnsigned($out, self::TERMINATOR);
		return $out->getData();
	}

	private static function writeValue(ByteBufferWriter $out, v84MetadataEntry $entry) : void
	{
		$value = $entry->value;
		match ($entry->type) {
			v84MetadataType::BYTE => Byte::writeUnsigned($out, self::integer($value)),
			v84MetadataType::SHORT => LE::writeUnsignedShort($out, self::integer($value)),
			v84MetadataType::INT => LE::writeSignedInt($out, self::integer($value)),
			v84MetadataType::FLOAT => LE::writeFloat($out, self::decimal($value)),
			v84MetadataType::STRING => self::writeString($out, self::text($value)),
			v84MetadataType::SLOT => self::writeSlot($out, $value),
			v84MetadataType::POSITION => self::writePosition($out, $value),
			v84MetadataType::LONG => LE::writeSignedLong($out, self::integer($value)),
		};
	}

	private static function writeString(ByteBufferWriter $out, string $value) : void
	{
		LE::writeUnsignedShort($out, strlen($value));
		$out->writeByteArray($value);
	}

	private static function writeSlot(ByteBufferWriter $out, mixed $parts) : void
	{
		$parts = is_array($parts) ? $parts : [];
		LE::writeUnsignedShort($out, self::integer($parts[self::SLOT_ID] ?? 0));
		Byte::writeUnsigned($out, self::integer($parts[self::SLOT_COUNT] ?? 0));
		LE::writeUnsignedShort($out, self::integer($parts[self::SLOT_DAMAGE] ?? 0));
	}

	private static function writePosition(ByteBufferWriter $out, mixed $parts) : void
	{
		$parts = is_array($parts) ? $parts : [];
		LE::writeSignedInt($out, self::integer($parts[self::POSITION_X] ?? 0));
		LE::writeSignedInt($out, self::integer($parts[self::POSITION_Y] ?? 0));
		LE::writeSignedInt($out, self::integer($parts[self::POSITION_Z] ?? 0));
	}

	private static function integer(mixed $value) : int
	{
		return is_int($value) ? $value : 0;
	}

	private static function decimal(mixed $value) : float
	{
		return is_float($value) ? $value : 0.0;
	}

	private static function text(mixed $value) : string
	{
		return is_string($value) ? $value : '';
	}
}
