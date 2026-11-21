<?php

declare(strict_types=1);

namespace lol\network\proto\v84\forms;

final class v84FormState
{

	public const MODAL = 'modal';

	public const SIMPLE = 'form';

	public const CUSTOM = 'custom_form';

	public array $answers = [];

	public int $stage = 0;

	public function __construct(
		public readonly int $formId,
		public readonly string $kind,
		public readonly array $form,
		public readonly float $expiresAt
	)
	{
	}
}
