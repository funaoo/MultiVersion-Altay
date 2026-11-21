<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use Closure;
use lol\network\proto\v84\items\v84ItemEncoder;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;

final class v84TileItems
{

	private const NBT_DEPTH_MAX = 16;

	private ?v84ItemEncoder $encoder = null;

	private readonly LittleEndianNbtSerializer $nbt;

	private function __construct(
		private readonly Closure $factory
	)
	{
		$this->nbt = new LittleEndianNbtSerializer();
	}

	public static function lazy() : self
	{
		return new self(static fn() : v84ItemEncoder => v84ItemEncoder::create());
	}

	public static function of(v84ItemEncoder $encoder) : self
	{
		return new self(static fn() : v84ItemEncoder => $encoder);
	}

	public function compound(Item $item, ?int $slot) : ?CompoundTag
	{
		$data = ($this->encoder ??= ($this->factory)())->data($item);
		if ($data->isAir()) {
			return null;
		}

		$legacy = CompoundTag::create()
			->setShort(v84TileTag::ID, $data->id)
			->setByte(v84TileTag::ITEM_COUNT, $data->count)
			->setShort(v84TileTag::ITEM_DAMAGE, $data->damage);

		if ($slot !== null) {
			$legacy->setByte(v84TileTag::ITEM_SLOT, $slot);
		}

		if ($data->compoundTag === '') {
			return $legacy;
		}

		$offset = 0;

		return $legacy->setTag(v84TileTag::ITEM_TAG, $this->nbt->read($data->compoundTag, $offset, self::NBT_DEPTH_MAX)->mustGetCompoundTag());
	}
}
