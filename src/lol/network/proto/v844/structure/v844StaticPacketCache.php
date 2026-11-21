<?php

declare(strict_types=1);

namespace lol\network\proto\v844\structure;

use lol\Loader;
use lol\network\proto\v844\packets\types\biome\v844BiomeDefinitionEntry;
use lol\network\proto\v844\packets\types\recipe\v844FurnaceRecipe;
use lol\network\proto\v844\packets\types\recipe\v844IntIdMetaItemDescriptor;
use lol\network\proto\v844\packets\types\recipe\v844RecipeUnlockingRequirement;
use lol\network\proto\v844\packets\types\recipe\v844ShapedRecipe;
use lol\network\proto\v844\packets\types\recipe\v844ShapelessRecipe;
use lol\network\proto\v844\packets\v844BiomeDefinitionListPacket;
use lol\network\proto\v844\packets\v844CraftingDataPacket;
use lol\network\utils\PacketCachedTrait;
use lol\network\utils\ProtocolUtils;
use InvalidArgumentException;
use JsonMapper;
use JsonMapper_Exception;
use pmmp\encoding\BE;
use pocketmine\color\Color;
use pocketmine\crafting\FurnaceType;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\crafting\ShapelessRecipeType;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\types\recipe\CraftingRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\FurnaceRecipeBlockName;
use pocketmine\network\mcpe\protocol\types\recipe\NameItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\PotionContainerChangeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\PotionTypeRecipe;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient as ProtocolRecipeIngredient;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\timings\Timings;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\biome\model\BiomeDefinitionEntryData;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function array_map;
use function count;
use function get_debug_type;
use function is_array;
use function json_decode;

class v844StaticPacketCache
{
	use PacketCachedTrait;

	private const RECIPE_ID_OFFSET = 1;

	private v844BiomeDefinitionListPacket $biomeDefinitionListPacket;
	private AvailableActorIdentifiersPacket $actorIdentifiersPacket;

	private ?v844CraftingDataPacket $craftingDataPacket = null;
	private bool $craftingInvalidationHookRegistered = false;

	public function getBiomeDefinitionListPacket() : v844BiomeDefinitionListPacket
	{
		return $this->biomeDefinitionListPacket;
	}

	public function getActorIdentifiersPacket() : AvailableActorIdentifiersPacket
	{
		return $this->actorIdentifiersPacket;
	}

	public function getCraftingDataPacket() : v844CraftingDataPacket
	{
		if (!$this->craftingInvalidationHookRegistered) {
			$this->protocol->getCraftingManager()->getRecipeRegisteredCallbacks()->add(function() : void {
				$this->craftingDataPacket = null;
			});
			$this->craftingInvalidationHookRegistered = true;
		}

		return $this->craftingDataPacket ??= $this->buildCraftingDataCache();
	}

	protected function load() : void
	{
		$this->biomeDefinitionListPacket = v844BiomeDefinitionListPacket::fromDefinitions(self::loadBiomeDefinitionModel(self::path("biome_definitions.json")));
		$this->actorIdentifiersPacket = AvailableActorIdentifiersPacket::create(ProtocolUtils::loadCacheableFromFile(self::path("entity_identifiers.nbt")));
	}

	private static function path(string $filename) : string
	{
		return Path::join(Loader::getPluginResourcePath(), "v844", $filename);
	}

	protected function areNetItemsSupported(array $items) : bool
	{
		return true;
	}

	private static function craftingIngredientToWire(ProtocolRecipeIngredient $ingredient, ItemTypeDictionary $itemTypeDictionary) : ProtocolRecipeIngredient
	{
		$descriptor = $ingredient->getDescriptor();
		if (!$descriptor instanceof NameItemDescriptor) {
			return $ingredient;
		}

		$id = $itemTypeDictionary->fromStringId($descriptor->getName());

		return new ProtocolRecipeIngredient(new v844IntIdMetaItemDescriptor($id, $descriptor->getAuxValue()), $ingredient->getCount());
	}

	private function buildCraftingDataCache() : v844CraftingDataPacket
	{
		Timings::$craftingDataCacheRebuild->startTiming();

		$manager = $this->protocol->getCraftingManager();
		$converter = $this->protocol->getTypeConverter();
		$itemTypeDictionary = $converter->getItemTypeDictionary();
		$nullUUID = Uuid::fromString(Uuid::NIL);
		$noUnlockingRequirement = v844RecipeUnlockingRequirement::none();

		$recipesWithTypeIds = [];

		$skippedCraftingRecipes = 0;
		foreach ($manager->getCraftingRecipeIndex() as $index => $recipe) {
			$recipeNetId = $index + self::RECIPE_ID_OFFSET;

			try {
				if ($recipe instanceof ShapelessRecipe) {
					$typeTag = match ($recipe->getType()) {
						ShapelessRecipeType::CRAFTING => CraftingRecipeBlockName::CRAFTING_TABLE,
						ShapelessRecipeType::STONECUTTER => CraftingRecipeBlockName::STONECUTTER,
						ShapelessRecipeType::CARTOGRAPHY => CraftingRecipeBlockName::CARTOGRAPHY_TABLE,
						ShapelessRecipeType::SMITHING => CraftingRecipeBlockName::SMITHING_TABLE,
					};
					$inputs = array_map(
						static fn($ingredient) => self::craftingIngredientToWire($converter->coreRecipeIngredientToNet($ingredient), $itemTypeDictionary),
						$recipe->getIngredientList()
					);
					$results = array_map($converter->coreItemStackToNet(...), $recipe->getResults());
					if (!$this->areNetItemsSupported($results)) {
						++$skippedCraftingRecipes;
						continue;
					}
					$recipesWithTypeIds[] = new v844ShapelessRecipe(
						v844CraftingDataPacket::ENTRY_SHAPELESS,
						BE::packUnsignedInt($recipeNetId),
						$inputs,
						$results,
						$nullUUID,
						$typeTag,
						50,
						$noUnlockingRequirement,
						$recipeNetId
					);
				} elseif ($recipe instanceof ShapedRecipe) {
					$inputs = [];
					for ($row = 0, $height = $recipe->getHeight(); $row < $height; ++$row) {
						for ($column = 0, $width = $recipe->getWidth(); $column < $width; ++$column) {
							$inputs[$row][$column] = self::craftingIngredientToWire($converter->coreRecipeIngredientToNet($recipe->getIngredient($column, $row)), $itemTypeDictionary);
						}
					}
					$results = array_map($converter->coreItemStackToNet(...), $recipe->getResults());
					if (!$this->areNetItemsSupported($results)) {
						++$skippedCraftingRecipes;
						continue;
					}
					$recipesWithTypeIds[] = new v844ShapedRecipe(
						v844CraftingDataPacket::ENTRY_SHAPED,
						BE::packUnsignedInt($recipeNetId),
						$inputs,
						$results,
						$nullUUID,
						CraftingRecipeBlockName::CRAFTING_TABLE,
						50,
						true,
						$noUnlockingRequirement,
						$recipeNetId
					);
				}
			} catch (ItemTypeSerializeException|InvalidArgumentException $e) {
				++$skippedCraftingRecipes;
				v844Debug::logOnce("craftingdata:crafting-skip:" . $e->getMessage(), "CraftingData", "skipped a crafting recipe (net id $recipeNetId): " . $e->getMessage());
			}
		}

		if ($skippedCraftingRecipes > 0) {
			v844Debug::logOnce(
				"craftingdata:crafting-skipped-total",
				"CraftingData",
				"skipped $skippedCraftingRecipes crafting recipe(s) referencing an item unmapped in the 844 item dictionary"
			);
		}

		$skippedFurnaceRecipes = 0;
		foreach (FurnaceType::cases() as $furnaceType) {
			$typeTag = match ($furnaceType) {
				FurnaceType::FURNACE => FurnaceRecipeBlockName::FURNACE,
				FurnaceType::BLAST_FURNACE => FurnaceRecipeBlockName::BLAST_FURNACE,
				FurnaceType::SMOKER => FurnaceRecipeBlockName::SMOKER,
				FurnaceType::CAMPFIRE => FurnaceRecipeBlockName::CAMPFIRE,
				FurnaceType::SOUL_CAMPFIRE => FurnaceRecipeBlockName::SOUL_CAMPFIRE,
			};

			foreach ($manager->getFurnaceRecipeManager($furnaceType)->getAll() as $recipe) {
				try {
					$descriptor = $converter->coreRecipeIngredientToNet($recipe->getInput())->getDescriptor();
					if (!$descriptor instanceof NameItemDescriptor) {
						++$skippedFurnaceRecipes;
						continue;
					}

					$inputId = $itemTypeDictionary->fromStringId($descriptor->getName());
				} catch (ItemTypeSerializeException|InvalidArgumentException) {
					++$skippedFurnaceRecipes;
					continue;
				}

				$furnaceResult = $converter->coreItemStackToNet($recipe->getResult());
				if (!$this->areNetItemsSupported([$furnaceResult])) {
					++$skippedFurnaceRecipes;
					continue;
				}

				$recipesWithTypeIds[] = new v844FurnaceRecipe(
					v844CraftingDataPacket::ENTRY_FURNACE_DATA,
					$inputId,
					$descriptor->getAuxValue(),
					$furnaceResult,
					$typeTag
				);
			}
		}

		if ($skippedFurnaceRecipes > 0) {
			v844Debug::logOnce(
				"craftingdata:furnace-skipped",
				"CraftingData",
				"skipped $skippedFurnaceRecipes furnace recipe(s) whose input has no numeric id in the 844 item dictionary"
			);
		}

		$skippedPotionTypeRecipes = 0;
		$potionTypeRecipes = [];
		foreach ($manager->getPotionTypeRecipes() as $recipe) {
			try {
				$input = $converter->coreRecipeIngredientToNet($recipe->getInput())->getDescriptor();
				$ingredient = $converter->coreRecipeIngredientToNet($recipe->getIngredient())->getDescriptor();
				if (!$input instanceof NameItemDescriptor || !$ingredient instanceof NameItemDescriptor) {
					++$skippedPotionTypeRecipes;
					continue;
				}

				$inputId = $itemTypeDictionary->fromStringId($input->getName());
				$ingredientId = $itemTypeDictionary->fromStringId($ingredient->getName());
			} catch (ItemTypeSerializeException|InvalidArgumentException) {
				++$skippedPotionTypeRecipes;
				continue;
			}

			$output = $converter->coreItemStackToNet($recipe->getOutput());
			if (!$this->areNetItemsSupported([$output])) {
				++$skippedPotionTypeRecipes;
				continue;
			}

			$potionTypeRecipes[] = new PotionTypeRecipe(
				$inputId,
				$input->getAuxValue(),
				$ingredientId,
				$ingredient->getAuxValue(),
				$output->getId(),
				$output->getMeta()
			);
		}

		if ($skippedPotionTypeRecipes > 0) {
			v844Debug::logOnce(
				"craftingdata:potiontype-skipped",
				"CraftingData",
				"skipped $skippedPotionTypeRecipes potion type recipe(s) whose input/ingredient has no numeric id in the 844 item dictionary"
			);
		}

		$skippedPotionContainerRecipes = 0;
		$potionContainerChangeRecipes = [];
		foreach ($manager->getPotionContainerChangeRecipes() as $recipe) {
			try {
				$ingredient = $converter->coreRecipeIngredientToNet($recipe->getIngredient())->getDescriptor();
				if (!$ingredient instanceof NameItemDescriptor) {
					++$skippedPotionContainerRecipes;
					continue;
				}

				$potionContainerChangeRecipes[] = new PotionContainerChangeRecipe(
					$itemTypeDictionary->fromStringId($recipe->getInputItemId()),
					$itemTypeDictionary->fromStringId($ingredient->getName()),
					$itemTypeDictionary->fromStringId($recipe->getOutputItemId())
				);
			} catch (ItemTypeSerializeException|InvalidArgumentException) {
				++$skippedPotionContainerRecipes;
				continue;
			}
		}

		if ($skippedPotionContainerRecipes > 0) {
			v844Debug::logOnce(
				"craftingdata:potioncontainer-skipped",
				"CraftingData",
				"skipped $skippedPotionContainerRecipes potion container recipe(s) referencing an item unmapped in the 844 item dictionary"
			);
		}

		Timings::$craftingDataCacheRebuild->stopTiming();

		v844Debug::log("CraftingData", "built " . count($recipesWithTypeIds) . " recipe(s), " . count($potionTypeRecipes) . " potion recipe(s) and " . count($potionContainerChangeRecipes) . " potion container recipe(s) for 844");

		return v844CraftingDataPacket::v844create($recipesWithTypeIds, $potionTypeRecipes, $potionContainerChangeRecipes, [], true);
	}

	private static function loadBiomeDefinitionModel(string $filePath) : array
	{
		$biomeEntries = json_decode(Filesystem::fileGetContents($filePath), associative: true);
		if (!is_array($biomeEntries)) {
			throw new SavedDataLoadingException("$filePath root should be an array, got " . get_debug_type($biomeEntries));
		}

		$jsonMapper = new JsonMapper();
		$jsonMapper->bExceptionOnMissingData = true;
		$jsonMapper->bStrictObjectTypeChecking = true;
		$jsonMapper->bEnforceMapType = false;

		$entries = [];
		foreach (Utils::promoteKeys($biomeEntries) as $biomeName => $entry) {
			if (!is_array($entry)) {
				throw new SavedDataLoadingException("$filePath should be an array of objects, got " . get_debug_type($entry));
			}

			try {
				$biomeDefinition = $jsonMapper->map($entry, new BiomeDefinitionEntryData());

				$mapWaterColour = $biomeDefinition->mapWaterColour;
				$entries[] = new v844BiomeDefinitionEntry(
					(string) $biomeName,
					$biomeDefinition->id,
					$biomeDefinition->temperature,
					$biomeDefinition->downfall,
					$biomeDefinition->foliageSnow,
					$biomeDefinition->depth,
					$biomeDefinition->scale,
					new Color(
						$mapWaterColour->r,
						$mapWaterColour->g,
						$mapWaterColour->b,
						$mapWaterColour->a
					),
					$biomeDefinition->rain,
					count($biomeDefinition->tags) > 0 ? $biomeDefinition->tags : null,
				);
			} catch (JsonMapper_Exception $e) {
				throw new RuntimeException($e->getMessage(), 0, $e);
			}
		}

		return $entries;
	}
}
