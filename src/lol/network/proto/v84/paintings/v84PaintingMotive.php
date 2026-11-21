<?php

declare(strict_types=1);

namespace lol\network\proto\v84\paintings;

final readonly class v84PaintingMotive
{

	public function __construct(
		public string $name,
		public int    $width,
		public int    $height
	)
	{
	}
}
