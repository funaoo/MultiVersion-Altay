<?php

declare(strict_types=1);

namespace lol\network\transport;

use altay\network\transport\AddressBlockingTransport;
use altay\network\transport\NameableTransport;
use altay\network\transport\RawPacketTransport;
use altay\network\transport\Transport;
use altay\network\transport\TransportSession;
use altay\network\transport\TunableTransport;
use lol\network\diagnostic\BadPacketReportWriter;
use lol\network\NetworkSession;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\transport\TransportNetworkInterface;
use pocketmine\network\Network;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\GameMode;
use pocketmine\Server;
use pocketmine\utils\Utils;
use Throwable;
use function addcslashes;
use function base64_encode;
use function implode;
use function rtrim;
use function substr;

class MVTransportNetworkInterface extends TransportNetworkInterface {

	private const MCPE_PACKET_ID = "\xfe";

	private ?Network $mvNetwork = null;
	private bool $mvRakNetFraming;

	private array $mvSessions = [];
	private array $mvTransportSessions = [];

	public function __construct(
		private Server $mvServer,
		private Transport $mvTransport,
		private PacketBroadcaster $mvPacketBroadcaster,
		private EntityEventBroadcaster $mvEntityEventBroadcaster,
		private TypeConverter $mvTypeConverter,
		private int $mvServerId = 0
	){
		parent::__construct($mvServer, $mvTransport, $mvPacketBroadcaster, $mvEntityEventBroadcaster, $mvTypeConverter);
		$this->mvRakNetFraming = $mvTransport->getName() === "raknet";
	}

	public function setNetwork(Network $network) : void {
		parent::setNetwork($network);
		$this->mvNetwork = $network;
	}

	public function onSessionOpen(Transport $transport, TransportSession $session) : void {
		if ($this->mvNetwork === null) {
			return;
		}
		$networkSession = new NetworkSession(
			$this->mvServer,
			$this->mvNetwork->getSessionManager(),
			PacketPool::getInstance(),
			new MVTransportPacketSender($session, $this, $this->mvRakNetFraming),
			$this->mvPacketBroadcaster,
			$this->mvEntityEventBroadcaster,
			ZlibCompressor::getInstance(),
			$this->mvTypeConverter,
			$session->getAddress(),
			$session->getPort(),
			$session->getAuthenticatedPublicKey()
		);
		$this->mvSessions[$session->getId()] = $networkSession;
		$this->mvTransportSessions[$session->getId()] = $session;
	}

	public function onSessionClose(Transport $transport, TransportSession $session, string $reason) : void {
		$sessionId = $session->getId();
		if (isset($this->mvSessions[$sessionId])) {
			$networkSession = $this->mvSessions[$sessionId];
			unset($this->mvSessions[$sessionId], $this->mvTransportSessions[$sessionId]);
			$networkSession->onClientDisconnect(match ($reason) {
				"client disconnect" => KnownTranslationFactory::pocketmine_disconnect_clientDisconnect(),
				"timeout" => KnownTranslationFactory::pocketmine_disconnect_error_timeout(),
				"client reconnect" => KnownTranslationFactory::pocketmine_disconnect_clientReconnect(),
				default => "Disconnected: $reason"
			});
		}
	}

	public function close(int $sessionId) : void {
		if (isset($this->mvSessions[$sessionId])) {
			$transportSession = $this->mvTransportSessions[$sessionId];
			unset($this->mvSessions[$sessionId], $this->mvTransportSessions[$sessionId]);
			$transportSession->disconnect();
		}
	}

	public function onPacketReceive(Transport $transport, TransportSession $session, string $payload) : void {
		$sessionId = $session->getId();
		if (!isset($this->mvSessions[$sessionId])) {
			return;
		}
		if ($this->mvRakNetFraming) {
			if ($payload === "" || $payload[0] !== self::MCPE_PACKET_ID) {
				$this->mvSessions[$sessionId]->getLogger()->debug("Non-FE packet received: " . base64_encode($payload));
				return;
			}
			$buf = substr($payload, 1);
		} else {
			if ($payload === "") {
				return;
			}
			$buf = $payload;
		}
		$networkSession = $this->mvSessions[$sessionId];
		$address = $networkSession->getIp();
		$name = $networkSession->getDisplayName();
		try {
			$networkSession->handleEncoded($buf);
		} catch (PacketHandlingException $e) {
			$logger = $networkSession->getLogger();
			$errorId = NetworkSession::generateErrorId();
			$context = $networkSession->consumeBadPacketContext();

			BadPacketReportWriter::write(
				$this->mvServer,
				$networkSession,
				$context,
				$e,
				$errorId,
				$this->mvTransport->getName(),
				$this->mvRakNetFraming,
				$buf
			);

			$networkSession->disconnectWithError(
				reason: "Bad packet: " . $e->getMessage(),
				disconnectScreenMessage: KnownTranslationFactory::pocketmine_disconnect_error_badPacket(),
				errorId: $errorId
			);
			$logger->debug(implode("\n", Utils::printableExceptionInfo($e)));

			$this->blockAddress($address, 5);
		} catch (Throwable $e) {
			$this->mvServer->getLogger()->emergency("Crash occurred while handling a packet from session: $name");
			throw $e;
		}
	}

	public function onPacketAck(Transport $transport, TransportSession $session, int $receiptId) : void {
		if (isset($this->mvSessions[$session->getId()])) {
			$this->mvSessions[$session->getId()]->handleAckReceipt($receiptId);
		}
	}

	public function onPingUpdate(Transport $transport, TransportSession $session, int $pingMS) : void {
		if (isset($this->mvSessions[$session->getId()])) {
			$this->mvSessions[$session->getId()]->updatePing($pingMS);
		}
	}

	public function onRawPacketReceive(Transport $transport, string $address, int $port, string $payload) : void {
		$this->mvNetwork?->processRawPacket($this, $address, $port, $payload);
	}

	public function onBandwidthUpdate(Transport $transport, int $bytesSentDiff, int $bytesReceivedDiff) : void {
		$this->mvNetwork?->getBandwidthTracker()->add($bytesSentDiff, $bytesReceivedDiff);
	}

	public function blockAddress(string $address, int $timeout = 300) : void {
		if ($this->mvTransport instanceof AddressBlockingTransport) {
			$this->mvTransport->blockAddress($address, $timeout);
		}
	}

	public function unblockAddress(string $address) : void {
		if ($this->mvTransport instanceof AddressBlockingTransport) {
			$this->mvTransport->unblockAddress($address);
		}
	}

	public function sendRawPacket(string $address, int $port, string $payload) : void {
		if ($this->mvTransport instanceof RawPacketTransport) {
			$this->mvTransport->sendRaw($address, $port, $payload);
		}
	}

	public function addRawPacketFilter(string $regex) : void {
		if ($this->mvTransport instanceof RawPacketTransport) {
			$this->mvTransport->addRawPacketFilter($regex);
		}
	}

	public function setName(string $name) : void {
		if (!($this->mvTransport instanceof NameableTransport)) {
			return;
		}
		$info = $this->mvServer->getQueryInformation();

		$this->mvTransport->setName(implode(";",
			[
				"MCPE",
				rtrim(addcslashes($name, ";"), '\\'),
				ProtocolInfo::CURRENT_PROTOCOL,
				ProtocolInfo::MINECRAFT_VERSION_NETWORK,
				$info->getPlayerCount(),
				$info->getMaxPlayerCount(),
				$this->mvTransport->getServerId() ?? $this->mvServerId,
				$this->mvServer->getName(),
				match ($this->mvServer->getGamemode()) {
					GameMode::SURVIVAL => "Survival",
					GameMode::ADVENTURE => "Adventure",
					default => "Creative"
				}
			]) . ";"
		);
	}

	public function setPortCheck(bool $name) : void {
		if ($this->mvTransport instanceof TunableTransport) {
			$this->mvTransport->setPortCheck($name);
		}
	}

	public function setPacketLimit(int $limit) : void {
		if ($this->mvTransport instanceof TunableTransport) {
			$this->mvTransport->setPacketsPerTickLimit($limit);
		}
	}
}
