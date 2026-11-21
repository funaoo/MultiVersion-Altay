<?php

declare(strict_types=1);

namespace lol\network\proto\v84\framing;

use Generator;
use lol\network\etc\MVBatch;
use lol\network\etc\MVFrameCodec;
use lol\network\proto\TProtocol;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use pmmp\encoding\BE;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\timings\TimingsHandler;
use function count;
use function is_string;
use function ord;
use function reset;
use function strlen;

final class v84FrameCodec implements MVFrameCodec
{

	private const LOOSE_PACKET_THRESHOLD = 256;

	public function __construct(private readonly v84ZlibCompressor $compressor)
	{
	}

	public function unwrap(string $payload) : Generator
	{
		if (ord($payload[0]) !== v84ProtocolInfo::BATCH_PACKET) {
			yield $payload;
			return;
		}

		$reader = new ByteBufferReader($payload);
		Byte::readUnsigned($reader);
		$compressed = $reader->readByteArray(BE::readUnsignedInt($reader));

		try {
			$body = $this->compressor->decompress($compressed);
		} catch (DecompressionException $e) {
			throw PacketDecodeException::wrap($e, "Compressed packet batch decode error");
		}

		$inner = new ByteBufferReader($body);
		while ($inner->getUnreadLength() > 0) {
			$length = BE::readUnsignedInt($inner);
			if ($length === 0) {
				throw new PacketDecodeException("Empty packet inside batch");
			}

			$buffer = $inner->readByteArray($length);
			if (ord($buffer[0]) === v84ProtocolInfo::BATCH_PACKET) {
				throw new PacketDecodeException("Batch packet inside batch");
			}

			yield $buffer;
		}
	}

	public function wrap(array $buffers, TProtocol $protocol, ?bool $sync, ?TimingsHandler $timings) : CompressBatchPromise|string
	{
		if (count($buffers) === 1 && strlen(reset($buffers)) < self::LOOSE_PACKET_THRESHOLD) {
			return reset($buffers);
		}

		$compressed = MVBatch::prepareBatch($this->encodeBody($buffers), $protocol, $this->compressor, $sync, $timings);
		if (is_string($compressed)) {
			return $this->envelope($compressed);
		}

		$framed = new CompressBatchPromise();
		$compressed->onResolve(function (CompressBatchPromise $done) use ($framed) : void {
			$framed->resolve($this->envelope($done->getResult()));
		});

		return $framed;
	}

	public function wrapSync(array $buffers) : string
	{
		return $this->envelope($this->compressor->compress($this->encodeBody($buffers)));
	}

	private function encodeBody(array $buffers) : string
	{
		$writer = new ByteBufferWriter();
		foreach ($buffers as $buffer) {
			BE::writeUnsignedInt($writer, strlen($buffer));
			$writer->writeByteArray($buffer);
		}

		return $writer->getData();
	}

	private function envelope(string $compressed) : string
	{
		$writer = new ByteBufferWriter();
		Byte::writeUnsigned($writer, v84ProtocolInfo::BATCH_PACKET);
		BE::writeUnsignedInt($writer, strlen($compressed));
		$writer->writeByteArray($compressed);

		return $writer->getData();
	}
}
