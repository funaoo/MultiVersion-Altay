<?php

declare(strict_types=1);

namespace lol\network\chunk\serializers;

use lol\network\chunk\io\ChunkDatum;
use lol\network\chunk\io\SubChunkDatum;
use lol\network\mcpe\MVBlockTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\block\tile\Spawnable;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use function chr;
use function ord;
use function str_repeat;

class PreFormattedChunkSerializer implements MVChunkSerializer {

	public const SUBCHUNK_COUNT = 16;
	public const BLOCKS_PER_SUBCHUNK = 4096;

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string
	{
		$stream = new ByteBufferWriter();
		$subChunkCount = $this->getSubChunkCount($chunk, $dimensionId);
		$datum = self::prepareChunk($blockTranslator, $chunk, $subChunkCount);
		$subChunks = $datum->subChunkDatum;

		for ($y = 0; $y < $subChunkCount; ++$y) {
			$subChunk = $subChunks[$y];
			Byte::writeUnsigned($stream, 0);
			$stream->writeByteArray($subChunk->blocksId);
			$stream->writeByteArray($subChunk->blocksData);
		}

		$stream->writeByteArray($datum->biomes);
		Byte::writeUnsigned($stream, 0);

		if ($tiles !== null) {
			$stream->writeByteArray($tiles);
		} else {
			$stream->writeByteArray($this->serializeTiles($chunk));
		}
		return $stream->getData();
	}

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int
	{
		for ($y = self::SUBCHUNK_COUNT - 1; $y >= 0; --$y) {
			if (!$chunk->getSubChunk($y)->isEmptyFast()) {
				return $y + 1;
			}
		}

		return 0;
	}

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void{}

	public function serializeTiles(Chunk $chunk) : string
	{
		$stream = new ByteBufferWriter();
		foreach ($chunk->getTiles() as $tile) {
			if ($tile instanceof Spawnable) {
				$stream->writeByteArray(
					$tile->getSerializedSpawnCompound()->getEncodedNbt()
				);
			}
		}

		return $stream->getData();
	}

	static protected function prepareChunk(MVBlockTranslator $blockTranslator, Chunk $chunk, int $subChunkCount) : ChunkDatum {
		$biomes = self::reduce3DBiomes($chunk->getSubChunk(Chunk::MIN_SUBCHUNK_INDEX)->getBiomeArray());
		$subChunks = [];
		for($y = 0; $y < self::SUBCHUNK_COUNT; $y++) {
			$layers = $y < $subChunkCount ? $chunk->getSubChunk($y)->getBlockLayers() : [];
			if(empty($layers)) {
				$subChunks[$y] = SubChunkDatum::empty();
				continue;
			}
			[$blocks, $data] = self::palettedToClassic($layers[0], $blockTranslator);
			$subChunks[$y] = new SubChunkDatum($blocks, $data);
		}

		return new ChunkDatum($biomes, $subChunks);
	}

	protected static function palettedToClassic(PalettedBlockArray $paletted, MVBlockTranslator $blockTranslator) : array {
		$blocks = str_repeat("\x00", self::BLOCKS_PER_SUBCHUNK);
		$data = str_repeat("\x00", self::BLOCKS_PER_SUBCHUNK >> 1);
		$resolved = [];
		$i = 0;

		for ($x = 0; $x < 16; $x++) {
			for ($z = 0; $z < 16; $z++) {
				for ($y = 0; $y < 16; $y++) {
					$stateId = $paletted->get($x, $y, $z);
					[$legacyId, $meta] = $resolved[$stateId] ??= $blockTranslator->internalIdToChunkIdMeta($stateId);

					$blocks[$i] = chr($legacyId);
					if ($meta !== 0) {
						$data[$i >> 1] = chr(ord($data[$i >> 1]) | (($i & 1) === 0 ? $meta : $meta << 4));
					}
					$i++;
				}
			}
		}

		return [$blocks, $data];
	}

	private static function reduce3DBiomes(PalettedBlockArray $biomes3d) : string{
		$biomes2d = new ByteBufferWriter();

		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				Byte::writeUnsigned($biomes2d, $biomes3d->get($x, 0, $z));
			}
		}

		return $biomes2d->getData();
	}
}
