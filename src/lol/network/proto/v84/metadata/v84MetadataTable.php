<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PlayerMetadataFlags;

final readonly class v84MetadataTable
{

	private const FULL_AIR = 300;

	private const NO_LEAD_HOLDER = -1;

	private const NAME_TAG_SHOWN = 1;

	private const POTION_COLOR_MASK = 0xffffff;

	public array $defaults;

	public array $humanDefaults;

	public array $rules;

	public array $flagBits;

	public array $playerFlagBits;

	public array $flagKeys;

	public function __construct()
	{
		$this->defaults = self::buildDefaults();
		$this->humanDefaults = $this->defaults + self::buildHumanExtras();
		$this->rules = self::buildRules();
		$this->flagBits = self::buildFlagBits();
		$this->playerFlagBits = self::buildPlayerFlagBits();
		$this->flagKeys = self::buildFlagKeys();
	}

	private static function buildDefaults() : array
	{
		return [
			v84MetadataKey::FLAGS => v84MetadataEntry::byte(0),
			v84MetadataKey::AIR => v84MetadataEntry::short(self::FULL_AIR),
			v84MetadataKey::NAME_TAG => v84MetadataEntry::string(''),
			v84MetadataKey::SHOW_NAME_TAG => v84MetadataEntry::byte(self::NAME_TAG_SHOWN),
			v84MetadataKey::SILENT => v84MetadataEntry::byte(0),
			v84MetadataKey::NO_AI => v84MetadataEntry::byte(0),
			v84MetadataKey::LEAD_HOLDER => v84MetadataEntry::long(self::NO_LEAD_HOLDER),
			v84MetadataKey::LEAD => v84MetadataEntry::byte(0),
		];
	}

	private static function buildHumanExtras() : array
	{
		return [
			v84MetadataKey::PLAYER_FLAGS => v84MetadataEntry::byte(0),
			v84MetadataKey::BED_POSITION => v84MetadataEntry::position(0, 0, 0),
		];
	}

	private static function buildRules() : array
	{
		return [
			EntityMetadataProperties::AIR => new v84MetadataRule(v84MetadataKey::AIR, v84MetadataType::SHORT),
			EntityMetadataProperties::NAMETAG => new v84MetadataRule(v84MetadataKey::NAME_TAG, v84MetadataType::STRING),
			EntityMetadataProperties::POTION_COLOR => new v84MetadataRule(v84MetadataKey::POTION_COLOR, v84MetadataType::INT, self::POTION_COLOR_MASK),
			EntityMetadataProperties::POTION_AMBIENT => new v84MetadataRule(v84MetadataKey::POTION_AMBIENT, v84MetadataType::BYTE),
			EntityMetadataProperties::LEAD_HOLDER_EID => new v84MetadataRule(v84MetadataKey::LEAD_HOLDER, v84MetadataType::LONG),
			EntityMetadataProperties::PLAYER_BED_POSITION => new v84MetadataRule(v84MetadataKey::BED_POSITION, v84MetadataType::POSITION),
		];
	}

	private static function buildFlagBits() : array
	{
		return [
			EntityMetadataFlags::ONFIRE => v84MetadataFlag::ON_FIRE,
			EntityMetadataFlags::SNEAKING => v84MetadataFlag::SNEAKING,
			EntityMetadataFlags::RIDING => v84MetadataFlag::RIDING,
			EntityMetadataFlags::SPRINTING => v84MetadataFlag::SPRINTING,
			EntityMetadataFlags::ACTION => v84MetadataFlag::ACTION,
			EntityMetadataFlags::INVISIBLE => v84MetadataFlag::INVISIBLE,
		];
	}

	private static function buildPlayerFlagBits() : array
	{
		return [
			PlayerMetadataFlags::SLEEP => v84MetadataFlag::PLAYER_SLEEP,
			PlayerMetadataFlags::DEAD => v84MetadataFlag::PLAYER_DEAD,
		];
	}

	private static function buildFlagKeys() : array
	{
		return [
			EntityMetadataFlags::NO_AI => v84MetadataKey::NO_AI,
			EntityMetadataFlags::SILENT => v84MetadataKey::SILENT,
			EntityMetadataFlags::LEASHED => v84MetadataKey::LEAD,
		];
	}
}
