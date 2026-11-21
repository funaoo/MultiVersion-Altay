<?php

declare(strict_types=1);

namespace lol\network\utils;

use lol\network\proto\TProtocol;

trait PacketCachedTrait
{

	final public function __construct(protected TProtocol $protocol) {
		$this->load();
	}

	abstract protected function load() : void;

	public function getProtocol() : TProtocol {
		return $this->protocol;
	}
}
