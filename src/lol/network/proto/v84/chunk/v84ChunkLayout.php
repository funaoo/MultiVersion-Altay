<?php

declare(strict_types=1);

namespace lol\network\proto\v84\chunk;

final class v84ChunkLayout
{

	public const ORDER_COLUMNS = 0;

	public const ORDER_LAYERED = 1;

	public const SECTION_COUNT = 8;

	public const SECTION_SIDE = 16;

	public const SECTION_BLOCKS = 4096;

	public const SECTION_NIBBLES = 2048;

	public const COLUMN_COUNT = 256;

	public const Z_SHIFT = 4;

	public const Y_SHIFT = 8;

	public const BLOCK_IDS_LENGTH = self::SECTION_COUNT * self::SECTION_BLOCKS;

	public const NIBBLE_ARRAY_LENGTH = self::SECTION_COUNT * self::SECTION_NIBBLES;

	public const HEIGHT_MAP_LENGTH = self::COLUMN_COUNT;

	public const BIOME_COLORS_LENGTH = self::COLUMN_COUNT * 4;

	public const FIXED_LENGTH = self::BLOCK_IDS_LENGTH + 3 * self::NIBBLE_ARRAY_LENGTH + self::HEIGHT_MAP_LENGTH + self::BIOME_COLORS_LENGTH;

	public const EXTRA_DATA_COUNT_LENGTH = 4;

	public const EXTRA_DATA_ENTRY_LENGTH = 6;

	public const EXTRA_DATA_MAX_ENTRIES = self::BLOCK_IDS_LENGTH;

	public const EMPTY_EXTRA_DATA = "\x00\x00\x00\x00";

	public const FULL_LIGHT = "\xff";

	public const NO_LIGHT = "\x00";

	public const EMPTY_BLOCK = "\x00";

	public const BIOME_SAMPLE_SUBCHUNK = 4;

	public const BIOME_SAMPLE_Y = 0;

	private function __construct()
	{
	}
}
