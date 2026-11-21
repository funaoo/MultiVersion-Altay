<?php

declare(strict_types=1);

namespace lol\network\proto\v84\paintings;

use pocketmine\entity\object\PaintingMotive;

final readonly class v84PaintingMotiveTable
{

	private const MOTIVES = [
		['Kebab', 1, 1],
		['Aztec', 1, 1],
		['Alban', 1, 1],
		['Aztec2', 1, 1],
		['Bomb', 1, 1],
		['Plant', 1, 1],
		['Wasteland', 1, 1],
		['Wanderer', 1, 2],
		['Graham', 1, 2],
		['Pool', 2, 1],
		['Courbet', 2, 1],
		['Sunset', 2, 1],
		['Sea', 2, 1],
		['Creebet', 2, 1],
		['Match', 2, 2],
		['Bust', 2, 2],
		['Stage', 2, 2],
		['Void', 2, 2],
		['SkullAndRoses', 2, 2],
		['Fighters', 4, 2],
		['Skeleton', 4, 3],
		['DonkeyKong', 4, 3],
		['Pointer', 4, 4],
		['Pigscene', 4, 4],
	];

	private const SIZE_KEY_WIDTH_FACTOR = 100;

	private array $byName;

	private array $bySize;

	public function __construct()
	{
		$byName = [];
		$bySize = [];
		foreach (self::MOTIVES as [$name, $width, $height]) {
			$motive = new v84PaintingMotive($name, $width, $height);
			$byName[$name] = $motive;
			$bySize[$width * self::SIZE_KEY_WIDTH_FACTOR + $height] ??= $motive;
		}

		$this->byName = $byName;
		$this->bySize = $bySize;
	}

	public function resolve(string $title) : ?v84PaintingMotive
	{
		$known = $this->byName[$title] ?? null;
		if ($known !== null) {
			return $known;
		}

		$source = PaintingMotive::getMotiveByName($title);
		if ($source === null) {
			return null;
		}

		return $this->bySize[$source->getWidth() * self::SIZE_KEY_WIDTH_FACTOR + $source->getHeight()] ?? null;
	}
}
