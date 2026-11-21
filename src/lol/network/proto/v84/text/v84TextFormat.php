<?php

declare(strict_types=1);

namespace lol\network\proto\v84\text;

use function preg_replace;
use function str_contains;

final class v84TextFormat
{

	private const SECTION = "\xC2\xA7";

	private const TRAILING_RESET = "/(?:\xC2\xA7r)+\z/i";

	private const BARE_RESET = "/\xC2\xA7r(?!\xC2\xA7[0-9a-f])/i";

	private const WHITE_RESET = "\xC2\xA7r\xC2\xA7f";

	private function __construct()
	{
	}

	public static function normalize(string $text) : string
	{
		if (!str_contains($text, self::SECTION)) {
			return $text;
		}

		$text = preg_replace(self::TRAILING_RESET, '', $text) ?? $text;

		return preg_replace(self::BARE_RESET, self::WHITE_RESET, $text) ?? $text;
	}
}
