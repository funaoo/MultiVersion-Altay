<?php

declare(strict_types=1);

namespace lol\network\proto\v84\recipes;

final class v84CraftingEntryLayout
{

	public const SHAPED = 1;

	public const FURNACE = 2;

	public const FURNACE_DATA = 3;

	public const RESULT_COUNT = 1;

	public const UUID_LENGTH = 16;

	private function __construct()
	{
	}
}
