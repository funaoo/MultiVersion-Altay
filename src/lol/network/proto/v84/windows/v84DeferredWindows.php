<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use Closure;
use lol\network\proto\v84\packets\v84ContainerOpenPacket;
use pocketmine\block\inventory\BlockInventory;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use WeakMap;

final class v84DeferredWindows
{

	public const OPEN_DELAY_TICKS = 5;

	public const RESYNC_DELAY_TICKS = 1;

	private const FIRST_SLOT = 0;

	private readonly WeakMap $sessions;

	public function __construct(private readonly Closure $later)
	{
		$this->sessions = new WeakMap();
	}

	public function admit(NetworkSession $session, int $networkId, v84ContainerOpenPacket $packet, int $hold = 0) : ?v84ContainerOpenPacket
	{
		$located = $session->getInvManager()?->locateWindowAndSlot($networkId, self::FIRST_SLOT);
		if ($located === null) {
			return $packet;
		}

		$delay = $located[0] instanceof BlockInventory ? $hold : max(self::OPEN_DELAY_TICKS, $hold);
		if ($delay <= 0) {
			return $packet;
		}

		$pending = $this->sessions[$session] ?? [];
		$pending[$networkId] = new v84PendingWindow($packet, $delay);
		$this->sessions[$session] = $pending;

		return null;
	}

	public function holds(NetworkSession $session, int $networkId) : bool
	{
		return isset(($this->sessions[$session] ?? [])[$networkId]);
	}

	public function cancel(NetworkSession $session, int $networkId) : bool
	{
		if (!$this->holds($session, $networkId)) {
			return false;
		}

		$pending = $this->sessions[$session];
		unset($pending[$networkId]);
		$this->sessions[$session] = $pending;

		return true;
	}

	public function release(NetworkSession $session) : ?v84ContainerOpenPacket
	{
		$pending = $this->sessions[$session] ?? [];
		$due = null;
		foreach ($pending as $networkId => $window) {
			if ($due === null && $window->elapse()) {
				$due = $networkId;
			}
		}

		if ($due === null) {
			return null;
		}

		$packet = $pending[$due]->packet;
		unset($pending[$due]);
		$this->sessions[$session] = $pending;
		$this->resyncLater($session, $due);

		return $packet;
	}

	private function resyncLater(NetworkSession $session, int $networkId) : void
	{
		($this->later)(static function () use ($session, $networkId) : void {
			$manager = $session->getInvManager();
			$located = $manager?->locateWindowAndSlot($networkId, self::FIRST_SLOT);
			if ($located !== null) {
				$manager->syncContents($located[0]);
			}
		}, self::RESYNC_DELAY_TICKS);
	}
}
