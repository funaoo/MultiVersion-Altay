<?php

declare(strict_types=1);

namespace lol\network\proto\v84\blocks;

final class v84BlockVariants
{

	private const KEY_SEPARATOR = '|';

	public function __construct(
		private readonly array $properties,
		private readonly array $variants,
		private readonly int $fallback
	)
	{
	}

	public function resolve(array $states) : int
	{
		$key = '';
		foreach ($this->properties as $index => $property) {
			if ($index > 0) {
				$key .= self::KEY_SEPARATOR;
			}
			$key .= $states[$property] ?? '';
		}

		return $this->variants[$key] ?? $this->fallback;
	}
}
