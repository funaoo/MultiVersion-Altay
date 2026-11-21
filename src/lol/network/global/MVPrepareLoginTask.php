<?php

declare(strict_types=1);

namespace lol\network\global;

use Closure;
use JsonMapper;
use JsonMapper_Exception;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\auth\VerifyLoginException;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\protocol\types\login\legacy\LegacyAuthJwtBody;
use pocketmine\network\mcpe\protocol\types\login\SelfSignedJwtHeader;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\utils\AssumptionFailedError;
use function base64_decode;
use function igbinary_serialize;
use function igbinary_unserialize;
use function time;

class MVPrepareLoginTask extends AsyncTask {

	private const TLS_KEY_ON_COMPLETION = "completion";
	private const DEFAULT_CLOCK_DRIFT = 60;

	private const LEGACY_MOJANG_ROOT_PUBLIC_KEY = "MHYwEAYHKoZIzj0CAQYFK4EEACIDYgAECRXueJeTDqNRRgJi/vlRufByu/2G0i2Ebt6YMar5QX/R0DIIyrJMcUpruK4QveTfJSTp3Shlq4Gk34cD/4GUWwkv0DVuzeuB+tXija7HBxii03NHDbPAD0AKnLr2wdAp";

	private string $chain;

	private NonThreadSafeValue|string|null $error = "Unknown";
	private bool $authenticated = false;
	private bool $chainOutOfDate = false;
	private ?string $clientPublicKeyDer = null;

	public function __construct(array          $chainJwts, private string $clientDataJwt, private bool   $authRequired, Closure       $onCompletion, private bool $tolerateOutOfDateChain = false)
	{
		$this->storeLocal(self::TLS_KEY_ON_COMPLETION, $onCompletion);
		$this->chain = igbinary_serialize($chainJwts) ?? throw new AssumptionFailedError("This should never fail");
	}

	public function onRun() : void
	{
		try {
			$this->clientPublicKeyDer = $this->validateChain();
			$this->error = null;
		} catch (VerifyLoginException $e) {
			$disconnectMessage = $e->getDisconnectMessage();
			$this->error = $disconnectMessage instanceof Translatable ? new NonThreadSafeValue($disconnectMessage) : $disconnectMessage;
		}
	}

	private function validateChain() : string
	{
		$chain = igbinary_unserialize($this->chain);

		$currentKey = null;
		$first = true;

		foreach ($chain as $jwt) {
			$this->validateToken($jwt, $currentKey, $first);
			if ($first) {
				$first = false;
			}
		}

		$clientKey = $currentKey;

		$this->validateToken($this->clientDataJwt, $currentKey);

		if ($this->chainOutOfDate) {
			$this->authenticated = false;
		}

		return $clientKey;
	}

	private function validateToken(string $jwt, ?string &$currentPublicKey, bool $first = false) : void
	{
		try {
			[$headersArray, $claimsArray,] = JwtUtils::parse($jwt);
		} catch (JwtException $e) {
			throw new VerifyLoginException("Failed to parse JWT: " . $e->getMessage(), null, 0, $e);
		}

		$mapper = new JsonMapper();
		$mapper->bExceptionOnMissingData = true;
		$mapper->bExceptionOnUndefinedProperty = true;
		$mapper->bStrictObjectTypeChecking = true;
		$mapper->bEnforceMapType = false;

		try {
			$headers = $mapper->map($headersArray, new SelfSignedJwtHeader());
		} catch (JsonMapper_Exception $e) {
			throw new VerifyLoginException("Invalid JWT header: " . $e->getMessage(), null, 0, $e);
		}

		$headerDerKey = base64_decode($headers->x5u, true);
		if ($headerDerKey === false) {
			throw new VerifyLoginException("Invalid JWT public key: base64 decoding error decoding x5u");
		}

		if ($currentPublicKey === null) {
			if (!$first) {
				throw new VerifyLoginException("Missing JWT public key", KnownTranslationFactory::pocketmine_disconnect_invalidSession_missingKey());
			}
		} elseif ($headerDerKey !== $currentPublicKey) {
			throw new VerifyLoginException("Invalid JWT signature", KnownTranslationFactory::pocketmine_disconnect_invalidSession_badSignature());
		}

		try {
			$signingKeyOpenSSL = JwtUtils::parseDerPublicKey($headerDerKey);
		} catch (JwtException $e) {
			throw new VerifyLoginException("Invalid JWT public key: " . $e->getMessage(), null, 0, $e);
		}
		try {
			if (!JwtUtils::verify($jwt, JwtUtils::emitDerPublicKey($signingKeyOpenSSL), true)) {
				throw new VerifyLoginException("Invalid JWT signature", KnownTranslationFactory::pocketmine_disconnect_invalidSession_badSignature());
			}
		} catch (JwtException $e) {
			throw new VerifyLoginException($e->getMessage(), null, 0, $e);
		}

		if ($headers->x5u === self::LEGACY_MOJANG_ROOT_PUBLIC_KEY) {
			$this->authenticated = true;
		}

		$mapper = new JsonMapper();
		$mapper->bExceptionOnUndefinedProperty = false;
		$mapper->bExceptionOnMissingData = true;
		$mapper->bStrictObjectTypeChecking = true;
		$mapper->bEnforceMapType = false;
		$mapper->bRemoveUndefinedAttributes = true;
		try {
			$claims = $mapper->map($claimsArray, new LegacyAuthJwtBody());
		} catch (JsonMapper_Exception $e) {
			throw new VerifyLoginException("Invalid chain link body: " . $e->getMessage(), null, 0, $e);
		}

		$now = time();
		if (isset($claims->nbf) && $claims->nbf > $now + self::DEFAULT_CLOCK_DRIFT) {
			if (!$this->tolerateOutOfDateChain) {
				throw new VerifyLoginException("JWT not yet valid", KnownTranslationFactory::pocketmine_disconnect_invalidSession_tooEarly());
			}
			$this->chainOutOfDate = true;
		}

		if (isset($claims->exp) && $claims->exp < $now - self::DEFAULT_CLOCK_DRIFT) {
			if (!$this->tolerateOutOfDateChain) {
				throw new VerifyLoginException("JWT expired", KnownTranslationFactory::pocketmine_disconnect_invalidSession_tooLate());
			}
			$this->chainOutOfDate = true;
		}

		if (isset($claims->identityPublicKey)) {
			$identityPublicKey = base64_decode($claims->identityPublicKey, true);
			if ($identityPublicKey === false) {
				throw new VerifyLoginException("Invalid identityPublicKey: base64 error decoding");
			}
			try {
				JwtUtils::parseDerPublicKey($identityPublicKey);
			} catch (JwtException $e) {
				throw new VerifyLoginException("Invalid identityPublicKey: " . $e->getMessage(), null, 0, $e);
			}
			$currentPublicKey = $identityPublicKey;
		}
	}

	public function onCompletion() : void
	{
		$callback = $this->fetchLocal(self::TLS_KEY_ON_COMPLETION);
		$callback($this->authenticated, $this->authRequired, $this->error instanceof NonThreadSafeValue ? $this->error->deserialize() : $this->error, $this->clientPublicKeyDer);
	}

}
