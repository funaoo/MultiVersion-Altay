<?php

declare(strict_types=1);

namespace lol\network\transport;

use altay\network\transport\TransportSession;
use pocketmine\network\mcpe\PacketSender;

final class MVTransportPacketSender implements PacketSender {

	private const MCPE_PACKET_ID = "\xfe";

	private bool $closed = false;

	public function __construct(
		private TransportSession $session,
		private MVTransportNetworkInterface $handler,
		private bool $rakNetFraming
	){}

	public function usesRakNetFraming() : bool {
		return $this->rakNetFraming;
	}

	public function send(string $payload, bool $immediate, ?int $receiptId) : void {
		if (!$this->closed) {
			$this->session->sendPacket(($this->rakNetFraming ? self::MCPE_PACKET_ID : "") . $payload, $immediate, $receiptId);
		}
	}

	public function close(string $reason = "unknown reason") : void {
		if (!$this->closed) {
			$this->closed = true;
			$this->handler->close($this->session->getId());
		}
	}
}
