<?php

declare(strict_types=1);

namespace lol\network\proto\v84\chunk;

use lol\network\proto\v84\structure\v84WorldLimits;
use function max;
use function strlen;
use function substr;
use function unpack;

final class v84ChunkPayloadVerifier
{

	private const HEIGHT_MAP_OFFSET = v84ChunkLayout::BLOCK_IDS_LENGTH + 3 * v84ChunkLayout::NIBBLE_ARRAY_LENGTH;

	private function __construct()
	{
	}

	public static function verify(string $payload, int $tilesLength) : ?string
	{
		$extraDataOffset = v84ChunkLayout::FIXED_LENGTH;
		$countEnd = $extraDataOffset + v84ChunkLayout::EXTRA_DATA_COUNT_LENGTH;
		if (strlen($payload) < $countEnd) {
			return 'payload shorter than the fixed layered sections: ' . strlen($payload) . ' bytes';
		}

		$tallest = max(unpack('C*', substr($payload, self::HEIGHT_MAP_OFFSET, v84ChunkLayout::HEIGHT_MAP_LENGTH)));
		if ($tallest > v84WorldLimits::MAX_BLOCK_Y) {
			return 'height map value ' . $tallest . ' above ' . v84WorldLimits::MAX_BLOCK_Y;
		}

		$entries = unpack('V', substr($payload, $extraDataOffset, v84ChunkLayout::EXTRA_DATA_COUNT_LENGTH))[1];
		if ($entries > v84ChunkLayout::EXTRA_DATA_MAX_ENTRIES) {
			return 'extra data declares ' . $entries . ' entries';
		}

		$remaining = strlen($payload) - $countEnd - $entries * v84ChunkLayout::EXTRA_DATA_ENTRY_LENGTH;
		if ($remaining !== $tilesLength) {
			return 'payload tail mismatch: ' . $remaining . ' unread bytes, expected exactly ' . $tilesLength . ' tile bytes';
		}

		return null;
	}
}
