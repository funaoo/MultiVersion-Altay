<?php

declare(strict_types=1);

namespace lol\network\proto\v84\particles;

final class v84ParticleEvent
{

	public const SHOOT = 2000;

	public const DESTROY = 2001;

	public const SPLASH = 2002;

	public const EYE_DESPAWN = 2003;

	public const SPAWN = 2004;

	public const ADD_PARTICLE_MASK = 0x4000;

	private function __construct()
	{
	}
}
