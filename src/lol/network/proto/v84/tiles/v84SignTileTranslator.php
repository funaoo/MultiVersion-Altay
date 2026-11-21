<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\nbt\tag\CompoundTag;
use function array_pad;
use function explode;

final readonly class v84SignTileTranslator implements v84TileTranslator
{

	private const LINE_COUNT = 4;

	private const LINE_SEPARATOR = "\n";

	public function translate(CompoundTag $modern) : CompoundTag
	{
		$sign = v84TileFields::header(v84TileId::SIGN, $modern);
		$lines = array_pad(explode(self::LINE_SEPARATOR, $this->frontText($modern)), self::LINE_COUNT, '');
		for ($line = 0; $line < self::LINE_COUNT; ++$line) {
			$sign->setString(v84TileTag::TEXT . ($line + 1), $lines[$line]);
		}

		return $sign;
	}

	private function frontText(CompoundTag $modern) : string
	{
		$front = $modern->getTag(v84TileTag::FRONT_TEXT);
		$source = $front instanceof CompoundTag ? $front : $modern;

		return v84TileFields::stringOf($source, v84TileTag::TEXT, '');
	}
}
