<?php

declare(strict_types=1);

namespace lol\network\chunk\serializers;

use lol\network\mcpe\MVBlockTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use Throwable;
use function intdiv;

final class MVChunkPayloadVerifier {

	private function __construct() {

	}

	public static function verify(string $payload, int $subChunkCount, int $dimensionId, MVBlockTranslator $blockTranslator, int $tilesLength) : ?string {
		$states = $blockTranslator->getBlockStateDictionary()->getStates();
		$in = new ByteBufferReader($payload);

		try {
			for ($i = 0; $i < $subChunkCount; ++$i) {
				$version = Byte::readUnsigned($in);
				if ($version !== 8 && $version !== 9) {
					return "subchunk #$i: unexpected format version $version";
				}

				$storageCount = Byte::readUnsigned($in);
				if ($version === 9) {
					Byte::readSigned($in);
				}

				for ($layer = 0; $layer < $storageCount; ++$layer) {
					$error = self::readPalettedArray($in, $states, "subchunk #$i layer #$layer");
					if ($error !== null) {
						return $error;
					}
				}
			}

			[$minSubChunkIndex, $maxSubChunkIndex] = ChunkSerializer::getDimensionChunkBounds($dimensionId);
			$biomeCount = 0;
			for ($y = $minSubChunkIndex; $y <= $maxSubChunkIndex; ++$y) {
				$error = self::readPalettedArray($in, null, "biome palette y=$y");
				if ($error !== null) {
					return $error;
				}
				++$biomeCount;
			}

			$borderBlockCount = Byte::readUnsigned($in);
			if ($borderBlockCount > 0) {
				$in->readByteArray($borderBlockCount);
			}
		} catch (Throwable $e) {
			return "payload ran out while parsing: " . $e->getMessage();
		}

		$remaining = $in->getUnreadLength();
		if ($remaining !== $tilesLength) {
			return "payload tail mismatch: $remaining unread bytes, expected exactly $tilesLength tile bytes (biome palettes written: $biomeCount)";
		}

		return null;
	}

	public static function verifyLegacy(string $payload, int $subChunkCount, int $tilesLength) : ?string {
		$in = new ByteBufferReader($payload);

		try {
			for ($i = 0; $i < $subChunkCount; ++$i) {
				$version = Byte::readUnsigned($in);
				if ($version !== 0) {
					return "subchunk #$i: unexpected format version $version, expected 0";
				}

				$in->readByteArray(PreFormattedChunkSerializer::BLOCKS_PER_SUBCHUNK);
				$in->readByteArray(PreFormattedChunkSerializer::BLOCKS_PER_SUBCHUNK >> 1);
			}

			$in->readByteArray(256);

			$borderBlockCount = Byte::readUnsigned($in);
			if ($borderBlockCount > 0) {
				$in->readByteArray($borderBlockCount);
			}
		} catch (Throwable $e) {
			return "payload ran out while parsing: " . $e->getMessage();
		}

		$remaining = $in->getUnreadLength();
		if ($remaining !== $tilesLength) {
			return "payload tail mismatch: $remaining unread bytes, expected exactly $tilesLength tile bytes";
		}

		return null;
	}

	private static function readPalettedArray(ByteBufferReader $in, ?array $states, string $where) : ?string {
		$flags = Byte::readUnsigned($in);
		$bitsPerBlock = $flags >> 1;

		if (($flags & 1) === 0) {
			return "$where: persistent palette flag set, runtime IDs expected";
		}

		if ($bitsPerBlock === 0) {
			$paletteCount = 1;
		} else {
			if ($bitsPerBlock > 32) {
				return "$where: invalid bitsPerBlock $bitsPerBlock";
			}

			$blocksPerWord = intdiv(32, $bitsPerBlock);
			$wordCount = intdiv(4096, $blocksPerWord);
			if ($wordCount * $blocksPerWord !== 4096) {
				++$wordCount;
			}

			$in->readByteArray($wordCount * 4);

			$paletteCount = VarInt::readSignedInt($in);
			if ($paletteCount <= 0) {
				return "$where: palette entry count is $paletteCount";
			}
		}

		for ($i = 0; $i < $paletteCount; ++$i) {
			$id = VarInt::readSignedInt($in);
			if ($states !== null && !isset($states[$id])) {
				return "$where: palette entry #$i references runtime ID $id, which is absent from the protocol block palette";
			}
		}

		return null;
	}
}
