<?php

declare(strict_types=1);

namespace lol\network\utils;

use lol\network\NetworkSession;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;
use function assert;

final class ProtocolCommand extends Command {

	public function __construct() {
		parent::__construct("protocol", "Get the current player protocol.", "/protocol [playerName]");
		$this->setPermission(DefaultPermissions::ROOT_USER);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (empty($args[0])) {
			$sender->sendMessage("Usage: /protocol [playerName]");
			return;
		}

		$player = $sender->getServer()->getPlayerByPrefix($args[0]);

		if ($player === null || !$player->isOnline()) {
			$sender->sendMessage(TextFormat::RED . "Player not found");
			return;
		}

		$mvSession = $player->getNetworkSession();

		assert($mvSession instanceof NetworkSession);
		$protocol = $mvSession->getProtocol();
		$sender->sendMessage(TextFormat::colorize("&r&g{$player->getName()} &7is playing on protocol version: &g$protocol &7({$protocol->getProtocolId()})"));
	}
}
