<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

interface v84PlayerStances
{

	public function setSprinting(bool $sprinting) : void;

	public function setSneaking(bool $sneaking) : void;

	public function stopSleeping() : void;

	public function jump() : void;
}
