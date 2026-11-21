<?php

declare(strict_types=1);

namespace lol\network\proto\v84\chunk;

use InvalidArgumentException;
use function strlen;

final readonly class v84ChunkSections
{

	public function __construct(
		public string $blockIds,
		public string $blockData,
		public string $skyLight,
		public string $blockLight,
		public string $heightMap,
		public string $biomeColors
	)
	{
		self::requireLength('blockIds', $this->blockIds, v84ChunkLayout::BLOCK_IDS_LENGTH);
		self::requireLength('blockData', $this->blockData, v84ChunkLayout::NIBBLE_ARRAY_LENGTH);
		self::requireLength('skyLight', $this->skyLight, v84ChunkLayout::NIBBLE_ARRAY_LENGTH);
		self::requireLength('blockLight', $this->blockLight, v84ChunkLayout::NIBBLE_ARRAY_LENGTH);
		self::requireLength('heightMap', $this->heightMap, v84ChunkLayout::HEIGHT_MAP_LENGTH);
		self::requireLength('biomeColors', $this->biomeColors, v84ChunkLayout::BIOME_COLORS_LENGTH);
	}

	public function layeredPayload(string $tiles) : string
	{
		return $this->blockIds
			. $this->blockData
			. $this->skyLight
			. $this->blockLight
			. $this->heightMap
			. $this->biomeColors
			. v84ChunkLayout::EMPTY_EXTRA_DATA
			. $tiles;
	}

	private static function requireLength(string $field, string $value, int $expected) : void
	{
		if (strlen($value) !== $expected) {
			throw new InvalidArgumentException($field . ' must be ' . $expected . ' bytes, ' . strlen($value) . ' given');
		}
	}
}
