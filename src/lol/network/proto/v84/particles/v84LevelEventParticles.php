<?php

declare(strict_types=1);

namespace lol\network\proto\v84\particles;

use Closure;
use lol\network\proto\v84\blocks\v84LegacyBlock;
use lol\network\proto\v84\packets\outgoing\v84OutgoingLevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;

final class v84LevelEventParticles
{

	private const TERRAIN_META_SHIFT = 8;

	private const DESTROY_META_SHIFT = 12;

	private const NO_BLOCK = -1;

	private const PARTICLES = [
		ParticleIds::BUBBLE => v84ParticleId::BUBBLE,
		ParticleIds::BUBBLE_MANUAL => v84ParticleId::BUBBLE,
		ParticleIds::CRITICAL => v84ParticleId::CRITICAL,
		ParticleIds::SMOKE => v84ParticleId::SMOKE,
		ParticleIds::EXPLODE => v84ParticleId::EXPLODE,
		ParticleIds::WHITE_SMOKE => v84ParticleId::WHITE_SMOKE,
		ParticleIds::FLAME => v84ParticleId::FLAME,
		ParticleIds::LAVA => v84ParticleId::LAVA,
		ParticleIds::LARGE_SMOKE => v84ParticleId::LARGE_SMOKE,
		ParticleIds::REDSTONE => v84ParticleId::REDSTONE,
		ParticleIds::SNOWBALL_POOF => v84ParticleId::SNOWBALL_POOF,
		ParticleIds::HUGE_EXPLODE => v84ParticleId::HUGE_EXPLODE,
		ParticleIds::MOB_FLAME => v84ParticleId::MOB_FLAME,
		ParticleIds::HEART => v84ParticleId::HEART,
		ParticleIds::TOWN_AURA => v84ParticleId::TOWN_AURA,
		ParticleIds::PORTAL => v84ParticleId::PORTAL,
		ParticleIds::WATER_SPLASH => v84ParticleId::WATER_SPLASH,
		ParticleIds::WATER_SPLASH_MANUAL => v84ParticleId::WATER_SPLASH,
		ParticleIds::WATER_WAKE => v84ParticleId::WATER_WAKE,
		ParticleIds::DRIP_WATER => v84ParticleId::DRIP_WATER,
		ParticleIds::DRIP_LAVA => v84ParticleId::DRIP_LAVA,
		ParticleIds::DUST => v84ParticleId::DUST,
		ParticleIds::MOB_SPELL => v84ParticleId::MOB_SPELL,
		ParticleIds::MOB_SPELL_AMBIENT => v84ParticleId::MOB_SPELL_AMBIENT,
		ParticleIds::MOB_SPELL_INSTANTANEOUS => v84ParticleId::MOB_SPELL_INSTANTANEOUS,
		ParticleIds::INK => v84ParticleId::INK,
		ParticleIds::SLIME => v84ParticleId::SLIME,
		ParticleIds::RAIN_SPLASH => v84ParticleId::RAIN_SPLASH,
		ParticleIds::VILLAGER_ANGRY => v84ParticleId::VILLAGER_ANGRY,
		ParticleIds::VILLAGER_HAPPY => v84ParticleId::VILLAGER_HAPPY,
		ParticleIds::ENCHANTMENT_TABLE => v84ParticleId::ENCHANTMENT_TABLE,
	];

	private function __construct()
	{
	}

	public static function translate(LevelEventPacket $packet, Closure $packedBlockFromRuntimeId) : ?v84OutgoingLevelEventPacket
	{
		if ($packet->position === null) {
			return null;
		}

		$particleId = $packet->eventId - v84ParticleEvent::ADD_PARTICLE_MASK;
		if ($particleId >= 0) {
			return self::particle($packet, $particleId, $packedBlockFromRuntimeId);
		}

		return match ($packet->eventId) {
			LevelEvent::PARTICLE_DESTROY => self::destroy($packet, $packedBlockFromRuntimeId),
			LevelEvent::PARTICLE_SHOOT => self::event($packet, v84ParticleEvent::SHOOT, $packet->eventData),
			LevelEvent::PARTICLE_SPLASH => self::event($packet, v84ParticleEvent::SPLASH, $packet->eventData),
			LevelEvent::PARTICLE_EYE_DESPAWN => self::event($packet, v84ParticleEvent::EYE_DESPAWN, $packet->eventData),
			LevelEvent::PARTICLE_SPAWN => self::event($packet, v84ParticleEvent::SPAWN, $packet->eventData),
			default => null
		};
	}

	private static function particle(LevelEventPacket $packet, int $particleId, Closure $packedBlockFromRuntimeId) : ?v84OutgoingLevelEventPacket
	{
		if ($particleId === ParticleIds::TERRAIN) {
			return self::terrain($packet, $packedBlockFromRuntimeId);
		}

		$mapped = self::PARTICLES[$particleId] ?? null;
		if ($mapped === null) {
			return null;
		}

		return self::event($packet, v84ParticleEvent::ADD_PARTICLE_MASK | $mapped, $packet->eventData);
	}

	private static function terrain(LevelEventPacket $packet, Closure $packedBlockFromRuntimeId) : ?v84OutgoingLevelEventPacket
	{
		$packed = self::packedBlock($packet, $packedBlockFromRuntimeId);
		if ($packed === null) {
			return null;
		}

		return self::event($packet, v84ParticleEvent::ADD_PARTICLE_MASK | v84ParticleId::TERRAIN, (v84LegacyBlock::meta($packed) << self::TERRAIN_META_SHIFT) | v84LegacyBlock::id($packed));
	}

	private static function destroy(LevelEventPacket $packet, Closure $packedBlockFromRuntimeId) : ?v84OutgoingLevelEventPacket
	{
		$packed = self::packedBlock($packet, $packedBlockFromRuntimeId);
		if ($packed === null) {
			return null;
		}

		return self::event($packet, v84ParticleEvent::DESTROY, v84LegacyBlock::id($packed) + (v84LegacyBlock::meta($packed) << self::DESTROY_META_SHIFT));
	}

	private static function packedBlock(LevelEventPacket $packet, Closure $packedBlockFromRuntimeId) : ?int
	{
		if ($packet->eventData === self::NO_BLOCK) {
			return null;
		}

		$packed = $packedBlockFromRuntimeId($packet->eventData);

		return v84LegacyBlock::id($packed) === v84LegacyBlock::AIR ? null : $packed;
	}

	private static function event(LevelEventPacket $packet, int $eventId, int $data) : v84OutgoingLevelEventPacket
	{
		return v84OutgoingLevelEventPacket::fromParts($eventId, $packet->position->getX(), $packet->position->getY(), $packet->position->getZ(), $data);
	}
}
