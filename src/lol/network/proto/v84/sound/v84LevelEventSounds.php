<?php

declare(strict_types=1);

namespace lol\network\proto\v84\sound;

use lol\network\proto\v84\packets\outgoing\v84OutgoingLevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\LevelEvent;

final class v84LevelEventSounds
{

	private const TABLE = [
		LevelEvent::SOUND_CLICK => v84SoundId::CLICK,
		LevelEvent::SOUND_CLICK_FAIL => v84SoundId::CLICK_FAIL,
		LevelEvent::SOUND_SHOOT => v84SoundId::SHOOT,
		LevelEvent::SOUND_DOOR => v84SoundId::DOOR,
		LevelEvent::SOUND_FIZZ => v84SoundId::FIZZ,
		LevelEvent::SOUND_IGNITE => v84SoundId::TNT,
		LevelEvent::SOUND_GHAST => v84SoundId::GHAST,
		LevelEvent::SOUND_GHAST_SHOOT => v84SoundId::GHAST_SHOOT,
		LevelEvent::SOUND_BLAZE_SHOOT => v84SoundId::BLAZE_SHOOT,
		LevelEvent::SOUND_DOOR_BUMP => v84SoundId::DOOR_BUMP,
		LevelEvent::SOUND_DOOR_CRASH => v84SoundId::DOOR_CRASH,
		LevelEvent::SOUND_ZOMBIE_INFECTED => v84SoundId::ZOMBIE_INFECT,
		LevelEvent::SOUND_ZOMBIE_CONVERT => v84SoundId::ZOMBIE_HEAL,
		LevelEvent::SOUND_ENDERMAN_TELEPORT => v84SoundId::ENDERMAN_TELEPORT,
		LevelEvent::SOUND_ANVIL_BREAK => v84SoundId::ANVIL_BREAK,
		LevelEvent::SOUND_ANVIL_USE => v84SoundId::ANVIL_USE,
		LevelEvent::SOUND_ANVIL_FALL => v84SoundId::ANVIL_FALL,
		LevelEvent::SOUND_POP => v84SoundId::DROP_ITEM,
		LevelEvent::SOUND_ITEMFRAME_ADD_ITEM => v84SoundId::ITEMFRAME_ADD_ITEM,
		LevelEvent::SOUND_ITEMFRAME_PLACE => v84SoundId::ITEMFRAME_PLACE,
		LevelEvent::SOUND_ITEMFRAME_REMOVE_ITEM => v84SoundId::ITEMFRAME_DROP_ITEM,
		LevelEvent::SOUND_ITEMFRAME_ROTATE_ITEM => v84SoundId::ITEMFRAME_ROTATE_ITEM,
		LevelEvent::SOUND_ORB => v84SoundId::EXP_PICKUP,
		LevelEvent::REDSTONE_TRIGGER => v84SoundId::BUTTON_CLICK,
		LevelEvent::CAULDRON_EXPLODE => v84SoundId::EXPLODE,
		LevelEvent::CAULDRON_FILL_POTION => v84SoundId::SPELL,
		LevelEvent::CAULDRON_FILL_WATER => v84SoundId::SPLASH,
		LevelEvent::CAULDRON_TAKE_WATER => v84SoundId::GRAY_SPLASH,
	];

	private function __construct()
	{
	}

	public static function translate(LevelEventPacket $packet) : ?v84OutgoingLevelEventPacket
	{
		$soundId = self::TABLE[$packet->eventId] ?? null;
		if ($soundId === null || $packet->position === null) {
			return null;
		}

		return v84OutgoingLevelEventPacket::fromParts($soundId, $packet->position->getX(), $packet->position->getY(), $packet->position->getZ(), $packet->eventData);
	}
}
