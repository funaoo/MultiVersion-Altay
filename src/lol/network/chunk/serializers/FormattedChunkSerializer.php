<?php

declare(strict_types=1);

namespace lol\network\chunk\serializers;

use lol\network\mcpe\MVBlockTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\data\bedrock\LegacyBiomeIdToStringIdMap;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use function count;

final class FormattedChunkSerializer implements MVChunkSerializer {

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string
	{
		$writer = new ByteBufferWriter();
		$subChunkCount = $this->getSubChunkCount($chunk, $dimensionId);
		$writtenCount = 0;

		[$minSubChunkIndex, $maxSubChunkIndex] = ChunkSerializer::getDimensionChunkBounds($dimensionId);

		for ($y = $minSubChunkIndex; $writtenCount < $subChunkCount; ++$y, ++$writtenCount) {
			$this->serializeSubChunk($chunk->getSubChunk($y), $blockTranslator, $writer, false);
		}

		$biomeIdMap = LegacyBiomeIdToStringIdMap::getInstance();

		for ($y = $minSubChunkIndex; $y <= $maxSubChunkIndex; ++$y) {
			self::serializeBiomePalette($chunk->getSubChunk($y)->getBiomeArray(), $biomeIdMap, $writer);
		}

		Byte::writeUnsigned($writer, 0);

		if ($tiles !== null) {
			$writer->writeByteArray($tiles);
		} else {
			$writer->writeByteArray(self::serializeTiles($chunk));
		}

		return $writer->getData();
	}

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int {
		return ChunkSerializer::getSubChunkCount($chunk, $dimensionId);
	}

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void
	{
		$layers = $subChunk->getBlockLayers();
		Byte::writeUnsigned($writer, 8);
		Byte::writeUnsigned($writer, count($layers));

		$blockStateDictionary = $blockTranslator->getBlockStateDictionary();

		foreach ($layers as $blocks) {
			$bitsPerBlock = $blocks->getBitsPerBlock();
			$words = $blocks->getWordArray();
			Byte::writeUnsigned($writer, ($bitsPerBlock << 1) | ($persistentBlockStates ? 0 : 1));
			$writer->writeByteArray($words);
			$palette = $blocks->getPalette();

			if ($bitsPerBlock !== 0) {
				VarInt::writeSignedInt($writer, count($palette));
			}

			if ($persistentBlockStates) {
				$nbtSerializer = new NetworkNbtSerializer();
				foreach ($palette as $p) {
					$state = $blockStateDictionary->generateDataFromStateId($blockTranslator->internalIdToNetworkId($p));
					if ($state === null) {
						$state = $blockTranslator->getFallbackStateData();
					}

					$writer->writeByteArray($nbtSerializer->write(new TreeRoot($state->toNbt())));
				}
				continue;
			}

			foreach ($palette as $p) {
				VarInt::writeSignedInt($writer, $blockTranslator->internalIdToNetworkId($p));
			}
		}
	}

	public function serializeTiles(Chunk $chunk) : string
	{
		return ChunkSerializer::serializeTiles($chunk);
	}

	private static function serializeBiomePalette(PalettedBlockArray $biomePalette, LegacyBiomeIdToStringIdMap $biomeIdMap, ByteBufferWriter $writer) : void
	{
		$biomePaletteBitsPerBlock = $biomePalette->getBitsPerBlock();
		Byte::writeUnsigned($writer, ($biomePaletteBitsPerBlock << 1) | 1);
		$writer->writeByteArray($biomePalette->getWordArray());

		$biomePaletteArray = $biomePalette->getPalette();
		if ($biomePaletteBitsPerBlock !== 0) {
			VarInt::writeSignedInt($writer, count($biomePaletteArray));
		}

		foreach ($biomePaletteArray as $p) {
			VarInt::writeSignedInt($writer, $biomeIdMap->legacyToString($p) !== null ? $p : BiomeIds::OCEAN);
		}
	}
}
