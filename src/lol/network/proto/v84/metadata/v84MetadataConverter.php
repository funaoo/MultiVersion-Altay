<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use function array_replace;
use function ksort;

final readonly class v84MetadataConverter
{

	private const ENABLED = 1;

	private const DISABLED = 0;

	public function __construct(private v84MetadataTable $table)
	{
	}

	public function snapshot(array $properties, string $fallbackNameTag) : array
	{
		return $this->overlay($this->table->defaults, $properties, $fallbackNameTag);
	}

	public function humanSnapshot(array $properties, string $fallbackNameTag) : array
	{
		return $this->overlay($this->table->humanDefaults, $properties, $fallbackNameTag);
	}

	public function changes(array $properties) : array
	{
		$entries = $this->convert($properties);
		ksort($entries);
		return $entries;
	}

	private function overlay(array $defaults, array $properties, string $fallbackNameTag) : array
	{
		$converted = $this->convert($properties);
		if (!isset($converted[v84MetadataKey::NAME_TAG])) {
			$converted[v84MetadataKey::NAME_TAG] = v84MetadataEntry::string($fallbackNameTag);
		}
		return array_replace($defaults, $converted);
	}

	private function convert(array $properties) : array
	{
		$entries = [];
		foreach ($this->table->rules as $modernKey => $rule) {
			$property = $properties[$modernKey] ?? null;
			$entry = $property instanceof MetadataProperty ? self::direct($rule, $property) : null;
			if ($entry !== null) {
				$entries[$rule->key] = $entry;
			}
		}

		$flags = $properties[EntityMetadataProperties::FLAGS] ?? null;
		if ($flags instanceof LongMetadataProperty) {
			$entries = array_replace($entries, $this->flagEntries($flags->getValue()));
		}

		$playerFlags = $properties[EntityMetadataProperties::PLAYER_FLAGS] ?? null;
		if ($playerFlags instanceof ByteMetadataProperty) {
			$entries[v84MetadataKey::PLAYER_FLAGS] = v84MetadataEntry::byte(self::remap($playerFlags->getValue(), $this->table->playerFlagBits));
		}

		return $entries;
	}

	private function flagEntries(int $flags) : array
	{
		$entries = [
			v84MetadataKey::FLAGS => v84MetadataEntry::byte(self::remap($flags, $this->table->flagBits)),
			v84MetadataKey::SHOW_NAME_TAG => v84MetadataEntry::byte(self::nameTagShown($flags)),
		];
		foreach ($this->table->flagKeys as $bit => $key) {
			$entries[$key] = v84MetadataEntry::byte(self::toggle(self::isSet($flags, $bit)));
		}

		return $entries;
	}

	private static function nameTagShown(int $flags) : int
	{
		$shown = self::isSet($flags, EntityMetadataFlags::CAN_SHOW_NAMETAG) && !self::isSet($flags, EntityMetadataFlags::INVISIBLE);
		return self::toggle($shown);
	}

	private static function direct(v84MetadataRule $rule, MetadataProperty $property) : ?v84MetadataEntry
	{
		if ($property instanceof StringMetadataProperty) {
			return $rule->type === v84MetadataType::STRING ? v84MetadataEntry::string($property->getValue()) : null;
		}

		if ($property instanceof BlockPosMetadataProperty) {
			$position = $property->getValue();
			return $rule->type === v84MetadataType::POSITION ? v84MetadataEntry::position($position->getX(), $position->getY(), $position->getZ()) : null;
		}

		$number = self::integerOf($property);
		if ($number === null) {
			return null;
		}

		$masked = $number & $rule->mask;
		return match ($rule->type) {
			v84MetadataType::BYTE => v84MetadataEntry::byte($masked),
			v84MetadataType::SHORT => v84MetadataEntry::short($masked),
			v84MetadataType::INT => v84MetadataEntry::int($masked),
			v84MetadataType::LONG => v84MetadataEntry::long($masked),
			default => null,
		};
	}

	private static function integerOf(MetadataProperty $property) : ?int
	{
		return match (true) {
			$property instanceof ByteMetadataProperty,
			$property instanceof ShortMetadataProperty,
			$property instanceof IntMetadataProperty,
			$property instanceof LongMetadataProperty => $property->getValue(),
			default => null,
		};
	}

	private static function remap(int $source, array $bits) : int
	{
		$result = 0;
		foreach ($bits as $from => $to) {
			if (self::isSet($source, $from)) {
				$result |= 1 << $to;
			}
		}

		return $result;
	}

	private static function isSet(int $flags, int $bit) : bool
	{
		return (($flags >> $bit) & 1) === 1;
	}

	private static function toggle(bool $value) : int
	{
		return $value ? self::ENABLED : self::DISABLED;
	}
}
