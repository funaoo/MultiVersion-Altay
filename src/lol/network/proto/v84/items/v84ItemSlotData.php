<?php

declare(strict_types=1);

namespace lol\network\proto\v84\items;

final class v84ItemSlotData
{

	public function __construct(
		public readonly int    $id,
		public readonly int    $count,
		public readonly int    $damage,
		public readonly string $compoundTag
	)
	{
	}

	public static function air() : self
	{
		return new self(v84LegacyItem::AIR, 0, 0, '');
	}

	public function isAir() : bool
	{
		return $this->id === v84LegacyItem::AIR;
	}

	public function sameItem(self $other, bool $checkDamage) : bool
	{
		return $this->id === $other->id && (!$checkDamage || $this->damage === $other->damage);
	}

	public function sameKind(self $other) : bool
	{
		return $this->id === $other->id && $this->damage === $other->damage && $this->compoundTag === $other->compoundTag;
	}
}
