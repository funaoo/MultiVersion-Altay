<?php

declare(strict_types=1);

namespace lol\network\transport;

use altay\network\raknet\server\ProtocolAcceptor;
use function array_keys;
use function max;

final class MVRakNetAcceptor implements ProtocolAcceptor {

	private array $versions = [];

	public function __construct(array $versions)
	{
		foreach ($versions as $v) {
			$this->versions[$v] = true;
		}
	}

	public function accepts(int $protocolVersion) : bool {
		return isset($this->versions[$protocolVersion]);
	}

	public function getPrimaryVersion() : int
	{
		return max(array_keys($this->versions));
	}
}
