<?php

declare(strict_types=1);

namespace lol\network\proto\v84\framing;

use lol\network\proto\v84\structure\v84ProtocolInfo;
use pocketmine\network\mcpe\compression\DecompressionException;
use function chr;
use function min;
use function ord;
use function pack;
use function str_starts_with;
use function strlen;
use function substr;
use function unpack;

final class v84LoginSniffer
{

	private const BATCH_HEADER_LENGTH = 5;

	private const LENGTH_PREFIX_LENGTH = 4;

	private const LOGIN_SIGNATURE_LENGTH = 5;

	private function __construct()
	{
	}

	public static function matches(string $payload) : bool
	{
		$head = self::firstPacketHead($payload);

		return $head !== null && str_starts_with($head, chr(v84ProtocolInfo::LOGIN_PACKET) . pack("N", v84ProtocolInfo::CURRENT_PROTOCOL));
	}

	private static function firstPacketHead(string $payload) : ?string
	{
		if ($payload === "") {
			return null;
		}

		if (ord($payload[0]) !== v84ProtocolInfo::BATCH_PACKET) {
			return substr($payload, 0, self::LOGIN_SIGNATURE_LENGTH);
		}

		return self::batchFirstPacketHead($payload);
	}

	private static function batchFirstPacketHead(string $payload) : ?string
	{
		if (strlen($payload) <= self::BATCH_HEADER_LENGTH) {
			return null;
		}

		$compressedLength = unpack("Nlength", $payload, 1)["length"];
		if ($compressedLength !== strlen($payload) - self::BATCH_HEADER_LENGTH) {
			return null;
		}

		try {
			$body = (new v84ZlibCompressor())->decompress(substr($payload, self::BATCH_HEADER_LENGTH));
		} catch (DecompressionException) {
			return null;
		}

		if (strlen($body) <= self::LENGTH_PREFIX_LENGTH) {
			return null;
		}

		$packetLength = unpack("Nlength", $body)["length"];

		return substr($body, self::LENGTH_PREFIX_LENGTH, min($packetLength, self::LOGIN_SIGNATURE_LENGTH));
	}
}
