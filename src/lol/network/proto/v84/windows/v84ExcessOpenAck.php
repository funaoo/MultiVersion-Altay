<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\PacketViolationWarningPacket;

final class v84ExcessOpenAck
{

	public const UNTYPED = -1;

	private function __construct()
	{
	}

	public static function deliver(NetworkSession $session) : void
	{
		$packet = PacketViolationWarningPacket::create(self::UNTYPED, PacketViolationWarningPacket::SEVERITY_WARNING, PacketViolationWarningPacket::NETWORK_ID, '');
		(new DataPacketReceiveEvent($session, $packet))->call();
	}
}
