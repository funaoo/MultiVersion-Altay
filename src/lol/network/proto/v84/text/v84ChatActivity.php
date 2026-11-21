<?php

declare(strict_types=1);

namespace lol\network\proto\v84\text;

use Closure;
use pocketmine\player\Player;
use pocketmine\Server;
use WeakMap;

final class v84ChatActivity
{

	public const SETTLE_TICKS = 6;

	private static ?self $instance = null;

	private readonly WeakMap $submissions;

	public function __construct(private readonly Closure $clock)
	{
		$this->submissions = new WeakMap();
	}

	public static function shared() : self
	{
		return self::$instance ??= new self(static fn() : int => Server::getInstance()->getTick());
	}

	public function record(Player $player) : void
	{
		$this->submissions[$player] = ($this->clock)();
	}

	public function remaining(Player $player) : int
	{
		$submitted = $this->submissions[$player] ?? null;

		return $submitted === null ? 0 : max(0, self::SETTLE_TICKS - (($this->clock)() - $submitted));
	}
}
