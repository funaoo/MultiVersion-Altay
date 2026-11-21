<?php

declare(strict_types=1);

namespace lol\network\proto\v84\inventory;

use pocketmine\inventory\transaction\action\CreateItemAction;
use pocketmine\inventory\transaction\action\DestroyItemAction;
use pocketmine\item\Item;
use function min;

final class v84CreativeBalance
{
	private function __construct()
	{
	}

	public static function extraActions(array $actions, bool $allowDestroy) : array
	{
		$needed = [];
		$owned = [];
		foreach ($actions as $action) {
			$target = $action->getTargetItem();
			if (!$target->isNull()) {
				$needed[] = $target;
			}
			$source = $action->getSourceItem();
			if (!$source->isNull()) {
				$owned[] = $source;
			}
		}
		foreach ($needed as $index => $item) {
			self::absorb($item, $owned);
			if ($item->isNull()) {
				unset($needed[$index]);
			}
		}
		$extra = [];
		foreach ($needed as $item) {
			$extra[] = new CreateItemAction($item);
		}
		if (!$allowDestroy) {
			return $extra;
		}
		foreach ($owned as $item) {
			$extra[] = new DestroyItemAction($item);
		}
		return $extra;
	}

	private static function absorb(Item $needed, array &$owned) : void
	{
		foreach ($owned as $index => $candidate) {
			if (!$needed->canStackWith($candidate)) {
				continue;
			}
			$amount = min($needed->getCount(), $candidate->getCount());
			$needed->setCount($needed->getCount() - $amount);
			$candidate->setCount($candidate->getCount() - $amount);
			if ($candidate->isNull()) {
				unset($owned[$index]);
			}
			if ($needed->isNull()) {
				return;
			}
		}
	}
}
