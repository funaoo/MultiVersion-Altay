<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\biome;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;

final class v844BiomeClimateData
{
	public function __construct(
		private float $temperature,
		private float $downfall,
		private float $snowAccumulationMin,
		private float $snowAccumulationMax,
	)
	{
	}

	public static function read(ByteBufferReader $in) : self
	{
		$temperature = LE::readFloat($in);
		$downfall = LE::readFloat($in);
		$snowAccumulationMin = LE::readFloat($in);
		$snowAccumulationMax = LE::readFloat($in);

		return new self(
			$temperature,
			$downfall,
			$snowAccumulationMin,
			$snowAccumulationMax
		);
	}

	public function getTemperature() : float
	{
		return $this->temperature;
	}

	public function getDownfall() : float
	{
		return $this->downfall;
	}

	public function getSnowAccumulationMin() : float
	{
		return $this->snowAccumulationMin;
	}

	public function getSnowAccumulationMax() : float
	{
		return $this->snowAccumulationMax;
	}

	public function write(ByteBufferWriter $out) : void
	{
		LE::writeFloat($out, $this->temperature);
		LE::writeFloat($out, $this->downfall);
		LE::writeFloat($out, $this->snowAccumulationMin);
		LE::writeFloat($out, $this->snowAccumulationMax);
	}
}
