<?php

declare(strict_types=1);

namespace lol\network\proto\v84\tiles;

use pocketmine\block\Chest;
use pocketmine\block\tile\Spawnable;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\ChestPairEvent;
use pocketmine\event\EventPriority;
use pocketmine\math\Facing;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use function count;

final class v84ChestPairSync
{
	private const DELAY_TICKS = 2;

	private function __construct()
	{
	}

	public static function register(Plugin $plugin) : void
	{
		$manager = $plugin->getServer()->getPluginManager();

		$manager->registerEvent(ChestPairEvent::class, static function (ChestPairEvent $event) use ($plugin) : void {
			$positions = [$event->getLeft()->getPosition(), $event->getRight()->getPosition()];
			$plugin->getLogger()->debug('[ChestPair] pairing ' . self::describe($positions[0]) . ' with ' . self::describe($positions[1]));
			self::later($plugin, static function () use ($positions, $plugin) : void {
				foreach ($positions as $position) {
					self::resend($position, $plugin);
				}
			});
		}, EventPriority::MONITOR, $plugin, false);

		$manager->registerEvent(BlockBreakEvent::class, static function (BlockBreakEvent $event) use ($plugin) : void {
			$block = $event->getBlock();
			if (!$block instanceof Chest) {
				return;
			}

			$origin = $block->getPosition();
			$neighbours = [];
			foreach ([Facing::NORTH, Facing::SOUTH, Facing::WEST, Facing::EAST] as $face) {
				$neighbours[] = Position::fromObject($origin->getSide($face), $origin->getWorld());
			}

			self::later($plugin, static function () use ($neighbours, $plugin) : void {
				foreach ($neighbours as $position) {
					self::resend($position, $plugin);
				}
			});
		}, EventPriority::MONITOR, $plugin, false);
	}

	private static function later(Plugin $plugin, \Closure $task) : void
	{
		$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask($task), self::DELAY_TICKS);
	}

	private static function describe(Position $position) : string
	{
		return $position->getFloorX() . ' ' . $position->getFloorY() . ' ' . $position->getFloorZ();
	}

	private static function resend(Position $position, Plugin $plugin) : void
	{
		$world = $position->getWorld();
		if (!$world->isLoaded() || !$world->getBlock($position) instanceof Chest) {
			return;
		}

		$tile = $world->getTileAt($position->getFloorX(), $position->getFloorY(), $position->getFloorZ());
		if (!$tile instanceof Spawnable) {
			return;
		}

		$packet = BlockActorDataPacket::create(BlockPosition::fromVector3($position), $tile->getSerializedSpawnCompound());
		$viewers = $world->getViewersForPosition($position);
		$plugin->getLogger()->debug('[ChestPair] resending tile ' . self::describe($position) . ' to ' . count($viewers) . ' viewer(s)');
		foreach ($viewers as $viewer) {
			$viewer->getNetworkSession()->sendDataPacket($packet);
		}
	}
}