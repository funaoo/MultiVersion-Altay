<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use Closure;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

final class v84ServerTicks
{

	private const PLUGIN_NAME = 'MultiVersion';

	private function __construct()
	{
	}

	public static function after(Closure $task, int $ticks) : void
	{
		$plugin = Server::getInstance()->getPluginManager()->getPlugin(self::PLUGIN_NAME);
		if ($plugin === null) {
			$task();
			return;
		}

		$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask($task), $ticks);
	}
}
