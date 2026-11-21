<?php

declare(strict_types=1);

namespace lol\network\transport;

use altay\network\transport\Transport;
use Logger;
use pocketmine\network\mcpe\transport\TransportFactory;

final class MVRakNetTransportFactory implements TransportFactory {

	public function __construct(
		private string $ip,
		private int $port,
		private bool $ipV6,
		private int $maxMtuSize,
		private int $serverId,
		private array $protocolVersions,
		private int $maxSplitPacketParts,
		private int $maxConcurrentSplitPackets
	){}

	public function getName() : string {
		return "raknet";
	}

	public function getServerId() : int {
		return $this->serverId;
	}

	public function make(Logger $logger) : Transport {
		return new MVRakNetTransport(
			$logger,
			$this->ip,
			$this->port,
			$this->ipV6,
			$this->maxMtuSize,
			$this->protocolVersions,
			$this->serverId,
			$this->maxSplitPacketParts,
			$this->maxConcurrentSplitPackets
		);
	}
}
