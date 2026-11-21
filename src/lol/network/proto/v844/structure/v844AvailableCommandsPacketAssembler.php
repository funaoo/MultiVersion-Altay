<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\network\assemble\command\ChainedSubCommandData;
use lol\network\assemble\command\CommandData;
use lol\network\assemble\command\CommandEnum;
use lol\network\assemble\command\CommandEnumConstraint;
use lol\network\proto\v844\packets\v844AvailableCommandsPacket;
use LogicException;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\command\CommandSoftEnum;
use pocketmine\network\mcpe\protocol\types\command\raw\ChainedSubCommandRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\ChainedSubCommandValueRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandEnumConstraintRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandEnumRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandOverloadRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandParameterRawData;
use pocketmine\network\mcpe\protocol\types\command\raw\CommandRawData;
use function count;
use function spl_object_id;

final class v844AvailableCommandsPacketAssembler{

	private array $enumIndexes = [];
	private array $enumValueIndexes = [];
	private array $softEnumIndexes = [];
	private array $postfixIndexes = [];
	private array $chainedSubCommandDataIndexes = [];
	private array $chainedSubCommandValueIndexes = [];

	private array $enumValues = [];
	private array $chainedSubCommandValues = [];
	private array $postfixes = [];
	private array $enums = [];
	private array $chainedSubCommandData = [];
	private array $commandData = [];
	private array $softEnums = [];
	private array $enumConstraints = [];

	public static function assemble(
		array $commandData,
		array $hardcodedEnums = [],
		array $hardcodedSoftEnums = [],
		array $hardcodedEnumConstraints = []
	) : v844AvailableCommandsPacket{
		$builder = new self();
		foreach($commandData as $data){
			$builder->addCommandData($data);
		}
		foreach($hardcodedEnums as $enum){
			$builder->addHardEnum($enum);
		}
		foreach($hardcodedSoftEnums as $enum){
			$builder->addSoftEnum($enum);
		}
		foreach($hardcodedEnumConstraints as $constraint){
			$builder->addEnumConstraint($constraint);
		}

		return v844AvailableCommandsPacket::create(
			enumValues: $builder->enumValues,
			chainedSubCommandValues: $builder->chainedSubCommandValues,
			postfixes: $builder->postfixes,
			enums: $builder->enums,
			chainedSubCommandData: $builder->chainedSubCommandData,
			commandData: $builder->commandData,
			softEnums: $builder->softEnums,
			enumConstraints: $builder->enumConstraints
		);
	}

	private function addEnumValue(string $str) : int{
		if(!isset($this->enumValueIndexes[$str])){
			$this->enumValueIndexes[$str] = count($this->enumValues);
			$this->enumValues[] = $str;
		}
		return $this->enumValueIndexes[$str];
	}

	private function addEnum(CommandEnum $enum) : int{
		return $enum->isSoft() ? $this->addSoftEnum($enum) : $this->addHardEnum($enum);
	}

	private function addHardEnum(CommandEnum $enum) : int{
		$key = spl_object_id($enum);
		if(!isset($this->enumIndexes[$key])){
			$valueIndexes = [];

			$enumIndex = $this->enumIndexes[$key] = count($this->enums);

			foreach($enum->getValues() as $value){
				$valueIndexes[] = $this->addEnumValue($value);
			}
			if(count($this->enums) !== $enumIndex){
				throw new LogicException("Didn't expect enum list to be modified while compiling values");
			}
			$this->enums[] = new CommandEnumRawData($enum->getName(), $valueIndexes);
		}

		return $this->enumIndexes[$key];
	}

	private function addSoftEnum(CommandEnum $enum) : int{
		$key = spl_object_id($enum);

		if(!isset($this->softEnumIndexes[$key])){
			$this->softEnumIndexes[$key] = count($this->softEnums);
			$this->softEnums[] = new CommandSoftEnum($enum->getName(), $enum->getValues());
		}

		return $this->softEnumIndexes[$key];
	}

	private function addPostfix(string $postfix) : int{
		if(!isset($this->postfixIndexes[$postfix])){
			$this->postfixIndexes[$postfix] = count($this->postfixes);
			$this->postfixes[] = $postfix;
		}
		return $this->postfixIndexes[$postfix];
	}

	private function addChainedSubCommandValueName(string $valueName) : int{
		if(!isset($this->chainedSubCommandValueIndexes[$valueName])){
			$this->chainedSubCommandValueIndexes[$valueName] = count($this->chainedSubCommandValues);
			$this->chainedSubCommandValues[] = $valueName;
		}
		return $this->chainedSubCommandValueIndexes[$valueName];
	}

	private function addChainedSubCommandData(ChainedSubCommandData $data) : int{
		$key = spl_object_id($data);

		if(!isset($this->chainedSubCommandDataIndexes[$key])){
			$rawValueData = [];
			foreach($data->getValues() as $value){
				$valueNameIndex = $this->addChainedSubCommandValueName($value->getName());
				$rawValueData[] = new ChainedSubCommandValueRawData($valueNameIndex, $value->getType());
			}

			$this->chainedSubCommandDataIndexes[$key] = count($this->chainedSubCommandData);
			$this->chainedSubCommandData[] = new ChainedSubCommandRawData($data->getName(), $rawValueData);
		}

		return $this->chainedSubCommandDataIndexes[$key];
	}

	private function addEnumConstraint(CommandEnumConstraint $constraint) : void{
		$enumIndex = $this->addHardEnum($constraint->getEnum());
		$affectedValueIndex = $this->addEnumValue($constraint->getAffectedValue());
		$this->enumConstraints[] = new CommandEnumConstraintRawData($affectedValueIndex, $enumIndex, $constraint->getConstraints());
	}

	private function addCommandData(CommandData $commandData) : void{
		$aliases = $commandData->getAliases();
		$aliasesIndex = $aliases !== null ? $this->addEnum($aliases) : -1;

		$chainedSubCommandDataIndexes = [];
		foreach($commandData->getChainedSubCommandData() as $chainedSubCommandData){
			$chainedSubCommandDataIndexes[] = $this->addChainedSubCommandData($chainedSubCommandData);
		}

		$rawOverloadData = [];
		foreach($commandData->getOverloads() as $overload){
			$rawParameterData = [];

			foreach($overload->getParameters() as $parameter){
				if($parameter->enum !== null){
					if($parameter->enum->isSoft()){
						$enumIndex = $this->addSoftEnum($parameter->enum);
						$typeInfo = v844AvailableCommandsPacket::ARG_FLAG_SOFT_ENUM | v844AvailableCommandsPacket::ARG_FLAG_VALID | $enumIndex;
					}else{
						$enumIndex = $this->addHardEnum($parameter->enum);
						$typeInfo = v844AvailableCommandsPacket::ARG_FLAG_ENUM | v844AvailableCommandsPacket::ARG_FLAG_VALID | $enumIndex;
					}
				}elseif($parameter->postfix !== null){
					$postfixIndex = $this->addPostfix($parameter->postfix);
					$typeInfo = v844AvailableCommandsPacket::ARG_FLAG_POSTFIX | $postfixIndex;
				}else{
					$typeInfo = v844AvailableCommandsPacket::ARG_FLAG_VALID | ($parameter->paramType & (v844AvailableCommandsPacket::ARG_FLAG_VALID - 1));
				}

				$rawParameterData[] = new CommandParameterRawData($parameter->paramName, $typeInfo, $parameter->isOptional, $parameter->flags);
			}

			$rawOverloadData[] = new CommandOverloadRawData($overload->isChaining(), $rawParameterData);
		}

		$this->commandData[] = new CommandRawData(
			name: $commandData->getName(),
			description: $commandData->getDescription(),
			flags: $commandData->getFlags(),
			permission: CommandPermissions::toName($commandData->getPermission()),
			aliasEnumIndex: $aliasesIndex,
			chainedSubCommandDataIndexes: $chainedSubCommandDataIndexes,
			overloads: $rawOverloadData
		);
	}
}
