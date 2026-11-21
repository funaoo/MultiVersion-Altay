<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use Closure;
use lol\network\proto\v84\packets\outgoing\v84OutgoingContainerClosePacket;
use lol\network\proto\v84\packets\v84ContainerOpenPacket;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;

final class v84WindowCloser
{

	public const ACKNOWLEDGE_DELAY_TICKS = 1;

	public const DISMISS_DELAY_TICKS = 1;

	public function __construct(private readonly Closure $later, private readonly v84DeferredWindows $deferred)
	{
	}

	public function close(NetworkSession $session, ContainerClosePacket $packet) : ?v84OutgoingContainerClosePacket
	{
		if ($packet->server && $this->deferred->cancel($session, $packet->windowId)) {
			$this->acknowledgeLater($session, $packet->windowId);

			return null;
		}

		$clientId = v84WindowIds::toClient($packet->windowId);
		if (!$packet->server || $clientId === null) {
			return null;
		}

		$this->acknowledgeLater($session, $packet->windowId);

		return v84WindowType::fromNetwork($packet->windowType) === null ? null : v84OutgoingContainerClosePacket::fromParts($clientId);
	}

	public function dismiss(NetworkSession $session, ContainerOpenPacket $packet) : void
	{
		if ($packet->actorUniqueId !== v84ContainerOpenPacket::NO_ENTITY) {
			return;
		}

		($this->later)(static function () use ($session, $packet) : void {
			$player = $session->getPlayer();
			if ($player === null || !$player->isConnected() || $player->getCurrentWindow() === null) {
				return;
			}

			if ($session->getInvManager()?->getCurrentWindowId() === $packet->windowId) {
				$player->removeCurrentWindow();
			}
		}, self::DISMISS_DELAY_TICKS);
	}

	private function acknowledgeLater(NetworkSession $session, int $networkId) : void
	{
		($this->later)(static function () use ($session, $networkId) : void {
			$session->getInvManager()?->onClientRemoveWindow($networkId);
		}, self::ACKNOWLEDGE_DELAY_TICKS);
	}
}
