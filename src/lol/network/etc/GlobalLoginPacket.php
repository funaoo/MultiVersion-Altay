<?php

declare(strict_types=1);

namespace lol\network\etc;

use BadMethodCallException;
use JsonException;
use lol\MCProtocols;
use lol\network\assemble\auth\JwtChain;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;
use function is_array;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final class GlobalLoginPacket extends LoginPacket {
	private JwtChain $chainDataJwt;

	public function getChainDataJwt() : JwtChain
	{
		if (!isset($this->chainDataJwt)) {
			throw new BadMethodCallException(self::class . "::$\chainDataJwt has not been provided either is not available in the protocol");
		}

		return $this->chainDataJwt;
	}

	public function setChainDataJwt(JwtChain $chainDataJwt) : void
	{
		$this->chainDataJwt = $chainDataJwt;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->protocol = BE::readUnsignedInt($in);
		$instance = MCProtocols::isSupported($this->protocol) ? MCProtocols::getProtocolInstance($this->protocol) : null;
		$buffer = $instance?->readLoginBuffer($in) ?? CommonTypes::getString($in);

		if ($instance === null) {
			$this->decodeConnectionRequest($buffer);
			return;
		}

		$instance->decodeConnection($buffer, $this);
	}

	public function decodeConnectionRequest(string $binary) : void {
		parent::decodeConnectionRequest($binary);
	}

	public function decodeLegacyConnectionRequest(string $binary) : void {
		$reader = new ByteBufferReader($binary);

		$chainDataJsonLength = LE::readUnsignedInt($reader);
		if ($chainDataJsonLength <= 0) {
			throw new PacketDecodeException("Length of chain data JSON must be positive");
		}
		try {
			$chainDataJson = json_decode($reader->readByteArray($chainDataJsonLength), true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new PacketDecodeException("Failed decoding chain data JSON: " . $e->getMessage());
		}
		if (!is_array($chainDataJson) || count($chainDataJson) !== 1 || !isset($chainDataJson["chain"]) || !is_array($chainDataJson["chain"])) {
			throw new PacketDecodeException("Chain data must be a JSON object containing only the 'chain' list");
		}

		$this->chainDataJwt = $this->createChain($chainDataJson["chain"]);

		$clientDataJwtLength = LE::readUnsignedInt($reader);
		if ($clientDataJwtLength <= 0) {
			throw new PacketDecodeException("Length of clientData JWT must be positive");
		}
		$this->clientDataJwt = $reader->readByteArray($clientDataJwtLength);
		$this->authInfoJson = "";
	}

	public function decodeCertificateConnectionRequest(string $binary) : void {
		parent::decodeConnectionRequest($binary);

		try {
			$authInfo = json_decode($this->authInfoJson, true, flags: JSON_THROW_ON_ERROR);
			$certificate = is_array($authInfo) ? ($authInfo["Certificate"] ?? null) : null;
			if (!is_string($certificate) || $certificate === "") {
				throw new PacketDecodeException("Auth info JSON must contain a certificate chain");
			}
			$chainDataJson = json_decode($certificate, true, flags: JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			throw new PacketDecodeException("Failed decoding certificate JSON: " . $e->getMessage());
		}
		if (!is_array($chainDataJson) || !isset($chainDataJson["chain"]) || !is_array($chainDataJson["chain"])) {
			throw new PacketDecodeException("Certificate must be a JSON object containing the 'chain' list");
		}

		$this->chainDataJwt = $this->createChain($chainDataJson["chain"]);
	}

	private function createChain(array $jwts) : JwtChain {
		$chain = new JwtChain();
		$chain->chain = [];
		foreach ($jwts as $jwt) {
			if (!is_string($jwt)) {
				throw new PacketDecodeException("Chain data 'chain' must contain only strings");
			}
			$chain->chain[] = $jwt;
		}

		return $chain;
	}

}
