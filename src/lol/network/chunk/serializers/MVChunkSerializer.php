<?php

declare(strict_types=1);

namespace lol\network\chunk\serializers;

use lol\network\mcpe\MVBlockTranslator;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;

interface MVChunkSerializer
{

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int;

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string;

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void;

	public function serializeTiles(Chunk $chunk) : string;
}
