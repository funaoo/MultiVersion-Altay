<?php

declare(strict_types=1);

namespace lol\network\proto\v84\recipes;

use lol\network\proto\v84\items\v84ItemSlot;
use lol\network\proto\v84\items\v84ItemSlotData;
use lol\network\proto\v84\items\v84LegacyItem;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use RuntimeException;

final class v84CraftingEntryReader
{

	private function __construct()
	{
	}

	public static function shaped(ByteBufferReader $in) : v84ShapedRecipe
	{
		$width = BE::readSignedInt($in);
		$height = BE::readSignedInt($in);
		if ($width < 1 || $width > v84RecipeTable::GRID_SIDE_MAX || $height < 1 || $height > v84RecipeTable::GRID_SIDE_MAX) {
			throw new RuntimeException('Invalid v84 crafting entry grid');
		}

		$ingredients = [];
		for ($index = 0; $index < $width * $height; ++$index) {
			$ingredients[] = v84ItemSlot::decode($in);
		}

		if (BE::readSignedInt($in) !== v84CraftingEntryLayout::RESULT_COUNT) {
			throw new RuntimeException('Invalid v84 crafting entry result count');
		}

		$result = v84ItemSlot::decode($in);

		return new v84ShapedRecipe($in->readByteArray(v84CraftingEntryLayout::UUID_LENGTH), $width, $height, $ingredients, $result);
	}

	public static function furnace(int $type, ByteBufferReader $in) : v84FurnaceRecipe
	{
		$input = BE::readSignedInt($in);
		$ingredient = $type === v84CraftingEntryLayout::FURNACE ?
			new v84ItemSlotData($input, 1, v84LegacyItem::ANY_DAMAGE, '') :
			new v84ItemSlotData(v84LegacyItem::id($input), 1, v84LegacyItem::damage($input), '');

		return new v84FurnaceRecipe($ingredient, v84ItemSlot::decode($in));
	}
}
