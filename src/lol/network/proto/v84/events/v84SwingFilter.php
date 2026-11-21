<?php

declare(strict_types=1);

namespace lol\network\proto\v84\events;

use Closure;
use lol\network\NetworkSession;
use lol\network\proto\v84\packets\v84AnimatePacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use WeakMap;
use function count;

final class v84SwingFilter
{

	private const WINDOW_TICKS = 2;

	private const MAX_TRACKED_ACTORS = 256;

	private readonly WeakMap $sessions;

	public function __construct(private readonly Closure $clock)
	{
		$this->sessions = new WeakMap();
	}

	public function filter(NetworkSession $session, ?ClientboundPacket $packet) : ?ClientboundPacket
	{
		if (!$packet instanceof v84AnimatePacket || $packet->action !== v84AnimateAction::SWING_ARM) {
			return $packet;
		}

		return $this->accept($session, $packet->actorId) ? $packet : null;
	}

	private function accept(NetworkSession $session, int $actorId) : bool
	{
		$now = ($this->clock)();
		$swings = $this->sessions[$session] ?? [];
		$last = $swings[$actorId] ?? null;
		if ($last !== null && $now - $last < self::WINDOW_TICKS) {
			return false;
		}

		if (count($swings) >= self::MAX_TRACKED_ACTORS) {
			$swings = [];
		}

		$swings[$actorId] = $now;
		$this->sessions[$session] = $swings;
		return true;
	}
}
