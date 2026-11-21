<?php

declare(strict_types=1);

namespace lol\network\proto\v84\crafting;

enum v84CraftingRejection : string
{

	case EmptyOutput = 'the request has no result';

	case ResultMismatch = 'the result does not match the recipe';

	case NoIngredients = 'the request has no ingredients';

	case IngredientsMismatch = 'the ingredients do not match the recipe';

	case MissingIngredients = 'the inventory does not hold the ingredients';

	case UnresolvedResult = 'the recipe result has no modern equivalent';

	case NeedsTable = 'the recipe needs a crafting table';
}
