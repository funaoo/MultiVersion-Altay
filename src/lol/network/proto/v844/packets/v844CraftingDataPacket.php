<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets;

use lol\network\proto\v844\packets\types\recipe\v844FurnaceRecipe;
use lol\network\proto\v844\packets\types\recipe\v844MultiRecipe;
use lol\network\proto\v844\packets\types\recipe\v844RecipeWithTypeId;
use lol\network\proto\v844\packets\types\recipe\v844ShapedRecipe;
use lol\network\proto\v844\packets\types\recipe\v844ShapelessRecipe;
use lol\network\proto\v844\packets\types\recipe\v844SmithingTransformRecipe;
use lol\network\proto\v844\packets\types\recipe\v844SmithingTrimRecipe;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\MaterialReducerRecipeOutput;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe;
use function count;

class v844CraftingDataPacket extends CraftingDataPacket
{

	public const ENTRY_FURNACE = 2;
	public const ENTRY_FURNACE_DATA = 3;

	private array $v844Recipes = [];
	private array $v844PotionTypeRecipes = [];
	private array $v844PotionContainerRecipes = [];
	private array $v844MaterialReducerRecipes = [];
	private bool $v844CleanRecipes = false;

	public static function v844create(
		array $recipes,
		array $potionTypeRecipes,
		array $potionContainerRecipes,
		array $materialReducerRecipes,
		bool  $cleanRecipes
	) : self
	{
		$result = new self();
		$result->v844Recipes = $recipes;
		$result->v844PotionTypeRecipes = $potionTypeRecipes;
		$result->v844PotionContainerRecipes = $potionContainerRecipes;
		$result->v844MaterialReducerRecipes = $materialReducerRecipes;
		$result->v844CleanRecipes = $cleanRecipes;

		return $result;
	}

	public function getV844Recipes() : array
	{
		return $this->v844Recipes;
	}

	public function getV844PotionTypeRecipes() : array
	{
		return $this->v844PotionTypeRecipes;
	}

	public function getV844PotionContainerRecipes() : array
	{
		return $this->v844PotionContainerRecipes;
	}

	public function getV844MaterialReducerRecipes() : array
	{
		return $this->v844MaterialReducerRecipes;
	}

	public function isV844CleanRecipes() : bool
	{
		return $this->v844CleanRecipes;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$recipes = [];
		$previousType = "none";
		for ($i = 0, $recipeCount = VarInt::readUnsignedInt($in); $i < $recipeCount; ++$i) {
			$recipeType = VarInt::readSignedInt($in);
			$recipes[] = match ($recipeType) {
				self::ENTRY_SHAPELESS, self::ENTRY_USER_DATA_SHAPELESS, self::ENTRY_SHAPELESS_CHEMISTRY => v844ShapelessRecipe::read($recipeType, $in),
				self::ENTRY_SHAPED, self::ENTRY_SHAPED_CHEMISTRY => v844ShapedRecipe::read($recipeType, $in),
				self::ENTRY_FURNACE, self::ENTRY_FURNACE_DATA => v844FurnaceRecipe::read($recipeType, $in),
				self::ENTRY_MULTI => v844MultiRecipe::read($recipeType, $in),
				self::ENTRY_SMITHING_TRANSFORM => v844SmithingTransformRecipe::read($recipeType, $in),
				self::ENTRY_SMITHING_TRIM => v844SmithingTrimRecipe::read($recipeType, $in),
				default => throw new PacketDecodeException("Unhandled recipe type $recipeType (previous was $previousType)"),
			};
			$previousType = (string) $recipeType;
		}
		$this->v844Recipes = $recipes;

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$inputId = VarInt::readSignedInt($in);
			$inputMeta = VarInt::readSignedInt($in);
			$ingredientId = VarInt::readSignedInt($in);
			$ingredientMeta = VarInt::readSignedInt($in);
			$outputId = VarInt::readSignedInt($in);
			$outputMeta = VarInt::readSignedInt($in);
			$this->v844PotionTypeRecipes[] = new PotionTypeRecipe($inputId, $inputMeta, $ingredientId, $ingredientMeta, $outputId, $outputMeta);
		}

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$input = VarInt::readSignedInt($in);
			$ingredient = VarInt::readSignedInt($in);
			$output = VarInt::readSignedInt($in);
			$this->v844PotionContainerRecipes[] = new PotionContainerChangeRecipe($input, $ingredient, $output);
		}

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$inputIdAndData = VarInt::readSignedInt($in);
			[$inputId, $inputMeta] = [$inputIdAndData >> 16, $inputIdAndData & 0x7fff];
			$outputs = [];
			for ($j = 0, $outputCount = VarInt::readUnsignedInt($in); $j < $outputCount; ++$j) {
				$outputItemId = VarInt::readSignedInt($in);
				$outputItemCount = VarInt::readSignedInt($in);
				$outputs[] = new MaterialReducerRecipeOutput($outputItemId, $outputItemCount);
			}
			$this->v844MaterialReducerRecipes[] = new MaterialReducerRecipe($inputId, $inputMeta, $outputs);
		}

		$this->v844CleanRecipes = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$this->writeRecipeList($out);
		$this->writePotionTypeRecipes($out);
		$this->writePotionContainerRecipes($out);
		$this->writeMaterialReducerRecipes($out);
		CommonTypes::putBool($out, $this->v844CleanRecipes);
	}

	private function writeRecipeList(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->v844Recipes));
		foreach ($this->v844Recipes as $recipe) {
			VarInt::writeSignedInt($out, $recipe->getTypeId());
			$recipe->write($out);
		}
	}

	private function writePotionTypeRecipes(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->v844PotionTypeRecipes));
		foreach ($this->v844PotionTypeRecipes as $recipe) {
			VarInt::writeSignedInt($out, $recipe->getInputItemId());
			VarInt::writeSignedInt($out, $recipe->getInputItemMeta());
			VarInt::writeSignedInt($out, $recipe->getIngredientItemId());
			VarInt::writeSignedInt($out, $recipe->getIngredientItemMeta());
			VarInt::writeSignedInt($out, $recipe->getOutputItemId());
			VarInt::writeSignedInt($out, $recipe->getOutputItemMeta());
		}
	}

	private function writePotionContainerRecipes(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->v844PotionContainerRecipes));
		foreach ($this->v844PotionContainerRecipes as $recipe) {
			VarInt::writeSignedInt($out, $recipe->getInputItemId());
			VarInt::writeSignedInt($out, $recipe->getIngredientItemId());
			VarInt::writeSignedInt($out, $recipe->getOutputItemId());
		}
	}

	private function writeMaterialReducerRecipes(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->v844MaterialReducerRecipes));
		foreach ($this->v844MaterialReducerRecipes as $recipe) {
			VarInt::writeSignedInt($out, ($recipe->getInputItemId() << 16) | $recipe->getInputItemMeta());
			VarInt::writeUnsignedInt($out, count($recipe->getOutputs()));
			foreach ($recipe->getOutputs() as $output) {
				VarInt::writeSignedInt($out, $output->getItemId());
				VarInt::writeSignedInt($out, $output->getCount());
			}
		}
	}
}
