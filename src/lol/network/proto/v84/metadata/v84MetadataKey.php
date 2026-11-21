<?php

declare(strict_types=1);

namespace lol\network\proto\v84\metadata;

final class v84MetadataKey
{

	public const FLAGS = 0;

	public const AIR = 1;

	public const NAME_TAG = 2;

	public const SHOW_NAME_TAG = 3;

	public const SILENT = 4;

	public const POTION_COLOR = 7;

	public const POTION_AMBIENT = 8;

	public const NO_AI = 15;

	public const PLAYER_FLAGS = 16;

	public const BED_POSITION = 17;

	public const LEAD_HOLDER = 23;

	public const LEAD = 24;

	private function __construct()
	{
	}
}
