<?php

declare(strict_types=1);

namespace lol;

use lol\network\NetworkSession;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v844\structure\v844ProtocolInfo;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\player\Player;

final class MultiVersionAPI {

	public const PROTOCOL_LEGACY = v84ProtocolInfo::CURRENT_PROTOCOL;
	public const PROTOCOL_1_21_114 = v844ProtocolInfo::CURRENT_PROTOCOL;

	private function __construct() {
	}

	public static function getLatestProtocol() : int {
		return ProtocolInfo::CURRENT_PROTOCOL;
	}

	public static function getSupportedProtocols() : array {
		return MCProtocols::getProtocols();
	}

	public static function isSupported(int $protocol) : bool {
		return MCProtocols::isSupported($protocol);
	}

	public static function getVersionName(int $protocol) : ?string {
		if (!MCProtocols::isSupported($protocol)) {
			return null;
		}

		return match ($protocol) {
			ProtocolInfo::CURRENT_PROTOCOL => ProtocolInfo::MINECRAFT_VERSION_NETWORK,
			v844ProtocolInfo::CURRENT_PROTOCOL => v844ProtocolInfo::MINECRAFT_VERSION_NETWORK,
			v84ProtocolInfo::CURRENT_PROTOCOL => v84ProtocolInfo::MINECRAFT_VERSION_NETWORK,
			default => null,
		};
	}

	public static function getPlayerProtocol(Player $player) : ?int {
		$session = $player->getNetworkSession();

		return $session instanceof NetworkSession ? $session->getProtocolId() : null;
	}

	public static function getPlayerVersionName(Player $player) : ?string {
		$protocol = self::getPlayerProtocol($player);

		return $protocol === null ? null : self::getVersionName($protocol);
	}

	public static function isLatest(Player $player) : bool {
		return self::getPlayerProtocol($player) === ProtocolInfo::CURRENT_PROTOCOL;
	}

	public static function isLegacy(Player $player) : bool {
		return self::getPlayerProtocol($player) === v84ProtocolInfo::CURRENT_PROTOCOL;
	}

	public static function isProtocol(Player $player, int $protocol) : bool {
		return self::getPlayerProtocol($player) === $protocol;
	}
}
