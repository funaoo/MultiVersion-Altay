<?php

declare(strict_types=1);

namespace lol\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;
use function array_shift;
use function count;

final class CheckPermCommand extends Command {

	public function __construct() {
		parent::__construct("checkperm", "Checks if a player has a certain permission node.", "/checkperm <permission> [player]");
		$this->setPermission(DefaultPermissions::ROOT_OPERATOR);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : bool {
		if (count($args) === 0) {
			throw new InvalidCommandSyntaxException();
		}

		$permission = array_shift($args);

		$target = $sender;
		if (count($args) > 0) {
			$targetName = array_shift($args);
			$target = $sender->getServer()->getPlayerByPrefix($targetName);
			if ($target === null) {
				$sender->sendMessage(TextFormat::RED . "Can't find player " . $targetName);
				return true;
			}
		}

		$hasPermission = $target->hasPermission($permission);
		$sender->sendMessage(TextFormat::colorize("&r&g" . $target->getName() . "&r has permission &b" . $permission . "&r: " . ($hasPermission ? "&atrue" : "&cfalse")));

		return true;
	}
}
