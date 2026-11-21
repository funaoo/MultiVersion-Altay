<?php

declare(strict_types=1);

namespace lol\network\global;

use pocketmine\network\mcpe\protocol\types\skin\SkinData;

interface MVSkinHelper
{

	public function adaptSkinData(SkinData &$skin) : void;
}
