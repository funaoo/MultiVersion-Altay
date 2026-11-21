<?php

declare(strict_types=1);

namespace lol\network\proto\v84\paintings;

use lol\network\proto\v84\packets\v84AddPaintingPacket;
use lol\network\proto\v84\structure\v84WorldLimits;
use pocketmine\network\mcpe\protocol\AddPaintingPacket;
use function floor;

final class v84PaintingTranslator
{

	private const HALF = 0.5;

	private function __construct()
	{
	}

	public static function addPainting(AddPaintingPacket $packet, v84PaintingMotiveTable $motives) : ?v84AddPaintingPacket
	{
		$direction = v84PaintingDirection::tryFrom($packet->direction);
		$motive = $motives->resolve($packet->title);
		if ($direction === null || $motive === null) {
			return null;
		}

		$position = $packet->position;
		$y = (int) floor($position->y - $motive->height * self::HALF + self::HALF);
		if (!v84WorldLimits::isBlockY($y)) {
			return null;
		}

		return v84AddPaintingPacket::fromParts(
			$packet->actorRuntimeId,
			self::blockOnAxis($position->x, $motive->width, $direction->rightX(), $direction->outwardX()),
			$y,
			self::blockOnAxis($position->z, $motive->width, $direction->rightZ(), $direction->outwardZ()),
			$direction->value,
			$motive->name
		);
	}

	private static function blockOnAxis(float $center, int $width, int $right, int $outward) : int
	{
		if ($right === 0) {
			return (int) floor($center) - $outward;
		}

		return (int) floor($center - $right * $width * self::HALF + $right * self::HALF);
	}
}
