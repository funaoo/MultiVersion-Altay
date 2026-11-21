<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use Closure;
use pocketmine\network\mcpe\NetworkSession;
use WeakMap;

final class v84VirtualChestRestorer
{

	public const RESTORE_DELAYS = [2, 10];

	public const GRACE_TICKS = 10;

	private const X = 0;

	private const Y = 1;

	private const Z = 2;

	private const GENERATION = 3;

	private const PLACED = 4;

	private const SCHEDULED = 5;

	private readonly WeakMap $sessions;

	private bool $restoring = false;

	public function __construct(
		private readonly Closure $later,
		private readonly Closure $occupied,
		private readonly Closure $restore,
		private readonly Closure $clock
	)
	{
		$this->sessions = new WeakMap();
	}

	public function observe(NetworkSession $session, int $x, int $y, int $z, bool $chest, bool $virtual) : void
	{
		if ($this->restoring) {
			return;
		}

		$key = self::key($x, $y, $z);
		$marks = $this->sessions[$session] ?? [];

		if ($virtual) {
			$marks[$key] = [$x, $y, $z, ($marks[$key][self::GENERATION] ?? 0) + 1, ($this->clock)(), false];
			$this->sessions[$session] = $marks;

			return;
		}

		if (!isset($marks[$key])) {
			return;
		}

		if ($chest) {
			unset($marks[$key]);
			$this->sessions[$session] = $marks;

			return;
		}

		$this->schedule($session, $key);
	}

	public function release(NetworkSession $session) : void
	{
		foreach (array_keys($this->sessions[$session] ?? []) as $key) {
			$this->schedule($session, $key);
		}
	}

	public function sweep(NetworkSession $session) : void
	{
		$marks = $this->sessions[$session] ?? [];
		if ($marks === [] || ($this->occupied)($session)) {
			return;
		}

		$now = ($this->clock)();
		foreach ($marks as $key => $mark) {
			if ($now - $mark[self::PLACED] >= self::GRACE_TICKS) {
				$this->schedule($session, $key);
			}
		}
	}

	private function schedule(NetworkSession $session, string $key) : void
	{
		$marks = $this->sessions[$session] ?? [];
		if (!isset($marks[$key]) || $marks[$key][self::SCHEDULED]) {
			return;
		}

		$marks[$key][self::SCHEDULED] = true;
		$this->sessions[$session] = $marks;

		$generation = $marks[$key][self::GENERATION];
		$last = array_key_last(self::RESTORE_DELAYS);
		foreach (self::RESTORE_DELAYS as $index => $delay) {
			($this->later)(fn() => $this->resend($session, $key, $generation, $index === $last), $delay);
		}
	}

	private function resend(NetworkSession $session, string $key, int $generation, bool $final) : void
	{
		$marks = $this->sessions[$session] ?? [];
		if (!$session->isConnected() || ($marks[$key][self::GENERATION] ?? null) !== $generation) {
			return;
		}

		if (($this->occupied)($session)) {
			$marks[$key][self::SCHEDULED] = false;
			$this->sessions[$session] = $marks;

			return;
		}

		$this->restoring = true;
		try {
			($this->restore)($session, $marks[$key][self::X], $marks[$key][self::Y], $marks[$key][self::Z]);
		} finally {
			$this->restoring = false;
		}

		if ($final) {
			unset($marks[$key]);
			$this->sessions[$session] = $marks;
		}
	}

	private static function key(int $x, int $y, int $z) : string
	{
		return $x . ':' . $y . ':' . $z;
	}
}
