<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;
use function is_float;
use function is_int;
use function is_string;

final class v84TileFields
{

	private function __construct()
	{
	}

	public static function header(string $legacyId, CompoundTag $modern) : CompoundTag
	{
		return CompoundTag::create()
			->setString(v84TileTag::ID, $legacyId)
			->setInt(v84TileTag::X, self::intOf($modern, v84TileTag::X, 0))
			->setInt(v84TileTag::Y, self::intOf($modern, v84TileTag::Y, 0))
			->setInt(v84TileTag::Z, self::intOf($modern, v84TileTag::Z, 0));
	}

	public static function copyCustomName(CompoundTag $target, CompoundTag $modern) : CompoundTag
	{
		$name = self::stringOf($modern, v84TileTag::CUSTOM_NAME, '');
		if ($name !== '') {
			$target->setString(v84TileTag::CUSTOM_NAME, $name);
		}

		return $target;
	}

	public static function intOf(CompoundTag $compound, string $name, int $default) : int
	{
		$value = $compound->getTag($name)?->getValue();

		return is_int($value) ? $value : $default;
	}

	public static function floatOf(CompoundTag $compound, string $name, float $default) : float
	{
		$value = $compound->getTag($name)?->getValue();
		if (is_int($value)) {
			return (float) $value;
		}

		return is_float($value) ? $value : $default;
	}

	public static function stringOf(CompoundTag $compound, string $name, string $default) : string
	{
		$value = $compound->getTag($name)?->getValue();

		return is_string($value) ? $value : $default;
	}
}
