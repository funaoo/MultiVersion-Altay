<?php

declare(strict_types=1);

namespace lol\network\transport;

use altay\network\nethernet\NetherNetTransport;
use pocketmine\network\mcpe\transport\NetherNetTransportFactory;
use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\ServerProperties;
use pocketmine\utils\Binary;
use pocketmine\utils\TextFormat;
use pocketmine\YmlServerProperties as Yml;
use Symfony\Component\Filesystem\Path;
use ReflectionMethod;
use function array_intersect_key;
use function count;
use function ctype_digit;
use function explode;
use function file_exists;
use function hash;
use function is_array;
use function is_bool;
use function is_string;
use function strtolower;
use function substr;
use function trim;

final class MVNetherNetSettings {

	private function __construct() {}

	public static function createFactory(Server $server, Config $plugin) : NetherNetTransportFactory {
		$config = $server->getConfigGroup();
		$port = $server->getPort();
		$bindAddress = self::getBindAddress($server);
		$useQuery = $config->getConfigBool(ServerProperties::ENABLE_QUERY, true);

		[$requireIdentity, $requireEndpointIdentity] = self::getIdentityPolicy($server);

		$signallingPort = $config->getPropertyInt(Yml::NETWORK_NETHERNET_SIGNALLING_PORT, 0);
		if ($signallingPort < 1 || $signallingPort > 65535) {
			$signallingPort = $port;
		}

		$udpPort = self::getUdpPort($server, $port, $useQuery);
		$interfaces = self::getInterfaces($server, $bindAddress, $udpPort);

		$arguments = [
			"networkId" => Binary::readLLong(substr(hash("sha256", $server->getServerUniqueId()->getBytes(), true), 0, 8)),
			"motd" => $server->getMotd(),
			"levelName" => $server->getName(),
			"maxPlayerCount" => $server->getMaxPlayers(),
			"bindAddress" => $bindAddress,
			"port" => NetherNetTransport::DISCOVERY_PORT,
			"onlineMode" => $server->getOnlineMode(),
			"signallingPort" => $signallingPort,
			"identityKeyPath" => Path::join($server->getDataPath(), "identity.key"),
			"identityDomain" => self::getIdentityDomain($server),
			"requireIdentity" => $requireIdentity,
			"requireEndpointIdentity" => $requireEndpointIdentity,
			"iceServers" => self::getIceServers($server, $plugin),
			"iceUsername" => self::getIceText($config->getPropertyString(Yml::NETWORK_NETHERNET_ICE_USERNAME, ""), $plugin, "ice-username"),
			"icePassword" => self::getIceText($config->getPropertyString(Yml::NETWORK_NETHERNET_ICE_PASSWORD, ""), $plugin, "ice-password"),
			"relayOnly" => $config->getPropertyBool(Yml::NETWORK_NETHERNET_RELAY_ONLY, false) || (bool) $plugin->get("ice-relay-only", false),
			"iceInterfaces" => $interfaces,
			"advertisedAddresses" => self::getList($server, Yml::NETWORK_NETHERNET_ADVERTISE_ADDRESSES, "address"),
			"udpPortRange" => self::getUdpPortRange($server),
			"tlsCertificatePath" => self::getTlsPath($server, Yml::NETWORK_NETHERNET_TLS_CERTIFICATE),
			"tlsKeyPath" => self::getTlsPath($server, Yml::NETWORK_NETHERNET_TLS_KEY),
			"maxPendingNegotiations" => self::getLimit($server, Yml::NETWORK_NETHERNET_MAX_PENDING_NEGOTIATIONS, 64),
			"maxNegotiationsPerAddress" => self::getLimit($server, Yml::NETWORK_NETHERNET_MAX_NEGOTIATIONS_PER_ADDRESS, 32),
			"verboseLogging" => $config->getPropertyBool(Yml::NETWORK_NETHERNET_VERBOSE_LOGGING, false),
			"handshakeTimeout" => self::getLimit($server, Yml::NETWORK_NETHERNET_HANDSHAKE_TIMEOUT, NetherNetTransport::DEFAULT_HANDSHAKE_TIMEOUT),
			"udpPort" => $udpPort,
			"udpPortRequired" => false
		];

		$supported = [];
		foreach ((new ReflectionMethod(NetherNetTransportFactory::class, "__construct"))->getParameters() as $parameter) {
			$supported[$parameter->getName()] = true;
		}

		if ($udpPort !== null && !isset($supported["udpPortRequired"]) && !self::canShareUdpPort($server, $udpPort, $interfaces ?: [$bindAddress])) {
			$arguments["udpPort"] = null;
		}

		return new NetherNetTransportFactory(...array_intersect_key($arguments, $supported));
	}

	private static function canShareUdpPort(Server $server, int $udpPort, array $interfaces) : bool {
		foreach ($interfaces as $interface) {
			if (!LocalAddresses::isBindable($interface, $udpPort)) {
				$server->getLogger()->warning("[MultiVersion] udp/$udpPort is not available on $interface, giving every player a media port of their own instead");
				return false;
			}
		}
		return true;
	}

	public static function getBindAddress(Server $server) : string {
		$configured = $server->getIp();
		$resolved = LocalAddresses::resolveBindAddress($configured);
		if ($resolved !== $configured) {
			$server->getLogger()->warning("[MultiVersion] server-ip $configured is not an address of this machine, listening on $resolved instead");
		}
		return $resolved;
	}

	private static function getInterfaces(Server $server, string $bindAddress, ?int $udpPort) : array {
		$configured = self::getList($server, Yml::NETWORK_NETHERNET_INTERFACES, "address");
		if ($configured !== [] || $udpPort === null || !LocalAddresses::isWildcard($bindAddress)) {
			return $configured;
		}
		return LocalAddresses::bindable();
	}

	private static function getIceServers(Server $server, Config $plugin) : array {
		$configured = self::getList($server, Yml::NETWORK_NETHERNET_ICE_SERVERS, "URL");
		return $configured !== [] ? $configured : self::sanitizeList($server, $plugin->get("ice-servers", []), "ice-servers", "URL");
	}

	private static function getIceText(string $configured, Config $plugin, string $key) : string {
		return $configured !== "" ? $configured : (string) $plugin->get($key, "");
	}

	private static function getList(Server $server, string $property, string $kind) : array {
		return self::sanitizeList($server, $server->getConfigGroup()->getProperty($property, []), $property, $kind);
	}

	private static function sanitizeList(Server $server, mixed $configured, string $property, string $kind) : array {
		if (!is_array($configured)) {
			$server->getLogger()->warning("Ignoring $property, it must be a list of {$kind}s");
			return [];
		}

		$entries = [];
		foreach ($configured as $entry) {
			if (!is_string($entry) || $entry === "") {
				$server->getLogger()->warning("Ignoring an entry of $property, it is not a $kind");
				continue;
			}
			$entries[] = $entry;
		}
		return $entries;
	}

	private static function getIdentityPolicy(Server $server) : array {
		$configured = $server->getConfigGroup()->getProperty(Yml::NETWORK_NETHERNET_REQUIRE_IDENTITY, "auto");
		if (is_bool($configured)) {
			return [$configured, $configured];
		}
		if (!is_string($configured) || strtolower($configured) !== "auto") {
			$server->getLogger()->warning("Ignoring " . Yml::NETWORK_NETHERNET_REQUIRE_IDENTITY . ", expected true, false or auto");
		}
		return [$server->getOnlineMode(), $server->getOnlineMode()];
	}

	private static function getIdentityDomain(Server $server) : string {
		$configured = $server->getConfigGroup()->getPropertyString(Yml::NETWORK_NETHERNET_IDENTITY_DOMAIN, "");
		return $configured !== "" ? $configured : TextFormat::clean($server->getMotd());
	}

	private static function getUdpPortRange(Server $server) : ?array {
		$configured = $server->getConfigGroup()->getPropertyString(Yml::NETWORK_NETHERNET_UDP_PORT_RANGE, "");
		if ($configured === "") {
			return null;
		}
		$parts = explode("-", $configured, 2);
		if (count($parts) !== 2 || !ctype_digit(trim($parts[0])) || !ctype_digit(trim($parts[1]))) {
			$server->getLogger()->warning("Ignoring " . Yml::NETWORK_NETHERNET_UDP_PORT_RANGE . ", expected a range such as \"30000-30999\"");
			return null;
		}
		return [(int) trim($parts[0]), (int) trim($parts[1])];
	}

	private static function getUdpPort(Server $server, int $serverPort, bool $useQuery) : ?int {
		$configured = $server->getConfigGroup()->getPropertyInt(Yml::NETWORK_NETHERNET_UDP_PORT, 0);
		if ($configured === 0) {
			return null;
		}
		if ($configured < 1 || $configured > 65535) {
			$server->getLogger()->warning("Ignoring " . Yml::NETWORK_NETHERNET_UDP_PORT . ", it must be between 1 and 65535");
			return null;
		}
		if ($configured === $serverPort && $useQuery) {
			$server->getLogger()->warning("Ignoring " . Yml::NETWORK_NETHERNET_UDP_PORT . ", query already listens on udp/$serverPort. Pick another port or turn enable-query off");
			return null;
		}
		return $configured;
	}

	private static function getTlsPath(Server $server, string $property) : ?string {
		$configured = $server->getConfigGroup()->getPropertyString($property, "");
		if ($configured === "") {
			return null;
		}
		$path = Path::isAbsolute($configured) ? $configured : Path::join($server->getDataPath(), $configured);
		if (!file_exists($path)) {
			$server->getLogger()->warning("Ignoring $property, there is no file at $path");
			return null;
		}
		return $path;
	}

	private static function getLimit(Server $server, string $property, int $default) : int {
		$configured = $server->getConfigGroup()->getPropertyInt($property, $default);
		if ($configured < 1) {
			$server->getLogger()->warning("Ignoring $property, it must be at least 1");
			return $default;
		}
		return $configured;
	}
}
