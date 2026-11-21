<?php

declare(strict_types=1);

namespace lol\network\proto\v84\windows;

use lol\network\proto\v84\packets\v84ContainerOpenPacket;

final class v84PendingWindow
{

	public function __construct(
		public readonly v84ContainerOpenPacket $packet,
		private int                            $ticks
	)
	{
	}

	public function elapse() : bool
	{
		return --$this->ticks <= 0;
	}
}
