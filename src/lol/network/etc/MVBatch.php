<?php

declare(strict_types=1);

namespace lol\network\etc;

use lol\network\async\MVCompressBatchTask;
use lol\network\proto\TProtocol;
use lol\network\utils\ReflectionUtils;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;
use function chr;
use function strlen;

final class MVBatch {

	private function __construct() {
	}

	public static function prepareBatch(string $buffer, TProtocol $protocol, Compressor $compressor, ?bool $sync = null, ?TimingsHandler $timings = null) : CompressBatchPromise|string {
		$server = Server::getInstance();

		$timings ??= Timings::$playerNetworkSendCompress;
		$timings->startTiming();
		try {
			$threshold = $compressor->getCompressionThreshold();
			if ($threshold === null || strlen($buffer) < $threshold && !$protocol->hasOldCompressionMethod()) {
				$compressionType = CompressionAlgorithm::NONE;
				$compressed = $buffer;
			} else {
				$sync ??= !ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsync");

				if (!$sync && strlen($buffer) >= ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsyncThreshold")) {
					$promise = new CompressBatchPromise();
					$server->getAsyncPool()->submitTask(
						new MVCompressBatchTask($buffer, $promise, $compressor, $protocol->hasOldCompressionMethod())
					);
					return $promise;
				}

				$compressionType = $compressor->getNetworkId();
				$compressed = $compressor->compress($buffer);
			}

			return (!$protocol->hasOldCompressionMethod() ? chr($compressionType) : '') . $compressed;
		} finally {
			$timings->stopTiming();
		}
	}
}
