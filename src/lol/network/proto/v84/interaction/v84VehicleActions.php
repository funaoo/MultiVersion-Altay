<?php

declare(strict_types=1);

namespace lol\network\proto\v84\interaction;

interface v84VehicleActions
{

	public function mount(int $vehicleId) : void;

	public function breakVehicle(int $vehicleId) : void;

	public function dismount() : void;
}
