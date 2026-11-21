<?php

declare(strict_types=1);

namespace lol\network\global;

use lol\MCProtocols;
use lol\network\assemble\auth\AuthenticationData;
use lol\network\assemble\auth\JwtChain;
use lol\network\etc\GlobalLoginPacket;
use lol\network\NetworkSession;
use lol\network\proto\ProtoLogger;
use lol\network\proto\TProtocol;
use lol\network\proto\v84\structure\v84ProtocolInfo;
use lol\network\proto\v844\structure\v844ProtocolInfo;
use lol\network\utils\ReflectionUtils;
use Closure;
use InvalidArgumentException;
use JsonMapper;
use JsonMapper_Exception;
use pocketmine\entity\InvalidSkinException;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\handler\LoginPacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\types\login\AuthenticationType;
use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientData;
use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientDataToSkinDataHelper;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ReflectionException;
use function assert;
use function base64_decode;
use function chr;
use function in_array;
use function is_array;
use function is_string;
use function md5;
use function ord;
use function preg_match;

class MVLoginPacketHandler extends LoginPacketHandler {

	private const TRANSLATED_PROTOCOLS = [
		v844ProtocolInfo::CURRENT_PROTOCOL,
	];

	private const LEGACY_LOGIN_PROTOCOLS = [
		v84ProtocolInfo::CURRENT_PROTOCOL,
	];

	public function __construct(private Server $server, private NetworkSession $session, private Closure $playerInfoConsumer, private Closure $authCallback, private Closure $onSucess)
	{
		parent::__construct($server, $session, $playerInfoConsumer, $authCallback);
	}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool {
		if(!MCProtocols::isSupported($protocol = $packet->getProtocolVersion())){
			$this->session->disconnectIncompatibleProtocol($protocol);
			return true;
		}

		$this->session->setProtocol(MCProtocols::getProtocolInstance($protocol));

		if($protocol !== ProtocolInfo::CURRENT_PROTOCOL){
			ReflectionUtils::setProperty(RequestNetworkSettingsPacket::class, $packet, "protocolVersion", ProtocolInfo::CURRENT_PROTOCOL);
		}

		$this->session->sendDataPacket(NetworkSettingsPacket::create(
			NetworkSettingsPacket::COMPRESS_EVERYTHING,
			$this->session->getCompressor()->getNetworkId(),
			false,
			0,
			0
		));
		($this->onSucess)();

		return true;
	}

	public function handleLogin(LoginPacket $packet) : bool {
		assert($packet instanceof GlobalLoginPacket);
		$protocol = $packet->protocol;

		if ($packet->protocol !== ProtocolInfo::CURRENT_PROTOCOL) {
			$session = $this->session;
			if (!MCProtocols::isSupported($protocol)) {
				$session->disconnectIncompatibleProtocol($protocol);
				return false;
			}

			$instance = MCProtocols::getProtocolInstance($protocol);
			$session->setProtocol($instance);
			(new ProtoLogger($instance, $this->resolveClientVersion($packet->clientDataJwt, $instance)))->info("Translating packets from {$this->session->getIp()}:{$this->session->getPort()}");
			$packet->protocol = ProtocolInfo::CURRENT_PROTOCOL;
			if (in_array($protocol, self::TRANSLATED_PROTOCOLS, true)){
			   return $this->handle844Login($packet);
			}
			if (in_array($protocol, self::LEGACY_LOGIN_PROTOCOLS, true)){
				return $this->handleLegacyLogin($packet);
			}
		}

		return parent::handleLogin($packet);
	}

	private function resolveClientVersion(string $clientDataJwt, TProtocol $protocol) : string {
		try {
			[, $claims,] = JwtUtils::parse($clientDataJwt);
		} catch (JwtException) {
			return (string) $protocol;
		}

		$version = $claims["GameVersion"] ?? null;
		return is_string($version) && preg_match('/^\d+(\.\d+){1,3}$/', $version) === 1 ? "v" . $version : (string) $protocol;
	}

	private function handle844Login(GlobalLoginPacket $packet) : bool {
		$authInfo = $this->parseAuthInfo($packet->authInfoJson);

		if($authInfo->AuthenticationType === AuthenticationType::FULL->value){
			try{
				[$headerArray, $claimsArray,] = JwtUtils::parse($authInfo->Token);
			}catch(JwtException $e){
				throw PacketHandlingException::wrap($e, "Error parsing authentication token");
			}
			$header = $this->mapXboxTokenHeader($headerArray);
			$claims = $this->mapXboxTokenBody($claimsArray);

			$legacyUuid = self::calculateUuidFromXuid($claims->xid);
			$username = $claims->xname;
			$xuid = $claims->xid;

			$authRequired = ReflectionUtils::invoke(LoginPacketHandler::class, $this, "processLoginCommon", $packet, $username, $legacyUuid, $xuid);
			if($authRequired === null){
				return true;
			}
			$this->processOpenIdLogin($authInfo->Token, $header->kid, $packet->clientDataJwt, $authRequired);

		}elseif($authInfo->AuthenticationType === AuthenticationType::SELF_SIGNED->value){
			try{
				[, $claimsArray, ] = JwtUtils::parse($authInfo->Token);
			}catch(JwtException $e){
				throw PacketHandlingException::wrap($e, "Error parsing self-signed authentication token");
			}
			$claims = $this->mapSelfSignedTokenBody($claimsArray);

			if(!Uuid::isValid($claims->leguuid)){
				throw new PacketHandlingException("Invalid UUID string in self-signed certificate: " . $claims->leguuid);
			}
			$legacyUuid = Uuid::fromString($claims->leguuid);
			$username = $claims->xname;
			$xuid = "";

			$selfSignedKey = base64_decode($claims->cpk, strict: true);
			if($selfSignedKey === false){
				throw new PacketHandlingException("Invalid self-signed key");
			}

			$authRequired = ReflectionUtils::invoke(LoginPacketHandler::class, $this, "processLoginCommon", $packet, $username, $legacyUuid, $xuid);
			if($authRequired === null){
				return true;
			}
			$this->processSelfSignedLogin($authInfo->Token, $selfSignedKey, $packet->clientDataJwt, $authRequired);
		}else{
			throw new PacketHandlingException("Unsupported authentication type: $authInfo->AuthenticationType");
		}

		return true;
	}

	private function handleLegacyLogin(GlobalLoginPacket $packet) : bool {
		$extraData = $this->mvFetchAuthData($packet->getChainDataJwt());

		if(!Uuid::isValid($extraData->identity)){
			throw new PacketHandlingException("Invalid login UUID");
		}

		$authRequired = ReflectionUtils::invoke(LoginPacketHandler::class, $this, "processLoginCommon", $packet, $extraData->displayName, Uuid::fromString($extraData->identity), $extraData->XUID);
		if($authRequired === null){
			return true;
		}
		if($this->session->safeProtocol()?->getProtocolId() === v84ProtocolInfo::CURRENT_PROTOCOL){
			$authRequired = false;
		}
		$this->mvProcessLogin($packet, $authRequired);

		return true;
	}

	protected function mvFetchAuthData(JwtChain $chain) : AuthenticationData
	{
		$extraData = null;
		foreach ($chain->chain as $jwt) {
			try {
				[, $claims,] = JwtUtils::parse($jwt);
			} catch (JwtException $e) {
				throw PacketHandlingException::wrap($e);
			}
			if (isset($claims["extraData"])) {
				if ($extraData !== null) {
					throw new PacketHandlingException("Found 'extraData' more than once in chainData");
				}

				if (!is_array($claims["extraData"])) {
					throw new PacketHandlingException("'extraData' key should be an array");
				}
				$mapper = $this->defaultJsonMapper("Extra Data");
				try {
					$extraData = $mapper->map($claims["extraData"], new AuthenticationData());
				} catch (JsonMapper_Exception $e) {
					throw PacketHandlingException::wrap($e);
				}
			}
		}
		if ($extraData === null) {
			throw new PacketHandlingException("'extraData' not found in chain data");
		}
		return $extraData;
	}

	protected function mvParseClientData(string $clientDataJwt) : ClientData
	{
		try {
			[, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
		} catch (JwtException $e) {
			throw PacketDecodeException::wrap($e);
		}

		$this->session->safeProtocol()?->injectExtraData($clientDataClaims);

		$mapper = $this->defaultJsonMapper("Legacy Client Data");
		try {
			$clientData = $mapper->map($clientDataClaims, new ClientData());
		} catch (JsonMapper_Exception $e) {
			throw PacketDecodeException::wrap($e);
		}
		return $clientData;
	}

	protected function mvProcessLogin(GlobalLoginPacket $packet, bool $authRequired) : void
	{
		$this->server->getAsyncPool()->submitTask(new MVPrepareLoginTask($packet->getChainDataJwt()->chain, $packet->clientDataJwt, $authRequired, $this->authCallback, $this->session->safeProtocol()?->getProtocolId() === v84ProtocolInfo::CURRENT_PROTOCOL));
		$this->session->setHandler(null);
	}

	public function handleCraftingData(CraftingDataPacket $packet) : bool
	{
		return true;
	}

	protected function parseClientData(string $clientDataJwt) : ClientData
	{
		try {
			[, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
		} catch (JwtException $e) {
			throw PacketHandlingException::wrap($e);
		}

		$this->session->safeProtocol()?->injectExtraData($clientDataClaims);

		$mapper = $this->defaultJsonMapper("Client Data");
		try {
			$clientData = $mapper->map($clientDataClaims, new ClientData());
		} catch (JsonMapper_Exception $e) {
			throw PacketHandlingException::wrap($e);
		}
		return $clientData;
	}

	private static function calculateUuidFromXuid(string $xuid) : UuidInterface{
		$hash = md5("pocket-auth-1-xuid:" . $xuid, binary: true);
		$hash[6] = chr((ord($hash[6]) & 0x0f) | 0x30);
		$hash[8] = chr((ord($hash[8]) & 0x3f) | 0x80);

		return Uuid::fromBytes($hash);
	}

	private function defaultJsonMapper(string $logContext) : JsonMapper{
		$mapper = new JsonMapper();
		$mapper->bExceptionOnMissingData = false;
		$mapper->undefinedPropertyHandler = $this->warnUndefinedJsonPropertyHandler($logContext);
		$mapper->bStrictObjectTypeChecking = true;
		$mapper->bEnforceMapType = false;
		return $mapper;
	}

	private function warnUndefinedJsonPropertyHandler(string $context) : Closure{
		return function(object $object, string $name, mixed $value) use ($context) : void{
			static $count = 0;
			if($count++ < 10){
				$this->session->getLogger()->warning(
					"$context: Unexpected JSON property for " . (new \ReflectionClass($object))->getShortName()
				);
			}else{
				throw new PacketHandlingException("$context: Too many unexpected JSON properties");
			}
		};
	}
}
