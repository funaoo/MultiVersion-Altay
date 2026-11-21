<?php

declare(strict_types=1);

namespace lol\network\chunk\io;

use function count;
use function str_repeat;

final readonly class ChunkDatum {

	static public function empty() : self    {
		$subChunks = [];
		for($i = 0; $i < 16; $i++) {
			$subChunks[] = SubChunkDatum::empty();
		}
		return new self(
			str_repeat("\0", 256),
			$subChunks,
		);
	}

	public function __construct(
		public string $biomes,
		public array $subChunkDatum = []
	) {
		if(count($this->subChunkDatum) !== 16){
			throw new \InvalidArgumentException("SubChunk count must be 16, " . count($this->subChunkDatum) . " given.");
		}
	}

	public function isEmpty() : bool    {
		static $emptyBiomes = null;
		$emptyBiomes ??= str_repeat("\0", 256);
		if($this->biomes !== $emptyBiomes) {
			return false;
		}

		foreach($this->subChunkDatum as $subChunkDatum) {
			if(!$subChunkDatum->isEmpty()) {
				return false;
			}
		}
		return true;
	}
}
