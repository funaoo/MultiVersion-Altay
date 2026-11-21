<?php

declare(strict_types=1);

namespace lol\network\async;

use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use function chr;

class MVCompressBatchTask extends AsyncTask
{
	private const TLS_KEY_PROMISE = "promise";

	private NonThreadSafeValue $compressor;

	public function __construct(
		private string       $data,
		CompressBatchPromise $promise,
		Compressor           $compressor,
		private bool         $oldCompression
	)
	{
		$this->compressor = new NonThreadSafeValue($compressor);
		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
	}

	public function onRun() : void {
		$compressor = $this->compressor->deserialize();
		$this->setResult((!$this->oldCompression ? chr($compressor->getNetworkId()) : '') . $compressor->compress($this->data));
	}

	public function onCompletion() : void {
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}

	protected function reallyDestruct() : void {
		unset($this->compressor, $this->data);
	}
}
