<?php

declare(strict_types=1);

namespace lol\network\proto\v84\scoreboard;

use Closure;
use lol\network\NetworkSession;
use lol\network\proto\v84\packets\outgoing\v84OutgoingTextPacket;
use lol\network\proto\v84\text\v84TextTranslator;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\player\Player;
use WeakMap;

final class v84ScoreboardBridge
{

	private WeakMap $boards;

	public function __construct(
		private readonly Closure $clock
	)
	{
		$this->boards = new WeakMap();
	}

	public function handle(NetworkSession $session, ClientboundPacket $packet) : ?v84OutgoingTextPacket
	{
		if (!v84ScoreboardSettings::isEnabled()) {
			return null;
		}

		$board = $this->board($session);
		$changed = match (true) {
			$packet instanceof SetDisplayObjectivePacket => $board->display($packet),
			$packet instanceof SetScorePacket => $board->scores($packet, static fn(int $actorId) : ?string => self::nameOf($session, $actorId)),
			$packet instanceof RemoveObjectivePacket => $board->forget($packet->objectiveName),
			default => false
		};

		return $changed ? $this->frame($board) : null;
	}

	public function refresh(NetworkSession $session) : ?v84OutgoingTextPacket
	{
		$board = $this->boards[$session] ?? null;
		if ($board === null || !$board->isVisible() || ($this->clock)() - $board->sentAt() < v84ScoreboardSettings::refreshTicks()) {
			return null;
		}

		return $this->frame($board);
	}

	private function frame(v84SidebarState $board) : ?v84OutgoingTextPacket
	{
		$text = $board->render(v84ScoreboardSettings::maxLines(), v84ScoreboardSettings::showNumbers());
		if ($text === null && !$board->isVisible()) {
			return null;
		}

		$board->markSent(($this->clock)(), $text !== null);

		return v84TextTranslator::channel(v84ScoreboardSettings::channel(), $text ?? '');
	}

	private function board(NetworkSession $session) : v84SidebarState
	{
		$board = $this->boards[$session] ?? null;
		if ($board === null) {
			$board = new v84SidebarState();
			$this->boards[$session] = $board;
		}

		return $board;
	}

	private static function nameOf(NetworkSession $session, int $actorId) : ?string
	{
		$entity = $session->getPlayer()?->getWorld()->getEntity($actorId);

		return match (true) {
			$entity instanceof Player => $entity->getDisplayName(),
			$entity !== null => $entity->getNameTag(),
			default => null
		};
	}
}
