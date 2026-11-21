<?php

declare(strict_types=1);

namespace lol\network\proto\v84\chunk;

use lol\network\chunk\serializers\MVChunkSerializer;
use lol\network\mcpe\MVBlockTranslator;
use lol\network\proto\v84\blocks\v84BlockResolver;
use lol\network\proto\v84\blocks\v84BlockTable;
use lol\network\proto\v84\tiles\v84TileItems;
use lol\network\proto\v84\tiles\v84TileSerializer;
use lol\network\proto\v84\tiles\v84TileTable;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\SubChunk;

final class v84ChunkSerializer implements MVChunkSerializer
{

	public function __construct(
		private ?v84ChunkConverter $converter = null,
		private ?v84TileSerializer $tiles = null
	)
	{
	}

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int
	{
		for ($section = v84ChunkLayout::SECTION_COUNT - 1; $section >= 0; --$section) {
			if (!$chunk->getSubChunk($section)->isEmptyFast()) {
				return $section + 1;
			}
		}

		return 0;
	}

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string
	{
		return $this->converter()->convert($chunk)->layeredPayload($tiles ?? $this->serializeTiles($chunk));
	}

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void
	{
	}

	public function serializeTiles(Chunk $chunk) : string
	{
		return $this->tileSerializer()->serialize($chunk);
	}

	public function serializeBlockEntity(CompoundTag $modern) : string
	{
		return $this->tileSerializer()->encodeCompound($modern);
	}

	public function serializeTile(Spawnable $tile) : string
	{
		return $this->tileSerializer()->encodeTile($tile);
	}

	private function tileSerializer() : v84TileSerializer
	{
		return $this->tiles ??= new v84TileSerializer(v84TileTable::create(v84BlockTable::load(), v84TileItems::lazy()));
	}

	private function converter() : v84ChunkConverter
	{
		return $this->converter ??= new v84ChunkConverter(
			v84BlockResolver::load(GlobalBlockStateHandlers::getSerializer()),
			v84BiomeColorTable::load()
		);
	}
}
