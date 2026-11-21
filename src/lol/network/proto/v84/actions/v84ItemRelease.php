<?php

declare(strict_types=1);

namespace lol\network\proto\v84\actions;

interface v84ItemRelease
{

	public function release() : void;

	public function cancel() : void;
}
