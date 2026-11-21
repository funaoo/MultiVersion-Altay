<?php

declare(strict_types=1);

namespace lol\network\diagnostic;

final class BadPacketContext {

	public string $stage = "UNAVAILABLE";

	public ?int $protocolId = null;

	public ?string $wrapperClass = null;

	public ?int $packetId = null;

	public ?string $packetName = null;

	public ?string $payload = null;

	public ?string $decompressedBatch = null;

	public ?string $currentPacketBuffer = null;

	public ?int $bufferOffset = null;

	public ?int $bufferUnreadLength = null;

	public ?string $remainingBytes = null;
}
