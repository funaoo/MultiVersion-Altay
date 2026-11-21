<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

final class v84ItemTag
{

	public const ENCHANTMENTS = 'ench';

	public const ENCHANTMENT_ID = 'id';

	public const ENCHANTMENT_LEVEL = 'lvl';

	public const DISPLAY = 'display';

	public const NAME = 'Name';

	public const LORE = 'Lore';

	public const ENCHANTMENT_ID_MAX = 24;

	public const ENCHANTMENT_LEVEL_MAX = 0x7fff;

	public const ENCHANTMENTS_MAX = 32;

	public const LORE_LINES_MAX = 32;

	public const TEXT_BYTES_MAX = 1024;

	public const NBT_DEPTH_MAX = 16;

	private function __construct()
	{
	}
}
