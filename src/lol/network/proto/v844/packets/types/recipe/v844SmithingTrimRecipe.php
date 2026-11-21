<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\recipe;

use lol\network\proto\v844\structure\v844CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;

final class v844SmithingTrimRecipe extends v844RecipeWithTypeId
{

	public function __construct(
		int                      $typeId,
		private string           $recipeId,
		private RecipeIngredient $template,
		private RecipeIngredient $input,
		private RecipeIngredient $addition,
		private string           $blockName,
		private int              $recipeNetId
	)
	{
		parent::__construct($typeId);
	}

	public function getRecipeId() : string
	{
		return $this->recipeId;
	}

	public function getTemplate() : RecipeIngredient
	{
		return $this->template;
	}

	public function getInput() : RecipeIngredient
	{
		return $this->input;
	}

	public function getAddition() : RecipeIngredient
	{
		return $this->addition;
	}

	public function getBlockName() : string
	{
		return $this->blockName;
	}

	public function getRecipeNetId() : int
	{
		return $this->recipeNetId;
	}

	public static function read(int $typeId, ByteBufferReader $in) : self
	{
		$recipeId = CommonTypes::getString($in);
		$template = v844CommonTypes::getRecipeIngredient($in);
		$input = v844CommonTypes::getRecipeIngredient($in);
		$addition = v844CommonTypes::getRecipeIngredient($in);
		$blockName = CommonTypes::getString($in);
		$recipeNetId = VarInt::readUnsignedInt($in);

		return new self($typeId, $recipeId, $template, $input, $addition, $blockName, $recipeNetId);
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->recipeId);
		v844CommonTypes::putRecipeIngredient($out, $this->template);
		v844CommonTypes::putRecipeIngredient($out, $this->input);
		v844CommonTypes::putRecipeIngredient($out, $this->addition);
		CommonTypes::putString($out, $this->blockName);
		VarInt::writeUnsignedInt($out, $this->recipeNetId);
	}
}
