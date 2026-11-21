<?php

declare(strict_types=1);

namespace lol\network\proto\v84\effects;

use lol\network\NetworkSession;
use lol\network\proto\v84\metadata\v84MetadataEntry;
use lol\network\proto\v84\metadata\v84MetadataKey;
use lol\network\proto\v84\metadata\v84MetadataWriter;
use lol\network\proto\v84\packets\v84SetEntityDataPacket;
use lol\network\proto\v84\structure\v84ActorIds;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use WeakMap;
use function intdiv;

final class v84EffectAuras
{

	private const COLORS = [
		1 => [124, 175, 198],
		2 => [90, 108, 129],
		3 => [217, 192, 67],
		4 => [74, 66, 23],
		5 => [147, 36, 35],
		6 => [248, 36, 35],
		7 => [67, 10, 9],
		8 => [34, 255, 76],
		9 => [85, 29, 74],
		10 => [205, 92, 171],
		11 => [153, 69, 58],
		12 => [228, 154, 58],
		13 => [46, 82, 153],
		14 => [127, 131, 146],
		15 => [191, 192, 192],
		16 => [0, 0, 139],
		17 => [46, 139, 87],
		18 => [72, 77, 72],
		19 => [78, 147, 49],
		20 => [53, 42, 39],
		21 => [248, 125, 35],
		22 => [36, 107, 251],
		23 => [255, 0, 255],
	];

	private const CHANNEL_MASK = 0xff;

	private const RED_SHIFT = 16;

	private const GREEN_SHIFT = 8;

	private WeakMap $sessions;

	public function __construct(
		private readonly v84EffectTable $effects
	)
	{
		$this->sessions = new WeakMap();
	}

	public function update(NetworkSession $session, MobEffectPacket $packet) : ?v84SetEntityDataPacket
	{
		$effectId = $this->effects->lookup($packet->effectId);
		$color = $effectId === null ? null : (self::COLORS[$effectId] ?? null);
		if ($effectId === null || $color === null) {
			return null;
		}

		$actorId = $packet->actorRuntimeId;
		$state = $this->sessions[$session] ?? [];
		$active = $state[$actorId] ?? [];

		if ($packet->eventId === MobEffectPacket::EVENT_REMOVE) {
			unset($active[$effectId]);
		} elseif ($packet->eventId === MobEffectPacket::EVENT_ADD || $packet->eventId === MobEffectPacket::EVENT_MODIFY) {
			$active[$effectId] = [$color, $packet->amplifier + 1, $packet->particles, $packet->ambient];
		} else {
			return null;
		}

		if ($active === []) {
			unset($state[$actorId]);
		} else {
			$state[$actorId] = $active;
		}
		$this->sessions[$session] = $state;

		[$rgb, $ambient] = self::blend($active);

		return v84SetEntityDataPacket::fromParts(
			v84ActorIds::resolve($session, $actorId),
			v84MetadataWriter::encode([
				v84MetadataKey::POTION_COLOR => v84MetadataEntry::int($rgb),
				v84MetadataKey::POTION_AMBIENT => v84MetadataEntry::byte($ambient ? 1 : 0),
			])
		);
	}

	private static function blend(array $active) : array
	{
		$red = 0;
		$green = 0;
		$blue = 0;
		$weight = 0;
		$ambient = true;

		foreach ($active as [$color, $factor, $visible, $isAmbient]) {
			if (!$visible) {
				continue;
			}

			$red += $color[0] * $factor;
			$green += $color[1] * $factor;
			$blue += $color[2] * $factor;
			$weight += $factor;
			if (!$isAmbient) {
				$ambient = false;
			}
		}

		if ($weight === 0) {
			return [0, false];
		}

		$rgb = ((intdiv($red, $weight) & self::CHANNEL_MASK) << self::RED_SHIFT)
			| ((intdiv($green, $weight) & self::CHANNEL_MASK) << self::GREEN_SHIFT)
			| (intdiv($blue, $weight) & self::CHANNEL_MASK);

		return [$rgb, $ambient];
	}
}
