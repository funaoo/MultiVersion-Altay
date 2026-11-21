<?php

declare(strict_types=1);

namespace lol\network\proto\v84\structure;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;

final class v84ActorFeet
{

	private function __construct()
	{
	}

	public static function y(Entity $entity, float $offsetY) : float
	{
		return $offsetY - $entity->getOffsetPosition(new Vector3(0.0, 0.0, 0.0))->y;
	}
}
