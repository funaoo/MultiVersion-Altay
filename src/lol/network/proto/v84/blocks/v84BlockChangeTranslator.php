<?php

declare(strict_types=1);

namespace lol\network\proto\v84\blocks;

use lol\network\mcpe\MVRuntimeIdToStateId;
use pocketmine\world\format\io\GlobalBlockStateHandlers;

final class v84BlockChangeTranslator
{

	public function __construct(
		private readonly v84BlockResolver $resolver
	)
	{
	}

	public static function create() : self
	{
		return new self(v84BlockResolver::load(GlobalBlockStateHandlers::getSerializer()));
	}

	public function packedFromRuntimeId(int $blockRuntimeId) : int
	{
		return $this->resolver->resolveInternalId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($blockRuntimeId));
	}
}
