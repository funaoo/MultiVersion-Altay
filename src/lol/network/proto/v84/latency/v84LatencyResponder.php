<?php

declare(strict_types=1);

namespace lol\network\proto\v84\latency;

use Closure;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;

final class v84LatencyResponder
{

	public const RESPONSE_DELAY_TICKS = 1;

	public const TIMESTAMP_MULTIPLIER = 1000000;

	public function __construct(private readonly Closure $later, private readonly Closure $deliver)
	{
	}

	public static function deliver(NetworkSession $session, int $timestamp) : void
	{
		(new DataPacketReceiveEvent($session, NetworkStackLatencyPacket::response($timestamp)))->call();
	}

	public function answer(NetworkSession $session, NetworkStackLatencyPacket $packet) : void
	{
		if (!$packet->needResponse) {
			return;
		}

		$deliver = $this->deliver;
		$timestamp = $packet->timestamp * self::TIMESTAMP_MULTIPLIER;
		($this->later)(static function () use ($session, $deliver, $timestamp) : void {
			if ($session->isConnected()) {
				$deliver($session, $timestamp);
			}
		}, self::RESPONSE_DELAY_TICKS);
	}
}
