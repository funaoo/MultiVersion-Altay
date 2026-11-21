<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use Closure;
use pocketmine\network\mcpe\NetworkSession;
use WeakMap;

final class v84DisplayedWindows
{

	public const ACKNOWLEDGE_DELAY_TICKS = 1;

	private readonly WeakMap $sessions;

	public function __construct(private readonly Closure $later, private readonly Closure $acknowledge)
	{
		$this->sessions = new WeakMap();
	}

	public function admit(NetworkSession $session, int $networkId) : bool
	{
		if (($this->sessions[$session] ?? null) !== $networkId) {
			$this->sessions[$session] = $networkId;

			return true;
		}

		$this->acknowledgeLater($session);

		return false;
	}

	public function forget(NetworkSession $session, int $networkId) : void
	{
		if (($this->sessions[$session] ?? null) === $networkId) {
			unset($this->sessions[$session]);
		}
	}

	private function acknowledgeLater(NetworkSession $session) : void
	{
		$acknowledge = $this->acknowledge;
		($this->later)(static function () use ($session, $acknowledge) : void {
			if ($session->isConnected()) {
				$acknowledge($session);
			}
		}, self::ACKNOWLEDGE_DELAY_TICKS);
	}
}
