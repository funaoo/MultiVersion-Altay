<?php

declare(strict_types=1);

namespace lol\network\proto\v84\recipes;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\items\v84LegacyItem;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferWriter;

final class v84CraftingEntryWriter
{

	private function __construct()
	{
	}

	public static function shaped(v84ShapedRecipe $recipe) : string
	{
		$out = new ByteBufferWriter();
		BE::writeSignedInt($out, $recipe->width);
		BE::writeSignedInt($out, $recipe->height);
		for ($y = 0; $y < $recipe->height; ++$y) {
			for ($x = 0; $x < $recipe->width; ++$x) {
				$out->writeByteArray(self::slot($recipe->ingredient($x, $y)));
			}
		}

		BE::writeSignedInt($out, v84CraftingEntryLayout::RESULT_COUNT);
		$out->writeByteArray(self::slot($recipe->result));
		$out->writeByteArray($recipe->id);

		return $out->getData();
	}

	public static function furnaceType(v84FurnaceRecipe $recipe) : int
	{
		return $recipe->input->damage === v84LegacyItem::ANY_DAMAGE ? v84CraftingEntryLayout::FURNACE : v84CraftingEntryLayout::FURNACE_DATA;
	}

	public static function furnace(v84FurnaceRecipe $recipe) : string
	{
		$out = new ByteBufferWriter();
		$input = self::furnaceType($recipe) === v84CraftingEntryLayout::FURNACE ? $recipe->input->id : v84LegacyItem::pack($recipe->input->id, $recipe->input->damage);
		BE::writeSignedInt($out, $input);
		$out->writeByteArray(self::slot($recipe->result));

		return $out->getData();
	}

	private static function slot(v84ItemSlotData $item) : string
	{
		return v84ItemSlot::encode($item->id, $item->count, $item->damage, $item->compoundTag);
	}
}
