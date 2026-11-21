<?php

declare(strict_types=1);

namespace lol\network\async;

use lol\MCProtocols;
use lol\network\chunk\serializers\MVChunkPayloadVerifier;
use lol\network\chunk\serializers\PreFormattedChunkSerializer;
use lol\network\proto\TProtocol;
use lol\network\proto\v84\chunk\v84ChunkPayloadVerifier;
use lol\network\proto\v84\chunk\v84ChunkSerializer;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use function chr;
use function strlen;

class MVChunkRequestTask extends AsyncTask {

	private const TLS_KEY_PROMISE = "promise";

	protected string $chunk;
	protected int $chunkX;
	protected int $chunkZ;
	protected NonThreadSafeValue $compressor;
	private int $dimensionId;
	private string $tiles;
	private int $protocol;
	private bool $verifyPayload;

	public static bool $verifyPayloads = false;

	public function __construct(int $chunkX, int $chunkZ, int $dimensionId, Chunk $chunk, CompressBatchPromise $promise, Compressor $compressor, TProtocol $protocol) {
		$this->compressor = new NonThreadSafeValue($compressor);
		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->dimensionId = $dimensionId;
		$this->tiles = $protocol->getChunkSerializer()->serializeTiles($chunk);
		$this->protocol = $protocol->getProtocolId();
		$this->verifyPayload = self::$verifyPayloads;

		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
	}

	public function onRun() : void {
		$protocol = MCProtocols::getProtocolInstance($this->protocol);
		$chunkSerializer = $protocol->getChunkSerializer();
		$chunk = FastChunkSerializer::deserializeTerrain($this->chunk);
		$subCount = $chunkSerializer->getSubChunkCount($chunk, $this->dimensionId);
		$blockTranslator = $protocol->getTypeConverter()->getMVBlockTranslator();
		$payload = $chunkSerializer->serializeFullChunk($chunk, $this->dimensionId, $blockTranslator, $this->tiles);

		if($this->verifyPayload){
			$structuralError = match(true){
				$chunkSerializer instanceof PreFormattedChunkSerializer => MVChunkPayloadVerifier::verifyLegacy($payload, $subCount, strlen($this->tiles)),
				$chunkSerializer instanceof v84ChunkSerializer => v84ChunkPayloadVerifier::verify($payload, strlen($this->tiles)),
				default => MVChunkPayloadVerifier::verify($payload, $subCount, $this->dimensionId, $blockTranslator, strlen($this->tiles))
			};
			if($structuralError !== null){
				$protocol->getLogger()->error("[MV CHUNK CHECK] chunk ({$this->chunkX}, {$this->chunkZ}): " . $structuralError);
			}
		}

		$packet = $protocol->outcoming(LevelChunkPacket::create(
			new ChunkPosition($this->chunkX, $this->chunkZ),
			$this->dimensionId,
			$subCount,
			null,
			false,
			[],
			$payload,
			false
		));

		if($packet === null) throw new \LogicException("LevelChunkPacket cannot be skipped.");

		$frameCodec = $protocol->getFrameCodec();
		if ($frameCodec !== null) {
			$stream = new ByteBufferWriter();
			$packet->encode($stream);
			$this->setResult($frameCodec->wrapSync([$stream->getData()]));
			return;
		}

		$stream = new ByteBufferWriter();
		PacketBatch::encodePackets($stream, [$packet]);

		$compressor = $this->compressor->deserialize();
		$this->setResult((!$protocol->hasOldCompressionMethod() ? chr($compressor->getNetworkId()) : '') . $compressor->compress($stream->getData()));
	}

	public function onCompletion() : void {
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}

	protected function reallyDestruct() : void {
		unset($this->chunk, $this->chunkX, $this->chunkZ, $this->dimensionId, $this->tiles, $this->protocol, $this->verifyPayload, $this->compressor);
	}
}
