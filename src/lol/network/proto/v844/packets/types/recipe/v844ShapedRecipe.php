<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\recipe;

use lol\network\proto\v844\structure\v844CommonTypes;
use lol\network\proto\v844\structure\v844ItemStackSerializer;
use InvalidArgumentException;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use Ramsey\Uuid\UuidInterface;
use function count;

final class v844ShapedRecipe extends v844RecipeWithTypeId
{

	private string $blockName;

	public function __construct(
		int                                 $typeId,
		private string                      $recipeId,
		private array                       $input,
		private array                       $output,
		private UuidInterface               $uuid,
		string                              $blockType,
		private int                         $priority,
		private bool                        $symmetric,
		private v844RecipeUnlockingRequirement $unlockingRequirement,
		private int                         $recipeNetId
	)
	{
		parent::__construct($typeId);

		$rows = count($input);
		if ($rows < 1 || $rows > 3) {
			throw new InvalidArgumentException("Expected 1, 2 or 3 input rows");
		}

		$columns = null;
		foreach ($input as $rowNumber => $row) {
			if ($columns === null) {
				$columns = count($row);
			} elseif (count($row) !== $columns) {
				throw new InvalidArgumentException("Expected each row to be $columns columns, but have " . count($row) . " in row $rowNumber");
			}
		}

		$this->blockName = $blockType;
	}

	public function getRecipeId() : string
	{
		return $this->recipeId;
	}

	public function getWidth() : int
	{
		return count($this->input[0]);
	}

	public function getHeight() : int
	{
		return count($this->input);
	}

	public function getInput() : array
	{
		return $this->input;
	}

	public function getOutput() : array
	{
		return $this->output;
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

	public function isSymmetric() : bool
	{
		return $this->symmetric;
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
		$width = VarInt::readSignedInt($in);
		$height = VarInt::readSignedInt($in);

		$input = [];
		for ($row = 0; $row < $height; ++$row) {
			for ($column = 0; $column < $width; ++$column) {
				$input[$row][$column] = v844CommonTypes::getRecipeIngredient($in);
			}
		}

		$output = [];
		for ($i = 0, $resultCount = VarInt::readUnsignedInt($in); $i < $resultCount; ++$i) {
			$output[] = v844ItemStackSerializer::getItemStackWithoutStackId($in);
		}

		$uuid = CommonTypes::getUUID($in);
		$blockName = CommonTypes::getString($in);
		$priority = VarInt::readSignedInt($in);
		$symmetric = CommonTypes::getBool($in);
		$unlockingRequirement = v844RecipeUnlockingRequirement::read($in);
		$recipeNetId = VarInt::readUnsignedInt($in);

		return new self($recipeType, $recipeId, $input, $output, $uuid, $blockName, $priority, $symmetric, $unlockingRequirement, $recipeNetId);
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->recipeId);
		VarInt::writeSignedInt($out, $this->getWidth());
		VarInt::writeSignedInt($out, $this->getHeight());

		foreach ($this->input as $row) {
			foreach ($row as $ingredient) {
				v844CommonTypes::putRecipeIngredient($out, $ingredient);
			}
		}

		VarInt::writeUnsignedInt($out, count($this->output));
		foreach ($this->output as $item) {
			v844ItemStackSerializer::putItemStackWithoutStackId($out, $item);
		}

		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->blockName);
		VarInt::writeSignedInt($out, $this->priority);
		CommonTypes::putBool($out, $this->symmetric);
		$this->unlockingRequirement->write($out);

		VarInt::writeUnsignedInt($out, $this->recipeNetId);
	}
}
