<?php

declare(strict_types=1);

namespace lol\network\proto\v84\scoreboard;

use lol\network\proto\v84\text\v84TextType;
use function max;
use function min;
use function strtolower;

final class v84ScoreboardSettings
{

	private const LINE_LIMIT = 15;

	private static bool $enabled = true;

	private static int $refreshTicks = 10;

	private static int $maxLines = 12;

	private static int $channel = v84TextType::TIP;

	private static bool $showNumbers = false;

	private function __construct()
	{
	}

	public static function configure(bool $enabled, int $refreshTicks, int $maxLines, string $channel, bool $showNumbers = false) : void
	{
		self::$enabled = $enabled;
		self::$refreshTicks = max(1, $refreshTicks);
		self::$maxLines = max(1, min($maxLines, self::LINE_LIMIT));
		self::$showNumbers = $showNumbers;
		self::$channel = strtolower($channel) === 'popup' ? v84TextType::POPUP : v84TextType::TIP;
	}

	public static function isEnabled() : bool
	{
		return self::$enabled;
	}

	public static function refreshTicks() : int
	{
		return self::$refreshTicks;
	}

	public static function maxLines() : int
	{
		return self::$maxLines;
	}

	public static function showNumbers() : bool
	{
		return self::$showNumbers;
	}

	public static function channel() : int
	{
		return self::$channel;
	}
}
