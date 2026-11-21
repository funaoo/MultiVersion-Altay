<?php

declare(strict_types=1);

namespace lol\network\inventory;

use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\Packet;

final class MVWindowCloseGuard
{
	private const REJECTED_WINDOW_ID = 0xff;

	private bool $rejected = false;

	private ?int $serverClosedWindow = null;

	public function begin(Packet $packet) : void
	{
		$this->rejected = $packet instanceof ContainerClosePacket
			&& ($packet->windowId & 0xff) === self::REJECTED_WINDOW_ID;
	}

	public function end() : void
	{
		$this->rejected = false;
	}

	public function filter(ClientboundPacket $packet) : ?ClientboundPacket
	{
		if ($packet instanceof ContainerOpenPacket) {
			$this->serverClosedWindow = null;
			return $packet;
		}

		if (!$packet instanceof ContainerClosePacket) {
			return $packet;
		}

		if ($packet->server) {
			$this->serverClosedWindow = $packet->windowId;
			return $packet;
		}

		if (!$this->rejected) {
			$this->serverClosedWindow = null;
			return $packet;
		}

		if ($this->serverClosedWindow === $packet->windowId) {
			return null;
		}

		$this->serverClosedWindow = $packet->windowId;
		$packet->server = true;
		return $packet;
	}
}
