<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

final class v84InventoryLayout
{

	public const HOTBAR_SIZE = 9;

	public const DUMMY_SLOT_COUNT = self::HOTBAR_SIZE;

	public const HOTBAR_LINK_OFFSET = self::HOTBAR_SIZE;

	public const UNSET_HOTBAR_SLOT = 0;

	private function __construct()
	{
	}
}
