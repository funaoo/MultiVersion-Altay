<?php

declare(strict_types=1);

namespace lol\network\proto\v84\paintings;

enum v84PaintingDirection : int
{

	case SOUTH = 0;

	case WEST = 1;

	case NORTH = 2;

	case EAST = 3;

	public function outwardX() : int
	{
		return match ($this) {
			self::WEST => -1,
			self::EAST => 1,
			default => 0
		};
	}

	public function outwardZ() : int
	{
		return match ($this) {
			self::SOUTH => 1,
			self::NORTH => -1,
			default => 0
		};
	}

	public function rightX() : int
	{
		return match ($this) {
			self::SOUTH => 1,
			self::NORTH => -1,
			default => 0
		};
	}

	public function rightZ() : int
	{
		return match ($this) {
			self::WEST => 1,
			self::EAST => -1,
			default => 0
		};
	}
}
