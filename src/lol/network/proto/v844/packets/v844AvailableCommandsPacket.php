<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\command\CommandParameterTypes as ArgTypes;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use pocketmine\network\mcpe\protocol\types\command\raw\ChainedSubCommandRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\ChainedSubCommandValueRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandEnumConstraintRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandEnumRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandOverloadRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandRawData;
use function count;

class v844AvailableCommandsPacket extends DataPacket implements ClientboundPacket {

	public const NETWORK_ID = ProtocolInfo::AVAILABLE_COMMANDS_PACKET;

	public const ARG_FLAG_VALID = 0x100000;

	public const ARG_TYPE_INT = ArgTypes::INT;
	public const ARG_TYPE_FLOAT = ArgTypes::VAL;
	public const ARG_TYPE_VALUE = ArgTypes::RVAL;
	public const ARG_TYPE_WILDCARD_INT = ArgTypes::WILDCARDINT;
	public const ARG_TYPE_OPERATOR = ArgTypes::OPERATOR;
	public const ARG_TYPE_COMPARE_OPERATOR = ArgTypes::COMPAREOPERATOR;
	public const ARG_TYPE_TARGET = ArgTypes::SELECTION;

	public const ARG_TYPE_WILDCARD_TARGET = ArgTypes::WILDCARDSELECTION;

	public const ARG_TYPE_FILEPATH = ArgTypes::PATHCOMMAND;

	public const ARG_TYPE_FULL_INTEGER_RANGE = ArgTypes::FULLINTEGERRANGE;

	public const ARG_TYPE_EQUIPMENT_SLOT = ArgTypes::EQUIPMENTSLOTENUM;
	public const ARG_TYPE_STRING = ArgTypes::ID;

	public const ARG_TYPE_INT_POSITION = ArgTypes::POSITION;
	public const ARG_TYPE_POSITION = ArgTypes::POSITION_FLOAT;

	public const ARG_TYPE_MESSAGE = ArgTypes::MESSAGE_ROOT;

	public const ARG_TYPE_RAWTEXT = ArgTypes::RAWTEXT;

	public const ARG_TYPE_JSON = ArgTypes::JSON_OBJECT;

	public const ARG_TYPE_BLOCK_STATES = ArgTypes::BLOCK_STATE_ARRAY;

	public const ARG_TYPE_COMMAND = ArgTypes::CODEBUILDERARGS;

	public const ARG_FLAG_ENUM = 0x200000;

	public const ARG_FLAG_POSTFIX = 0x1000000;

	public const ARG_FLAG_SOFT_ENUM = 0x4000000;

	public array $enumValues = [];
	public array $chainedSubCommandValues = [];
	public array $postfixes = [];
	public array $enums = [];
	public array $chainedSubCommandData = [];
	public array $commandData = [];
	public array $softEnums = [];
	public array $enumConstraints = [];

	public static function create(
		array $enumValues,
		array $chainedSubCommandValues,
		array $postfixes,
		array $enums,
		array $chainedSubCommandData,
		array $commandData,
		array $softEnums,
		array $enumConstraints
	) : self{
		$result = new self();
		$result->enumValues = $enumValues;
		$result->chainedSubCommandValues = $chainedSubCommandValues;
		$result->postfixes = $postfixes;
		$result->enums = $enums;
		$result->chainedSubCommandData = $chainedSubCommandData;
		$result->commandData = $commandData;
		$result->softEnums = $softEnums;
		$result->enumConstraints = $enumConstraints;
		return $result;
	}

	static public function fromLatest(AvailableCommandsPacket $packet) : self{
		return self::create(
			$packet->enumValues,
			$packet->chainedSubCommandValues,
			$packet->postfixes,
			$packet->enums,
			$packet->chainedSubCommandData,
			$packet->commandData,
			$packet->softEnums,
			$packet->enumConstraints
		);
	}

	private static function readEnumValueIndex(ByteBufferReader $in, int $valueListSize) : int{
		return match(true){
			$valueListSize < 256 => Byte::readUnsigned($in),
			$valueListSize < 65536 => LE::readUnsignedShort($in),
			default => LE::readUnsignedInt($in)
		};
	}

	private static function writeEnumValueIndex(ByteBufferWriter $out, int $valueListSize, int $index) : void{
		match(true){
			$valueListSize < 256 => Byte::writeUnsigned($out, $index),
			$valueListSize < 65536 => LE::writeUnsignedShort($out, $index),
			default => LE::writeUnsignedInt($out, $index)
		};
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->enumValues = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$this->enumValues[] = CommonTypes::getString($in);
		}

		$this->chainedSubCommandValues = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$this->chainedSubCommandValues[] = CommonTypes::getString($in);
		}

		$this->postfixes = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$this->postfixes[] = CommonTypes::getString($in);
		}

		$this->enums = [];
		$valueListSize = count($this->enumValues);
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$name = CommonTypes::getString($in);
			$valueIndexes = [];
			for($j = 0, $jSize = VarInt::readUnsignedInt($in); $j < $jSize; $j++){
				$valueIndexes[] = self::readEnumValueIndex($in, $valueListSize);
			}
			$this->enums[] = new CommandEnumRawData($name, $valueIndexes);
		}

		$this->chainedSubCommandData = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$name = CommonTypes::getString($in);
			$values = [];
			for($j = 0, $jSize = VarInt::readUnsignedInt($in); $j < $jSize; $j++){
				$nameIndex = LE::readUnsignedShort($in);
				$type = LE::readUnsignedShort($in);
				$values[] = new ChainedSubCommandValueRawData($nameIndex, $type);
			}
			$this->chainedSubCommandData[] = new ChainedSubCommandRawData($name, $values);
		}

		$this->commandData = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$name = CommonTypes::getString($in);
			$description = CommonTypes::getString($in);
			$flags = LE::readUnsignedShort($in);
			$permission = CommandPermissions::toName(Byte::readUnsigned($in));
			$aliasEnumIndex = LE::readSignedInt($in);

			$chainedSubCommandDataIndexes = [];
			for($j = 0, $jSize = VarInt::readUnsignedInt($in); $j < $jSize; $j++){
				$chainedSubCommandDataIndexes[] = LE::readUnsignedShort($in);
			}

			$overloads = [];
			for($j = 0, $jSize = VarInt::readUnsignedInt($in); $j < $jSize; $j++){
				$overloads[] = CommandOverloadRawData::read($in);
			}

			$this->commandData[] = new CommandRawData(
				name: $name,
				description: $description,
				flags: $flags,
				permission: $permission,
				aliasEnumIndex: $aliasEnumIndex,
				chainedSubCommandDataIndexes: $chainedSubCommandDataIndexes,
				overloads: $overloads
			);
		}

		$this->softEnums = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$this->softEnums[] = CommandSoftEnum::read($in);
		}

		$this->enumConstraints = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$this->enumConstraints[] = CommandEnumConstraintRawData::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->enumValues));
		foreach($this->enumValues as $value){
			CommonTypes::putString($out, $value);
		}

		VarInt::writeUnsignedInt($out, count($this->chainedSubCommandValues));
		foreach($this->chainedSubCommandValues as $value){
			CommonTypes::putString($out, $value);
		}

		VarInt::writeUnsignedInt($out, count($this->postfixes));
		foreach($this->postfixes as $postfix){
			CommonTypes::putString($out, $postfix);
		}

		VarInt::writeUnsignedInt($out, count($this->enums));
		$valueListSize = count($this->enumValues);
		foreach($this->enums as $enum){
			CommonTypes::putString($out, $enum->getName());
			$valueIndexes = $enum->getValueIndexes();
			VarInt::writeUnsignedInt($out, count($valueIndexes));
			foreach($valueIndexes as $index){
				self::writeEnumValueIndex($out, $valueListSize, $index);
			}
		}

		VarInt::writeUnsignedInt($out, count($this->chainedSubCommandData));
		foreach($this->chainedSubCommandData as $data){
			CommonTypes::putString($out, $data->getName());
			$values = $data->getValueData();
			VarInt::writeUnsignedInt($out, count($values));
			foreach($values as $value){
				LE::writeUnsignedShort($out, $value->getNameIndex());
				LE::writeUnsignedShort($out, $value->getType());
			}
		}

		VarInt::writeUnsignedInt($out, count($this->commandData));
		foreach($this->commandData as $data){
			CommonTypes::putString($out, $data->getName());
			CommonTypes::putString($out, $data->getDescription());
			LE::writeUnsignedShort($out, $data->getFlags());
			Byte::writeUnsigned($out, CommandPermissions::fromName($data->getPermission()));
			LE::writeSignedInt($out, $data->getAliasEnumIndex());

			VarInt::writeUnsignedInt($out, count($data->getChainedSubCommandDataIndexes()));
			foreach($data->getChainedSubCommandDataIndexes() as $index){
				LE::writeUnsignedShort($out, $index);
			}

			VarInt::writeUnsignedInt($out, count($data->getOverloads()));
			foreach($data->getOverloads() as $overload){
				$overload->write($out);
			}
		}

		VarInt::writeUnsignedInt($out, count($this->softEnums));
		foreach($this->softEnums as $softEnum){
			$softEnum->write($out);
		}

		VarInt::writeUnsignedInt($out, count($this->enumConstraints));
		foreach($this->enumConstraints as $constraint){
			$constraint->write($out);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool {
		return true;
	}
}
