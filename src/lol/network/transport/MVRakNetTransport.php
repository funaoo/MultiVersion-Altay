<?php

declare(strict_types=1);

namespace lol\network\transport;

use altay\network\raknet\generic\SocketException;
use altay\network\raknet\NullServerEventSource;
use altay\network\raknet\RakNetSession;
use altay\network\raknet\server\Server;
use altay\network\raknet\server\ServerSession;
use altay\network\raknet\server\ServerSocket;
use altay\network\raknet\utils\ExceptionTraceCleaner;
use altay\network\raknet\utils\InternetAddress;
use altay\network\transport\AddressBlockingTransport;
use altay\network\transport\NameableTransport;
use altay\network\transport\RawPacketTransport;
use altay\network\transport\TransportException;
use altay\network\transport\TransportListener;
use altay\network\transport\TunableTransport;
use Logger;
use function mt_rand;
use const PHP_INT_MAX;

final class MVRakNetTransport implements NameableTransport, RawPacketTransport, AddressBlockingTransport, TunableTransport {

	private ?Server $server = null;
	private ?MVRakNetEventListener $eventListener = null;

	public function __construct(
		private Logger $logger,
		private string $bindAddress,
		private int $port,
		private bool $ipv6,
		private int $maxMtuSize,
		private array $protocolVersions,
		private ?int $serverId,
		private int $recvMaxSplitParts = ServerSession::DEFAULT_MAX_SPLIT_PART_COUNT,
		private int $recvMaxConcurrentSplits = ServerSession::DEFAULT_MAX_CONCURRENT_SPLIT_COUNT
	){}

	public function getName() : string {
		return "raknet";
	}

	public function start(TransportListener $listener) : void {
		if ($this->server !== null) {
			throw new TransportException("RakNet transport is already running");
		}
		$this->eventListener = new MVRakNetEventListener($this, $listener);
		try {
			$socket = new ServerSocket(new InternetAddress($this->bindAddress, $this->port, $this->ipv6 ? 6 : 4));
		} catch (SocketException $e) {
			$this->eventListener = null;
			throw new TransportException("Failed to start RakNet transport: " . $e->getMessage(), 0, $e);
		}
		$this->server = new Server(
			$this->serverId ?? mt_rand(0, PHP_INT_MAX),
			$this->logger,
			$socket,
			$this->maxMtuSize,
			new MVRakNetAcceptor($this->protocolVersions),
			new NullServerEventSource(),
			$this->eventListener,
			new ExceptionTraceCleaner(\pocketmine\PATH),
			$this->recvMaxSplitParts,
			$this->recvMaxConcurrentSplits
		);
		$this->eventListener->setServer($this->server);
	}

	public function tick() : void {
		$this->server?->tickProcessor();
	}

	public function isSelfPacing() : bool {
		return true;
	}

	public function isRunning() : bool {
		return $this->server !== null;
	}

	public function shutdown() : void {
		if ($this->server !== null) {
			$this->server->waitShutdown();
			$this->server = null;
			$this->eventListener = null;
		}
	}

	public function getSession(int $sessionId) : ?RakNetSession {
		return $this->eventListener?->getSession($sessionId);
	}

	public function getServerId() : ?int {
		return $this->server?->getID();
	}

	public function setName(string $name) : void {
		$this->server?->setName($name);
	}

	public function sendRaw(string $address, int $port, string $payload) : void {
		$this->server?->sendRaw($address, $port, $payload);
	}

	public function blockAddress(string $address, int $timeout = 300) : void {
		$this->server?->blockAddress($address, $timeout);
	}

	public function unblockAddress(string $address) : void {
		$this->server?->unblockAddress($address);
	}

	public function setPortCheck(bool $value) : void {
		$this->server?->setPortCheck($value);
	}

	public function setPacketsPerTickLimit(int $limit) : void {
		$this->server?->setPacketsPerTickLimit($limit);
	}

	public function addRawPacketFilter(string $regex) : void {
		$this->server?->addRawPacketFilter($regex);
	}
}
