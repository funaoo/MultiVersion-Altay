<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

use InvalidArgumentException;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\TreeRoot;
use function count;
use function min;
use function strlen;

final class v84ItemTagEncoder
{

	private readonly LittleEndianNbtSerializer $nbt;

	public function __construct(
		private readonly EnchantmentIdMap $enchantments
	)
	{
		$this->nbt = new LittleEndianNbtSerializer();
	}

	public function encode(Item $item) : string
	{
		return $this->write($this->enchantmentList($item), $item->hasCustomName() ? $item->getCustomName() : '', $item->getLore());
	}

	private function write(?ListTag $enchantments, string $name, array $lore) : string
	{
		$display = $this->display($name, $lore);
		if ($enchantments === null && $display === null) {
			return '';
		}

		$root = CompoundTag::create();
		if ($enchantments !== null) {
			$root->setTag(v84ItemTag::ENCHANTMENTS, $enchantments);
		}
		if ($display !== null) {
			$root->setTag(v84ItemTag::DISPLAY, $display);
		}

		return $this->nbt->write(new TreeRoot($root));
	}

	private function enchantmentList(Item $item) : ?ListTag
	{
		$entries = [];
		foreach ($item->getEnchantments() as $instance) {
			$entry = $this->enchantmentEntry($instance->getType(), $instance->getLevel());
			if ($entry !== null) {
				$entries[] = $entry;
			}
			if (count($entries) === v84ItemTag::ENCHANTMENTS_MAX) {
				break;
			}
		}

		return $entries === [] ? null : new ListTag($entries);
	}

	private function enchantmentEntry(Enchantment $type, int $level) : ?CompoundTag
	{
		try {
			$id = $this->enchantments->toId($type);
		} catch (InvalidArgumentException) {
			return null;
		}

		if ($id < 0 || $id > v84ItemTag::ENCHANTMENT_ID_MAX || $level < 1) {
			return null;
		}

		return CompoundTag::create()
			->setShort(v84ItemTag::ENCHANTMENT_ID, $id)
			->setShort(v84ItemTag::ENCHANTMENT_LEVEL, min($level, v84ItemTag::ENCHANTMENT_LEVEL_MAX));
	}

	private function display(string $name, array $lore) : ?CompoundTag
	{
		$lines = [];
		foreach ($lore as $line) {
			if (strlen($line) <= v84ItemTag::TEXT_BYTES_MAX) {
				$lines[] = new StringTag($line);
			}
			if (count($lines) === v84ItemTag::LORE_LINES_MAX) {
				break;
			}
		}

		$hasName = $name !== '' && strlen($name) <= v84ItemTag::TEXT_BYTES_MAX;
		if (!$hasName && $lines === []) {
			return null;
		}

		$display = CompoundTag::create();
		if ($hasName) {
			$display->setString(v84ItemTag::NAME, $name);
		}
		if ($lines !== []) {
			$display->setTag(v84ItemTag::LORE, new ListTag($lines));
		}

		return $display;
	}
}
