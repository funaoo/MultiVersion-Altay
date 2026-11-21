<?php

declare(strict_types=1);

namespace lol\network\utils;

use Logger;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use Throwable;

final class TranslationGuard
{

	private static array $reported = [];

	private function __construct()
	{

	}

	public static function report(Logger $logger, ClientboundPacket $packet, Throwable $e) : void
	{
		$key = $packet::class . "|" . $e::class;
		$message = "Dropped " . $packet->getName() . " (pid " . $packet->pid() . "): " . $e::class . ": " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();

		if (isset(self::$reported[$key])) {
			$logger->debug($message);
			return;
		}

		self::$reported[$key] = true;
		$logger->error($message);
	}
}
