<?php

declare(strict_types=1);

namespace lol\network\assemble\auth;

final class AuthenticationData
{
	public string $displayName;

	public string $identity;

	public string $sandboxId = "RETAIL";

	public ?string $titleId = "";

	public string $XUID = "";

}
