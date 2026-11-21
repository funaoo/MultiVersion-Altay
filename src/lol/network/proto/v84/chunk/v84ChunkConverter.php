<?php

declare(strict_types=1);

namespace lol\network\proto\v84\chunk;

use lol\network\proto\v84\blocks\v84BlockResolver;
use lol\network\proto\v84\blocks\v84LegacyBlock;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use function array_fill;
use function chr;
use function ord;
use function pack;
use function str_repeat;

final readonly class v84ChunkConverter
{

	public function __construct(
		private v84BlockResolver $resolver,
		private v84BiomeColorTable $biomeColors
	)
	{
	}

	public function convert(Chunk $chunk) : v84ChunkSections
	{
		$blockIds = '';
		$blockData = '';
		$heights = array_fill(0, v84ChunkLayout::COLUMN_COUNT, 0);

		for ($section = 0; $section < v84ChunkLayout::SECTION_COUNT; ++$section) {
			[$ids, $data] = $this->convertSection($chunk->getSubChunk($section), $section * v84ChunkLayout::SECTION_SIDE, $heights);
			$blockIds .= $ids;
			$blockData .= $data;
		}

		return new v84ChunkSections(
			$blockIds,
			$blockData,
			str_repeat(v84ChunkLayout::FULL_LIGHT, v84ChunkLayout::NIBBLE_ARRAY_LENGTH),
			str_repeat(v84ChunkLayout::NO_LIGHT, v84ChunkLayout::NIBBLE_ARRAY_LENGTH),
			pack('C*', ...$heights),
			$this->packBiomeColors($chunk)
		);
	}

	private function convertSection(SubChunk $subChunk, int $baseY, array &$heights) : array
	{
		$ids = str_repeat(v84ChunkLayout::EMPTY_BLOCK, v84ChunkLayout::SECTION_BLOCKS);
		$data = str_repeat(v84ChunkLayout::EMPTY_BLOCK, v84ChunkLayout::SECTION_NIBBLES);
		if ($subChunk->isEmptyFast()) {
			return [$ids, $data];
		}

		$layers = $subChunk->getBlockLayers();
		if ($layers === []) {
			return [$ids, $data];
		}

		$layer = $layers[0];
		$packedByState = [];
		for ($x = 0; $x < v84ChunkLayout::SECTION_SIDE; ++$x) {
			for ($z = 0; $z < v84ChunkLayout::SECTION_SIDE; ++$z) {
				$column = ($z << v84ChunkLayout::Z_SHIFT) | $x;
				for ($y = 0; $y < v84ChunkLayout::SECTION_SIDE; ++$y) {
					$state = $layer->get($x, $y, $z);
					$packed = $packedByState[$state] ??= $this->resolver->resolveInternalId($state);
					$legacyId = v84LegacyBlock::id($packed);
					if ($legacyId === v84LegacyBlock::AIR) {
						continue;
					}

					$index = ($y << v84ChunkLayout::Y_SHIFT) | $column;
					$ids[$index] = chr($legacyId);
					$meta = v84LegacyBlock::meta($packed);
					if ($meta !== 0) {
						$byte = $index >> 1;
						$data[$byte] = chr(ord($data[$byte]) | (($index & 1) === 0 ? $meta : $meta << v84LegacyBlock::META_BITS));
					}
					$heights[$column] = $baseY + $y;
				}
			}
		}

		return [$ids, $data];
	}

	private function packBiomeColors(Chunk $chunk) : string
	{
		$biomes = $chunk->getSubChunk(v84ChunkLayout::BIOME_SAMPLE_SUBCHUNK)->getBiomeArray();
		$colors = [];
		for ($z = 0; $z < v84ChunkLayout::SECTION_SIDE; ++$z) {
			for ($x = 0; $x < v84ChunkLayout::SECTION_SIDE; ++$x) {
				$colors[] = $this->biomeColors->colorOf($biomes->get($x, v84ChunkLayout::BIOME_SAMPLE_Y, $z));
			}
		}

		return pack('N*', ...$colors);
	}
}
