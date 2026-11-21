<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\recipe;

use lol\network\proto\v844\structure\v844CommonTypes;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use Ramsey\Uuid\UuidInterface;
use function count;

final class v844ShapelessRecipe extends v844RecipeWithTypeId
{

	public function __construct(
		int                                 $typeId,
		private string                      $recipeId,
		private array                       $inputs,
		private array                       $outputs,
		private UuidInterface               $uuid,
		private string                      $blockName,
		private int                         $priority,
		private v844RecipeUnlockingRequirement $unlockingRequirement,
		private int                         $recipeNetId
	)
	{
		parent::__construct($typeId);
	}

	public function getRecipeId() : string
	{
		return $this->recipeId;
	}

	public function getInputs() : array
	{
		return $this->inputs;
	}

	public function getOutputs() : array
	{
		return $this->outputs;
	}

	public function getUuid() : UuidInterface
	{
		return $this->uuid;
	}

	public function getBlockName() : string
	{
		return $this->blockName;
	}

	public function getPriority() : int
	{
		return $this->priority;
	}

	public function getUnlockingRequirement() : v844RecipeUnlockingRequirement
	{
		return $this->unlockingRequirement;
	}

	public function getRecipeNetId() : int
	{
		return $this->recipeNetId;
	}

	public static function read(int $recipeType, ByteBufferReader $in) : self
	{
		$recipeId = CommonTypes::getString($in);

		$inputs = [];
		for ($i = 0, $ingredientCount = VarInt::readUnsignedInt($in); $i < $ingredientCount; ++$i) {
			$inputs[] = v844CommonTypes::getRecipeIngredient($in);
		}

		$outputs = [];
		for ($i = 0, $resultCount = VarInt::readUnsignedInt($in); $i < $resultCount; ++$i) {
			$outputs[] = v844ItemStackSerializer::getItemStackWithoutStackId($in);
		}

		$uuid = CommonTypes::getUUID($in);
		$blockName = CommonTypes::getString($in);
		$priority = VarInt::readSignedInt($in);
		$unlockingRequirement = v844RecipeUnlockingRequirement::read($in);
		$recipeNetId = VarInt::readUnsignedInt($in);

		return new self($recipeType, $recipeId, $inputs, $outputs, $uuid, $blockName, $priority, $unlockingRequirement, $recipeNetId);
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->recipeId);

		VarInt::writeUnsignedInt($out, count($this->inputs));
		foreach ($this->inputs as $input) {
			v844CommonTypes::putRecipeIngredient($out, $input);
		}

		VarInt::writeUnsignedInt($out, count($this->outputs));
		foreach ($this->outputs as $output) {
			v844ItemStackSerializer::putItemStackWithoutStackId($out, $output);
		}

		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->blockName);
		VarInt::writeSignedInt($out, $this->priority);
		$this->unlockingRequirement->write($out);

		VarInt::writeUnsignedInt($out, $this->recipeNetId);
	}
}
