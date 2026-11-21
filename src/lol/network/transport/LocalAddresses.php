<?php

declare(strict_types=1);

namespace lol\network\transport;

use function array_values;
use function fclose;
use function filter_var;
use function in_array;
use function is_string;
use function net_get_interfaces;
use function stream_socket_server;
use const FILTER_FLAG_IPV4;
use const FILTER_VALIDATE_IP;
use const STREAM_SERVER_BIND;

final class LocalAddresses {

	public const WILDCARD = "0.0.0.0";

	private function __construct() {}

	public static function isWildcard(string $address) : bool {
		return in_array($address, ["", self::WILDCARD, "::", "[::]"], true);
	}

	public static function isBindable(string $address, int $port = 0) : bool {
		$socket = @stream_socket_server("udp://$address:$port", $errno, $errstr, STREAM_SERVER_BIND);
		if ($socket === false) {
			return false;
		}
		fclose($socket);
		return true;
	}

	public static function bindable() : array {
		$addresses = [];
		foreach (net_get_interfaces() ?: [] as $interface) {
			foreach ($interface["unicast"] ?? [] as $unicast) {
				$address = $unicast["address"] ?? null;
				if (is_string($address) && filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false && self::isBindable($address)) {
					$addresses[$address] = $address;
				}
			}
		}
		return array_values($addresses);
	}

	public static function resolveBindAddress(string $configured) : string {
		return self::isWildcard($configured) || self::isBindable($configured) ? $configured : self::WILDCARD;
	}
}
