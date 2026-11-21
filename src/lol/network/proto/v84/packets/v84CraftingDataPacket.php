<?php

declare(strict_types=1);

namespace lol\network\proto\v84\packets;

use lol\network\proto\v84\recipes\v84CraftingEntryLayout;
use lol\network\proto\v84\recipes\v84CraftingEntryReader;
use lol\network\proto\v84\recipes\v84CraftingEntryWriter;
use lol\network\proto\v84\recipes\v84RecipeTable;
use lol\network\proto\v84\structure\v84ClientboundPacket;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use RuntimeException;
use function count;
use function strlen;

class v84CraftingDataPacket extends v84ClientboundPacket
{
	public const NETWORK_ID = v84ProtocolInfo::CRAFTING_DATA_PACKET;

	public array $shaped = [];

	public array $furnace = [];

	public bool $cleanRecipes = true;

	static public function fromTable(v84RecipeTable $table) : self
	{
		$npk = new self();
		$npk->shaped = $table->shaped();
		$npk->furnace = $table->furnace();
		$npk->cleanRecipes = true;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$count = BE::readSignedInt($in);
		for ($index = 0; $index < $count; ++$index) {
			$type = BE::readSignedInt($in);
			$body = new ByteBufferReader($in->readByteArray(BE::readSignedInt($in)));
			$this->decodeEntry($type, $body);
			if ($body->getUnreadLength() !== 0) {
				throw new RuntimeException('Trailing bytes in v84 crafting entry');
			}
		}

		$this->cleanRecipes = Byte::readUnsigned($in) !== 0;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		BE::writeSignedInt($out, count($this->shaped) + count($this->furnace));
		foreach ($this->shaped as $recipe) {
			self::writeEntry($out, v84CraftingEntryLayout::SHAPED, v84CraftingEntryWriter::shaped($recipe));
		}

		foreach ($this->furnace as $recipe) {
			self::writeEntry($out, v84CraftingEntryWriter::furnaceType($recipe), v84CraftingEntryWriter::furnace($recipe));
		}

		Byte::writeUnsigned($out, $this->cleanRecipes ? 1 : 0);
	}

	private function decodeEntry(int $type, ByteBufferReader $body) : void
	{
		if ($type === v84CraftingEntryLayout::SHAPED) {
			$this->shaped[] = v84CraftingEntryReader::shaped($body);
			return;
		}

		if ($type === v84CraftingEntryLayout::FURNACE || $type === v84CraftingEntryLayout::FURNACE_DATA) {
			$this->furnace[] = v84CraftingEntryReader::furnace($type, $body);
			return;
		}

		throw new RuntimeException('Unsupported v84 crafting entry type');
	}

	private static function writeEntry(ByteBufferWriter $out, int $type, string $body) : void
	{
		BE::writeSignedInt($out, $type);
		BE::writeSignedInt($out, strlen($body));
		$out->writeByteArray($body);
	}
}
