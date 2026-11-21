<?php

declare(strict_types=1);

namespace lol\network\chunk\io;

use pocketmine\world\format\io\exception\CorruptedChunkException;
use function str_repeat;
use function strlen;

final class SubChunkDatum {

	private static function assignData(string $data, int $length, bool $preserve) : string{
		if(strlen($data) !== $length){
			if($preserve && $data === ""){
				throw new CorruptedChunkException("Invalid non-zero length given, expected $length, got " . strlen($data));
			}
			return str_repeat("\x00", $length);
		}
		return $data;
	}

	static public function empty() : self    {
		return new self(
			self::assignData("", 4096, false),
			self::assignData("", 2048, false),
		);
	}

	public function __construct(
		public string $blocksId,
		public string $blocksData,
	){
		$this->blocksId = self::assignData($this->blocksId, 4096, true);
		$this->blocksData = self::assignData($this->blocksData, 2048, true);
	}

	public function isEmpty() : bool {
		static $emptyId = null;
		static $emptyData = null;
		$emptyId ??= self::assignData("", 4096, false);
		$emptyData ??= self::assignData("", 2048, false);

		return $this->blocksId === $emptyId
			&& $this->blocksData === $emptyData;
	}
}
