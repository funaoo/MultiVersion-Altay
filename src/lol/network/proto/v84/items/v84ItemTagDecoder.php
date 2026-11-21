<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

use GlobalLogger;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use function count;
use function min;
use function strlen;

final class v84ItemTagDecoder
{

	private readonly LittleEndianNbtSerializer $nbt;

	public function __construct(
		private readonly EnchantmentIdMap $enchantments
	)
	{
		$this->nbt = new LittleEndianNbtSerializer();
	}

	public function apply(Item $item, string $compoundTag) : void
	{
		if ($compoundTag === '') {
			return;
		}

		$offset = 0;
		try {
			$root = $this->nbt->read($compoundTag, $offset, v84ItemTag::NBT_DEPTH_MAX)->getTag();
		} catch (NbtException $e) {
			GlobalLogger::get()->debug('Item NBT from a 0.15.10 client is invalid and was ignored: ' . $e->getMessage());

			return;
		}

		if (!$root instanceof CompoundTag) {
			return;
		}

		$this->applyEnchantments($item, $root->getTag(v84ItemTag::ENCHANTMENTS));
		$this->applyDisplay($item, $root->getTag(v84ItemTag::DISPLAY));
	}

	private function applyEnchantments(Item $item, mixed $list) : void
	{
		if (!$list instanceof ListTag) {
			return;
		}

		$accepted = 0;
		foreach ($list->getValue() as $entry) {
			if (!$entry instanceof CompoundTag) {
				continue;
			}

			$instance = $this->enchantmentInstance($entry);
			if ($instance === null) {
				continue;
			}

			$item->addEnchantment($instance);
			if (++$accepted === v84ItemTag::ENCHANTMENTS_MAX) {
				return;
			}
		}
	}

	private function enchantmentInstance(CompoundTag $entry) : ?EnchantmentInstance
	{
		$id = $entry->getTag(v84ItemTag::ENCHANTMENT_ID);
		$level = $entry->getTag(v84ItemTag::ENCHANTMENT_LEVEL);
		if (!$id instanceof ShortTag || !$level instanceof ShortTag) {
			return null;
		}

		$type = $this->enchantmentType($id->getValue());
		if ($type === null || $level->getValue() < 1) {
			return null;
		}

		return new EnchantmentInstance($type, min($level->getValue(), $type->getMaxLevel()));
	}

	private function enchantmentType(int $id) : ?Enchantment
	{
		if ($id < 0 || $id > v84ItemTag::ENCHANTMENT_ID_MAX) {
			return null;
		}

		return $this->enchantments->fromId($id);
	}

	private function applyDisplay(Item $item, mixed $display) : void
	{
		if (!$display instanceof CompoundTag) {
			return;
		}

		$name = $display->getTag(v84ItemTag::NAME);
		if ($name instanceof StringTag && $this->acceptableText($name->getValue())) {
			$item->setCustomName($name->getValue());
		}

		$lore = $display->getTag(v84ItemTag::LORE);
		if ($lore instanceof ListTag) {
			$lines = $this->loreLines($lore);
			if ($lines !== []) {
				$item->setLore($lines);
			}
		}
	}

	private function loreLines(ListTag $lore) : array
	{
		$lines = [];
		foreach ($lore->getValue() as $line) {
			if ($line instanceof StringTag && $this->acceptableText($line->getValue())) {
				$lines[] = $line->getValue();
			}
			if (count($lines) === v84ItemTag::LORE_LINES_MAX) {
				break;
			}
		}

		return $lines;
	}

	private function acceptableText(string $text) : bool
	{
		return $text !== '' && strlen($text) <= v84ItemTag::TEXT_BYTES_MAX;
	}
}
