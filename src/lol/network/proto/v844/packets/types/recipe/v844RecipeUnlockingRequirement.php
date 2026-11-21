<?php

declare(strict_types=1);

namespace lol\network\proto\v844\packets\types\recipe;

use lol\network\proto\v844\structure\v844CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use function count;

final class v844RecipeUnlockingRequirement
{

	public function __construct(
		private ?array $unlockingIngredients
	)
	{

	}

	public static function none() : self
	{
		return new self(null);
	}

	public function getUnlockingIngredients() : ?array
	{
		return $this->unlockingIngredients;
	}

	public static function read(ByteBufferReader $in) : self
	{
		$unlockingContext = CommonTypes::getBool($in);
		$unlockingIngredients = null;
		if (!$unlockingContext) {
			$unlockingIngredients = [];
			for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
				$unlockingIngredients[] = v844CommonTypes::getRecipeIngredient($in);
			}
		}

		return new self($unlockingIngredients);
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::putBool($out, $this->unlockingIngredients === null);
		if ($this->unlockingIngredients !== null) {
			VarInt::writeUnsignedInt($out, count($this->unlockingIngredients));
			foreach ($this->unlockingIngredients as $ingredient) {
				v844CommonTypes::putRecipeIngredient($out, $ingredient);
			}
		}
	}
}
