<?php

declare(strict_types=1);

namespace lol\network\assemble;

use pocketmine\network\mcpe\protocol\types\command\CommandOriginData as OriginData;
use pocketmine\network\PacketHandlingException;
use Ramsey\Uuid\UuidInterface;

class CommandOriginData
{
	public const ORIGIN_PLAYER = 0;
	public const ORIGIN_BLOCK = 1;
	public const ORIGIN_MINECART_BLOCK = 2;
	public const ORIGIN_DEV_CONSOLE = 3;
	public const ORIGIN_TEST = 4;
	public const ORIGIN_AUTOMATION_PLAYER = 5;
	public const ORIGIN_CLIENT_AUTOMATION = 6;
	public const ORIGIN_DEDICATED_SERVER = 7;
	public const ORIGIN_ENTITY = 8;
	public const ORIGIN_VIRTUAL = 9;
	public const ORIGIN_GAME_ARGUMENT = 10;
	public const ORIGIN_ENTITY_SERVER = 11;
	public const ORIGIN_PRECOMPILED = 12;
	public const ORIGIN_GAME_DIRECTOR_ENTITY_SERVER = 13;
	public const ORIGIN_SCRIPTING = 14;
	public const ORIGIN_EXECUTE_CONTEXT = 15;

	public int $type;
	public UuidInterface $uuid;
	public string $requestId;
	public int $playerActorUniqueId;

	static public function fromLatest(OriginData $data) : CommandOriginData{
		$result = new CommandOriginData();
		if (isset($data->playerActorUniqueId)) {
			$result->playerActorUniqueId = $data->playerActorUniqueId;
		}
		$result->requestId = $data->requestId;
		$result->uuid = $data->uuid;
		$result->type = match ($data->type) {
			OriginData::ORIGIN_PLAYER => self::ORIGIN_PLAYER,
			OriginData::ORIGIN_BLOCK => self::ORIGIN_BLOCK,
			OriginData::ORIGIN_MINECART_BLOCK => self::ORIGIN_MINECART_BLOCK,
			OriginData::ORIGIN_DEV_CONSOLE => self::ORIGIN_DEV_CONSOLE,
			OriginData::ORIGIN_TEST => self::ORIGIN_TEST,
			OriginData::ORIGIN_AUTOMATION_PLAYER => self::ORIGIN_AUTOMATION_PLAYER,
			OriginData::ORIGIN_ENTITY => self::ORIGIN_ENTITY,
			OriginData::ORIGIN_DEDICATED_SERVER => self::ORIGIN_DEDICATED_SERVER,
			OriginData::ORIGIN_CLIENT_AUTOMATION => self::ORIGIN_CLIENT_AUTOMATION,
			OriginData::ORIGIN_VIRTUAL => self::ORIGIN_VIRTUAL,
			OriginData::ORIGIN_GAME_ARGUMENT => self::ORIGIN_GAME_ARGUMENT,
			OriginData::ORIGIN_ENTITY_SERVER => self::ORIGIN_ENTITY_SERVER,
			OriginData::ORIGIN_PRECOMPILED => self::ORIGIN_PRECOMPILED,
			OriginData::ORIGIN_GAME_DIRECTOR_ENTITY_SERVER => self::ORIGIN_GAME_DIRECTOR_ENTITY_SERVER,
			OriginData::ORIGIN_SCRIPTING => self::ORIGIN_SCRIPTING,
			OriginData::ORIGIN_EXECUTE_CONTEXT => self::ORIGIN_EXECUTE_CONTEXT,
			default => throw new PacketHandlingException("Unsupported type for CommandOriginData")
		};
		return $result;
	}

	final public function toLatest() : OriginData {
		$result = new OriginData();
		if (isset($this->playerActorUniqueId)) {
			$result->playerActorUniqueId = $this->playerActorUniqueId;
		}
		$result->requestId = $this->requestId;
		$result->uuid = $this->uuid;
		$result->type = match ($this->type) {
			self::ORIGIN_PLAYER => OriginData::ORIGIN_PLAYER,
			self::ORIGIN_BLOCK => OriginData::ORIGIN_BLOCK,
			self::ORIGIN_MINECART_BLOCK => OriginData::ORIGIN_MINECART_BLOCK,
			self::ORIGIN_DEV_CONSOLE => OriginData::ORIGIN_DEV_CONSOLE,
			self::ORIGIN_TEST => OriginData::ORIGIN_TEST,
			self::ORIGIN_AUTOMATION_PLAYER => OriginData::ORIGIN_AUTOMATION_PLAYER,
			self::ORIGIN_ENTITY => OriginData::ORIGIN_ENTITY,
			self::ORIGIN_DEDICATED_SERVER => OriginData::ORIGIN_DEDICATED_SERVER,
			self::ORIGIN_CLIENT_AUTOMATION => OriginData::ORIGIN_CLIENT_AUTOMATION,
			self::ORIGIN_VIRTUAL => OriginData::ORIGIN_VIRTUAL,
			self::ORIGIN_GAME_ARGUMENT => OriginData::ORIGIN_GAME_ARGUMENT,
			self::ORIGIN_ENTITY_SERVER => OriginData::ORIGIN_ENTITY_SERVER,
			self::ORIGIN_PRECOMPILED => OriginData::ORIGIN_PRECOMPILED,
			self::ORIGIN_GAME_DIRECTOR_ENTITY_SERVER => OriginData::ORIGIN_GAME_DIRECTOR_ENTITY_SERVER,
			self::ORIGIN_SCRIPTING => OriginData::ORIGIN_SCRIPTING,
			self::ORIGIN_EXECUTE_CONTEXT => OriginData::ORIGIN_EXECUTE_CONTEXT,
			default => throw new PacketHandlingException("Unsupported type for CommandOriginData")
		};
		return $result;
	}
}
