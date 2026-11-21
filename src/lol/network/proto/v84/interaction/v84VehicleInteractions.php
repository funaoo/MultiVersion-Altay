<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

final class v84VehicleInteractions implements v84VehicleActions
{

	private const MAX_REACH_SQUARED = 64.0;

	private const VEHICLE_TYPES = [
		EntityIds::BOAT => true,
		EntityIds::MINECART => true,
	];

	public function __construct(private readonly Player $player)
	{
	}

	public function mount(int $vehicleId) : void
	{
		$vehicle = $this->reachableVehicle($vehicleId);
		if ($vehicle === null || $vehicle->getPassengers() !== []) {
			return;
		}

		$vehicle->addPassenger($this->player);
	}

	public function breakVehicle(int $vehicleId) : void
	{
		if (!$this->player->isAlive() || !$this->player->isConnected()) {
			return;
		}

		$entity = $this->player->getWorld()->getEntity($vehicleId);
		if ($entity === null || $entity === $this->player) {
			return;
		}

		if ($this->isUsableVehicle($entity)) {
			$this->reachableVehicle($vehicleId)?->flagForDespawn();
			return;
		}

		$this->player->attackEntity($entity);
	}

	public function dismount() : void
	{
		if (!$this->player->isAlive() || !$this->player->isConnected()) {
			return;
		}

		$this->player->getVehicle()?->removePassenger($this->player);
	}

	private function reachableVehicle(int $vehicleId) : ?Entity
	{
		if (!$this->player->isAlive() || !$this->player->isConnected() || $this->player->isSpectator()) {
			return null;
		}

		$entity = $this->player->getWorld()->getEntity($vehicleId);
		if ($entity === null || !$this->isUsableVehicle($entity)) {
			return null;
		}

		return $entity->getLocation()->distanceSquared($this->player->getLocation()) <= self::MAX_REACH_SQUARED ? $entity : null;
	}

	private function isUsableVehicle(Entity $entity) : bool
	{
		return isset(self::VEHICLE_TYPES[$entity::getNetworkTypeId()])
			&& $entity->isAlive()
			&& !$entity->isClosed()
			&& !$entity->isFlaggedForDespawn();
	}
}
