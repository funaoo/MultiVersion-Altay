<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use Closure;
use lol\network\proto\v84\inventory\v84ServerTicks;
use pocketmine\player\Player;
use WeakMap;
use function hrtime;
use function intdiv;

final class v84EatingClock
{

	private const TICK_NANOSECONDS = 50000000;

	private const ACCEPTED_PERCENT = 80;

	private const PERCENT = 100;

	private static ?WeakMap $starts = null;

	private static ?WeakMap $deferred = null;

	private function __construct()
	{
	}

	public static function begin(Player $player) : void
	{
		self::starts()[$player] = hrtime(true);
	}

	public static function end(Player $player) : void
	{
		unset(self::starts()[$player]);
	}

	public static function hasElapsed(Player $player, int $ticks) : bool
	{
		return self::remainingTicks($player, $ticks) === 0;
	}

	public static function remainingTicks(Player $player, int $ticks) : ?int
	{
		$start = self::starts()[$player] ?? null;
		if ($start === null) {
			return null;
		}

		$missing = intdiv($ticks * self::TICK_NANOSECONDS * self::ACCEPTED_PERCENT, self::PERCENT) - (hrtime(true) - $start);
		return $missing <= 0 ? 0 : intdiv($missing + self::TICK_NANOSECONDS - 1, self::TICK_NANOSECONDS);
	}

	public static function defer(Player $player, int $ticks, Closure $task) : void
	{
		$deferred = self::deferred();
		if (isset($deferred[$player])) {
			return;
		}

		$deferred[$player] = true;
		v84ServerTicks::after(static function () use ($player, $task, $deferred) : void {
			unset($deferred[$player]);
			$task();
		}, $ticks);
	}

	private static function starts() : WeakMap
	{
		return self::$starts ??= new WeakMap();
	}

	private static function deferred() : WeakMap
	{
		return self::$deferred ??= new WeakMap();
	}
}
