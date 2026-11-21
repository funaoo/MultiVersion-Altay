<?php

declare(strict_types=1);

namespace lol\network\diagnostic;

use lol\network\NetworkSession;
use pocketmine\Server;
use Symfony\Component\Filesystem\Path;
use Throwable;
use function bin2hex;
use function date;
use function file_put_contents;
use function implode;
use function is_dir;
use function is_string;
use function mkdir;
use function strlen;

final class BadPacketReportWriter {

	private static bool $enabled = true;

	public static function setEnabled(bool $enabled) : void {
		self::$enabled = $enabled;
	}

	public static function write(
		Server $server,
		NetworkSession $session,
		?BadPacketContext $context,
		Throwable $exception,
		string $errorId,
		string $transportName,
		bool $rakNetFraming,
		string $topLevelPayload
	) : void {
		if (!self::$enabled) {
			return;
		}

		try {
			self::writeInternal($server, $session, $context, $exception, $errorId, $transportName, $rakNetFraming, $topLevelPayload);
		} catch (Throwable $ignored) {

		}
	}

	private static function writeInternal(
		Server $server,
		NetworkSession $session,
		?BadPacketContext $context,
		Throwable $exception,
		string $errorId,
		string $transportName,
		bool $rakNetFraming,
		string $topLevelPayload
	) : void {
		$dir = Path::join($server->getDataPath(), "plugin_data", "MultiVersion", "bad_packets");
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		$filename = date("Ymd_His") . "_" . self::sanitizeFileToken($errorId) . ".txt";
		$path = Path::join($dir, $filename);

		$playerInfo = $session->getPlayerInfo();
		$username = $playerInfo !== null ? $playerInfo->getUsername() : "UNAVAILABLE";
		$uuid = $playerInfo !== null ? $playerInfo->getUuid()->toString() : "UNAVAILABLE";
		$extraData = $playerInfo !== null ? $playerInfo->getExtraData() : [];
		$gameVersion = isset($extraData["GameVersion"]) && is_string($extraData["GameVersion"]) ? $extraData["GameVersion"] : null;

		$protocol = $session->safeProtocol();
		$protocolId = $context?->protocolId ?? $protocol?->getProtocolId();
		$wrapperClass = $context?->wrapperClass ?? ($protocol !== null ? $protocol::class : null);
		$mappedVersion = self::resolveBedrockVersion($protocolId);

		$lines = [];
		$lines[] = "MultiVersion - Bad Packet Report";
		$lines[] = "Generado: " . date(DATE_ATOM);
		$lines[] = "Error ID: " . $errorId;
		$lines[] = "";
		$lines[] = "Jugador: " . $username;
		$lines[] = "UUID: " . $uuid;
		$lines[] = "IP: " . $session->getIp();
		$lines[] = "Puerto: " . (string) $session->getPort();
		$lines[] = "Protocolo Bedrock (raw): " . ($protocolId !== null ? (string) $protocolId : "UNAVAILABLE");
		$lines[] = "Version Bedrock (GameVersion cliente): " . ($gameVersion ?? "UNAVAILABLE");
		$lines[] = "Version Bedrock (mapeada por protocolo): " . ($mappedVersion ?? "UNAVAILABLE");
		$lines[] = "Wrapper de protocolo: " . ($wrapperClass ?? "UNAVAILABLE");
		$lines[] = "Transporte: " . $transportName;
		$lines[] = "RakNet framing: " . ($rakNetFraming ? "true" : "false");
		$lines[] = "Etapa de fallo: " . ($context?->stage ?? "UNAVAILABLE");
		$lines[] = "Packet ID: " . ($context?->packetId !== null ? (string) $context->packetId : "UNAVAILABLE");
		$lines[] = "Packet Name: " . ($context?->packetName ?? "UNAVAILABLE");
		$lines[] = "";
		$lines[] = "Error completo: " . $exception::class . ": " . $exception->getMessage();

		$cause = $exception->getPrevious();
		$chainIndex = 1;
		while ($cause !== null) {
			$lines[] = "Causa #" . $chainIndex . " (" . $cause::class . "): " . $cause->getMessage();
			$cause = $cause->getPrevious();
			$chainIndex++;
		}

		$lines[] = "";
		$lines[] = "Stack trace:";
		$lines[] = $exception->getTraceAsString();
		$lines[] = "";
		$lines[] = "Estado del buffer:";
		$lines[] = "Offset: " . ($context?->bufferOffset !== null ? (string) $context->bufferOffset : "UNAVAILABLE");
		$lines[] = "Bytes restantes: " . ($context?->bufferUnreadLength !== null ? (string) $context->bufferUnreadLength : "UNAVAILABLE");
		$lines[] = "Bytes restantes (HEX): " . ($context?->remainingBytes ?? "UNAVAILABLE");
		$lines[] = "";
		$lines[] = "Payload (HEX):";
		$lines[] = "Packet actual: " . ($context?->currentPacketBuffer !== null ? bin2hex($context->currentPacketBuffer) : "UNAVAILABLE");
		$lines[] = "Batch descomprimido: " . ($context?->decompressedBatch !== null ? bin2hex($context->decompressedBatch) : "UNAVAILABLE");
		$lines[] = "Payload: " . ($context?->payload !== null ? bin2hex($context->payload) : "UNAVAILABLE");
		$lines[] = "Payload de nivel de transporte: " . ($topLevelPayload !== "" ? bin2hex($topLevelPayload) : "UNAVAILABLE");

		file_put_contents($path, implode("\n", $lines) . "\n");
	}

	private static function sanitizeFileToken(string $errorId) : string {
		$result = "";
		$length = strlen($errorId);
		for ($i = 0; $i < $length; $i++) {
			$char = $errorId[$i];
			$result .= ($char >= "a" && $char <= "z") || ($char >= "A" && $char <= "Z") || ($char >= "0" && $char <= "9") ? $char : "-";
		}
		return $result;
	}

	private static function resolveBedrockVersion(?int $protocolId) : ?string {
		if ($protocolId === null) {
			return null;
		}

		return match ($protocolId) {
			84 => "0.15.10",
			844 => "1.21.114",
			2225 => "1.26.60",
			default => null,
		};
	}
}
