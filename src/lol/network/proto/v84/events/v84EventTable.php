<?php

declare(strict_types=1);

namespace lol\network\proto\v84\events;

use pocketmine\network\mcpe\protocol\types\ActorEvent;

final readonly class v84EventTable
{

	private array $events;

	public function __construct()
	{
		$this->events = [
			ActorEvent::HURT_ANIMATION => v84EntityEventId::HURT,
			ActorEvent::DEATH_ANIMATION => v84EntityEventId::DEATH,
			ActorEvent::TAME_FAIL => v84EntityEventId::TAME_FAIL,
			ActorEvent::TAME_SUCCESS => v84EntityEventId::TAME_SUCCESS,
			ActorEvent::SHAKE_WET => v84EntityEventId::SHAKE_WET,
			ActorEvent::USE_ITEM => v84EntityEventId::USE_ITEM,
			ActorEvent::EATING_ITEM => v84EntityEventId::USE_ITEM,
			ActorEvent::EAT_GRASS_ANIMATION => v84EntityEventId::EAT_GRASS,
			ActorEvent::FISH_HOOK_BUBBLE => v84EntityEventId::FISH_HOOK_BUBBLE,
			ActorEvent::FISH_HOOK_POSITION => v84EntityEventId::FISH_HOOK_POSITION,
			ActorEvent::FISH_HOOK_HOOK => v84EntityEventId::FISH_HOOK_HOOK,
			ActorEvent::FISH_HOOK_TEASE => v84EntityEventId::FISH_HOOK_TEASE,
			ActorEvent::SQUID_INK_CLOUD => v84EntityEventId::SQUID_INK_CLOUD,
			ActorEvent::PLAY_AMBIENT_SOUND => v84EntityEventId::AMBIENT_SOUND,
			ActorEvent::RESPAWN => v84EntityEventId::RESPAWN,
		];
	}

	public function lookup(int $modernEvent) : ?int
	{
		return $this->events[$modernEvent] ?? null;
	}
}
