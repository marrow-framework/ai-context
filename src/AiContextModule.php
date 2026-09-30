<?php

declare(strict_types=1);

namespace Marrow\AiContext;

use Marrow\AiContext\Commands\AiContextCommand;
use Marrow\Module\Attributes\Module;
use Marrow\Module\BaseModule;

/**
 * Registers `php forge ai:context`. Auto-discovered the same way as every
 * other package module (see docs/modules.md#distributing-a-module-as-a-package)
 * — no config/modules.php entry needed.
 */
#[Module(name: 'ai-context', commands: [AiContextCommand::class])]
class AiContextModule extends BaseModule
{
}
