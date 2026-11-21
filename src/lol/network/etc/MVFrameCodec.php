<?php

declare(strict_types=1);

namespace lol\network\etc;

use lol\network\proto\TProtocol;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\timings\TimingsHandler;

interface MVFrameCodec
{

	public function unwrap(string $payload) : iterable;

	public function wrap(array $buffers, TProtocol $protocol, ?bool $sync, ?TimingsHandler $timings) : CompressBatchPromise|string;

	public function wrapSync(array $buffers) : string;
}
