<?php

declare(strict_types=1);

namespace lol\network\utils;

use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use function class_exists;
use function dirname;
use function is_dir;
use function is_file;
use function pathinfo;
use function scandir;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

final class ProtocolUtils
{

	private function __construct() {

	}

	static public function registerPackets(PacketPool $pool, int $protocol) : void{
		$phar = \Phar::running();
		$packetDir = ($phar === "" ? dirname(__DIR__) : "$phar/src/lol/network") . "/proto/v$protocol/packets/";
		if (!is_dir($packetDir)) throw new AssumptionFailedError("packets directory does not exist");
		$namespace = "lol\\network\\proto\\v$protocol\\packets\\";

		foreach (self::scanAndGetPackets($namespace, $packetDir) as $packet) {
			if(!$packet instanceof Packet) {
				\GlobalLogger::get()->debug("Ignoring non-packet " . ReflectionUtils::invokeStatic(Utils::class, "stringifyValueForTrace", $packet, 2048));
				continue;
			}

			$pool->registerPacket($packet);
		}
	}

	static private function scanAndGetPackets(string $namespace, string $packetDir) : \Generator {
		foreach (Utils::assumeNotFalse(scandir($packetDir)) as $file) {
			if ($file === "." || $file === ".." || pathinfo($file, PATHINFO_EXTENSION) !== "php") {
				continue;
			}

			$className = $namespace . pathinfo($file, PATHINFO_FILENAME);

			if (!class_exists($className)) {
				continue;
			}

			yield new $className();
		}
	}

	public static function loadCacheableFromFile(string $path) : CacheableNbt {
		if(!is_file($path)) throw new \InvalidArgumentException("file $path does not exist");
		$rawNbt = Filesystem::fileGetContents($path);
		return new CacheableNbt((new NetworkNbtSerializer())->read($rawNbt)->mustGetCompoundTag());
	}

}
