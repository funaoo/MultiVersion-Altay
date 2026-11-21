<?php

declare(strict_types=1);

namespace lol\network\global;

use lol\network\etc\MVBatch;
use lol\network\NetworkSession;
use lol\network\proto\TProtocol;
use lol\network\utils\TranslationGuard;
use InvalidArgumentException;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\timings\Timings;
use Throwable;
use function count;
use function log;
use function spl_object_id;
use function strlen;

class MVPacketBroadcaster implements PacketBroadcaster
{
	public function __construct(
		private TProtocol $protocol,
	)
	{

	}

	public function broadcastPackets(array $recipients, array $packets) : void
	{
		if ($this->protocol->getFrameCodec() !== null) {
			foreach ($recipients as $recipient) {
				foreach ($packets as $packet) {
					$recipient->sendDataPacket($packet);
				}
			}
			return;
		}

		if (DataPacketSendEvent::hasHandlers()) {
			$ev = new DataPacketSendEvent($recipients, $packets);
			$ev->call();

			if ($ev->isCancelled()) {
				return;
			}

			$packets = $ev->getPackets();
		}

		$compressors = [];

		$targetsByCompressor = [];

		$protocol = $this->protocol;
		foreach ($recipients as $recipient) {
			if ($recipient->safeProtocol() !== $protocol) {
				throw new InvalidArgumentException("Only recipients with the same protocol context as the broadcaster can be broadcast to by this broadcaster");
			}

			$compressor = $recipient->getCompressor();
			$compressors[spl_object_id($compressor)] = $compressor;

			$targetsByCompressor[spl_object_id($compressor)][] = $recipient;
		}

		$totalLength = 0;
		$packetBuffers = [];
		$writer = new ByteBufferWriter();
		foreach ($packets as $packet) {
			$writer->clear();
			try {
				$pk = $protocol->outcoming(clone $packet);
				$buffer = $pk === null ? null : NetworkSession::encodePacketTimed($writer, $pk);
			} catch (Throwable $e) {
				TranslationGuard::report($protocol->getLogger(), $packet, $e);
				continue;
			}

			if ($buffer === null) {
				$protocol->getLogger()->debug("Ignoring packet {$packet->getName()} to be broadcasted");
				continue;
			}

			$totalLength += (((int) log(strlen($buffer), 128)) + 1) + strlen($buffer);
			$packetBuffers[] = $buffer;
		}

		foreach ($targetsByCompressor as $compressorId => $compressorTargets) {
			$compressor = $compressors[$compressorId];

			$threshold = $compressor->getCompressionThreshold();
			if (count($compressorTargets) > 1 && $threshold !== null && $totalLength >= $threshold) {
				$stream = new ByteBufferWriter();
				PacketBatch::encodeRaw($stream, $packetBuffers);

				$promise = MVBatch::prepareBatch($stream->getData(), $protocol, $compressor, timings: Timings::$playerNetworkSendCompressBroadcast);
				foreach ($compressorTargets as $target) {
					$target->queueCompressed($promise);
				}
			} else {
				foreach ($compressorTargets as $target) {
					foreach ($packetBuffers as $packetBuffer) {
						$target->addToSendBuffer($packetBuffer);
					}
				}
			}
		}
	}

}
