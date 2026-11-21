<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

final class v844UnsupportedPackets
{

	private const IDS = [
		328 => true,
		329 => true,
		330 => true,
		331 => true,
		332 => true,
		333 => true,
		334 => true,
		335 => true,
		336 => true,
		337 => true,
		338 => true,
		339 => true,
		340 => true,
		341 => true,
		342 => true,
		343 => true,
		344 => true,
		345 => true,
		346 => true,
		347 => true,
		348 => true,
		349 => true,
		350 => true,
		351 => true,
		352 => true,
	];

	private function __construct()
	{

	}

	public static function isUnsupported(int $packetId) : bool
	{
		return isset(self::IDS[$packetId]);
	}
}
