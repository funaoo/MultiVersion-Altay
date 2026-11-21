<?php

declare(strict_types=1);

namespace lol\network\proto\v84\framing;

use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use RuntimeException;
use function zlib_decode;
use function zlib_encode;
use const ZLIB_ENCODING_DEFLATE;

final class v84ZlibCompressor implements Compressor
{

	private const COMPRESSION_LEVEL = 7;

	private const MAX_DECOMPRESSED_SIZE = 2 * 1024 * 1024;

	private const COMPRESS_EVERYTHING_THRESHOLD = 0;

	public function willCompress(string $data) : bool
	{
		return true;
	}

	public function getCompressionThreshold() : ?int
	{
		return self::COMPRESS_EVERYTHING_THRESHOLD;
	}

	public function getNetworkId() : int
	{
		return CompressionAlgorithm::ZLIB;
	}

	public function decompress(string $payload) : string
	{
		$result = @zlib_decode($payload, self::MAX_DECOMPRESSED_SIZE);
		if ($result === false) {
			throw new DecompressionException("Failed to decompress zlib data");
		}

		return $result;
	}

	public function compress(string $payload) : string
	{
		return zlib_encode($payload, ZLIB_ENCODING_DEFLATE, self::COMPRESSION_LEVEL) ?: throw new RuntimeException("Failed to compress zlib data");
	}
}
