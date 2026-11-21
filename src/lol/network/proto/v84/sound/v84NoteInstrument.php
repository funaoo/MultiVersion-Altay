<?php

declare(strict_types=1);

namespace lol\network\proto\v84\sound;

final class v84NoteInstrument
{

	public const NOTE_MAX = 24;

	private const FROM_MODERN = [
		0 => 0,
		1 => 1,
		2 => 3,
		3 => 2,
		4 => 4,
	];

	private function __construct()
	{
	}

	public static function fromModern(int $modernInstrument) : ?int
	{
		return self::FROM_MODERN[$modernInstrument] ?? null;
	}
}
