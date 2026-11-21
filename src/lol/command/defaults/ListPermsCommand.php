<?php

declare(strict_types=1);

namespace lol\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;
use function array_shift;
use function count;
use function implode;
use function sort;

final class ListPermsCommand extends Command {

	public function __construct() {
		parent::__construct("listperms", "Lists a player effective permissions.", "/listperms [player]", ["listpermissions"]);
		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		$target = $sender;
		if (count($args) > 0) {
			$targetName = array_shift($args);
			$target = $sender->getServer()->getPlayerByPrefix($targetName);
			if ($target === null) {
				$sender->sendMessage(TextFormat::RED . "Can't find player " . $targetName);
				return true;
			}
		}

		$perms = [];
		foreach ($target->getEffectivePermissions() as $info) {
			$perms[] = $info->getPermission() . ": " . ($info->getValue() ? "true" : "false");
		}
		sort($perms);

		$sender->sendMessage(TextFormat::GREEN . "Effective permissions for " . $target->getName() . ":\n" . TextFormat::RESET . implode("\n", $perms));

		return true;
	}
}
