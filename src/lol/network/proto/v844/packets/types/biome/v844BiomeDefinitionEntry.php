<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\biome;

use pocketmine\color\Color;

final class v844BiomeDefinitionEntry
{

	public function __construct(
		private string                           $biomeName,
		private int                              $id,
		private float                            $temperature,
		private float                            $downfall,
		private float                            $foliageSnow,
		private float                            $depth,
		private float                            $scale,
		private Color                            $mapWaterColor,
		private bool                             $rain,
		private ?array                           $tags,
		private ?v844BiomeDefinitionChunkGenData $chunkGenData = null
	)
	{

	}

	public function getBiomeName() : string
	{
		return $this->biomeName;
	}

	public function getId() : int
	{
		return $this->id;
	}

	public function getTemperature() : float
	{
		return $this->temperature;
	}

	public function getDownfall() : float
	{
		return $this->downfall;
	}

	public function getFoliageSnow() : float
	{
		return $this->foliageSnow;
	}

	public function getDepth() : float
	{
		return $this->depth;
	}

	public function getScale() : float
	{
		return $this->scale;
	}

	public function getMapWaterColor() : Color
	{
		return $this->mapWaterColor;
	}

	public function hasRain() : bool
	{
		return $this->rain;
	}

	public function getTags() : ?array
	{
		return $this->tags;
	}

	public function getChunkGenData() : ?v844BiomeDefinitionChunkGenData
	{
		return $this->chunkGenData;
	}

}
