<?php

declare(strict_types=1);

namespace lol;

use InvalidArgumentException;
use altay\network\raknet\server\ProtocolAcceptor;
use lol\network\proto\latest\LatestProtocol;
use lol\network\proto\TProtocol;
use lol\network\proto\v844\v844Protocol;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v84\v84Protocol;
use lol\network\transport\MVRakNetAcceptor;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use Logger;
use Throwable;
use function array_diff;
use function array_fill_keys;
use function array_keys;
use function array_push;
use function array_unique;
use function array_values;
use function constant;
use function defined;
use function ctype_digit;
use function is_int;
use function is_string;
use function method_exists;
use function microtime;
use function round;
use function strrpos;
use function substr;

final class MCProtocols {

	private const REGISTRY = [
		ProtocolInfo::CURRENT_PROTOCOL => LatestProtocol::class,
		844 => v844Protocol::class,
		v84ProtocolInfo::CURRENT_PROTOCOL => v84Protocol::class,
	];

	private static array $instances = [];

	private static ?array $enabled = null;

	public static function configure(array $disabled, array $allowed, Logger $logger) : void {
		$disabledIds = self::normalize($disabled, "disabled-protocols", $logger);
		$allowedIds = self::normalize($allowed, "allowed-protocols", $logger);

		if ($allowed !== [] && $allowedIds === []) {
			$logger->warning("allowed-protocols has no valid entries, so it is being ignored");
		}

		$enabled = [];
		foreach (array_keys(self::REGISTRY) as $protocol) {
			if ($allowedIds !== [] && !isset($allowedIds[$protocol])) {
				continue;
			}
			if (isset($disabledIds[$protocol])) {
				continue;
			}
			$enabled[$protocol] = true;
		}

		if (!isset($enabled[ProtocolInfo::CURRENT_PROTOCOL])) {
			$enabled[ProtocolInfo::CURRENT_PROTOCOL] = true;
			$logger->warning("The native protocol " . ProtocolInfo::CURRENT_PROTOCOL . " cannot be disabled, it stays enabled");
		}

		self::$enabled = $enabled;
	}

	private static function normalize(array $values, string $key, Logger $logger) : array {
		$ids = [];
		foreach ($values as $value) {
			if (is_int($value) || (is_string($value) && ctype_digit($value))) {
				$protocol = (int) $value;
			} else {
				$logger->warning("Ignoring invalid entry in $key, protocol numbers are expected");
				continue;
			}

			if (!isset(self::REGISTRY[$protocol])) {
				$logger->warning("Ignoring unknown protocol $protocol in $key");
				continue;
			}

			$ids[$protocol] = true;
		}

		return $ids;
	}

	public static function isEnabled(int $protocol) : bool {
		return self::$enabled === null || isset(self::$enabled[$protocol]);
	}

	public static function disable(int $protocol) : void {
		$enabled = self::$enabled ?? array_fill_keys(array_keys(self::REGISTRY), true);
		unset($enabled[$protocol]);
		self::$enabled = $enabled;
	}

	public static function isSupported(int $protocol) : bool {
		return isset(self::REGISTRY[$protocol]) && self::isEnabled($protocol);
	}

	public static function getProtocolInstance(int $protocol) : TProtocol {
		if (isset(self::$instances[$protocol])) {
			return self::$instances[$protocol];
		}

		$class = self::REGISTRY[$protocol] ?? throw new InvalidArgumentException("Unsupported protocol: " . $protocol);

		return self::$instances[$protocol] = new $class();
	}

	public static function preload() : array {
		$protocols = [];
		foreach (self::getProtocols() as $protocol) {
			$protocols[$protocol] = self::getProtocolInstance($protocol);
		}

		return $protocols;
	}

	public static function warmupConverters(Logger $logger) : void {
		$start = microtime(true);
		$done = 0;
		foreach (self::preload() as $protocol => $instance) {
			try {
				$instance->getTypeConverter();
				$done++;
			} catch (Throwable $e) {
				$logger->error("Could not preload the type converter of protocol $protocol: " . $e->getMessage());
			}
		}

		$logger->info("Preloaded $done type converters in " . round((microtime(true) - $start) * 1000) . " ms");
	}

	public static function warmupCaches(Logger $logger) : void {
		$start = microtime(true);
		$done = 0;
		foreach (self::preload() as $protocol => $instance) {
			try {
				if (method_exists($instance, "getStaticPacketCache")) {
					$instance->getStaticPacketCache();
				}
				$instance->outcoming(new CraftingDataPacket());
				$done++;
			} catch (Throwable $e) {
				$logger->error("Could not preload the packet cache of protocol $protocol: " . $e->getMessage());
			}
		}

		$logger->info("Preloaded $done packet caches in " . round((microtime(true) - $start) * 1000) . " ms");
	}

	public static function getProtocols() : array {
		return array_keys(self::$enabled ?? self::REGISTRY);
	}

	public static function getVersionLabel(int $protocol) : string {
		$class = self::REGISTRY[$protocol] ?? null;
		if ($class === null) {
			return "unknown";
		}

		$constant = substr($class, 0, (int) strrpos($class, "\\")) . "\\structure\\v" . $protocol . "ProtocolInfo::MINECRAFT_VERSION_NETWORK";

		return defined($constant) ? "v" . constant($constant) : "unknown";
	}

	public static function getDisabledProtocols() : array {
		return self::$enabled === null ? [] : array_diff(array_keys(self::REGISTRY), array_keys(self::$enabled));
	}

	public static function getRaknetProtocolAcceptor() : ProtocolAcceptor {
		return new MVRakNetAcceptor(self::getRaknetProtocolVersions());
	}

	public static function getRaknetProtocolVersions() : array {
		$versions = [LatestProtocol::RAKNET_VERSION];
		if (self::isEnabled(v84ProtocolInfo::CURRENT_PROTOCOL)) {
			array_push($versions, ...v84Protocol::ACCEPTED_RAKNET_VERSIONS);
		}

		return array_values(array_unique($versions));
	}
}
