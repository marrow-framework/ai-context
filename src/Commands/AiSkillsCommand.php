<?php

declare(strict_types=1);

namespace Marrow\AiContext\Commands;

use Marrow\Console\Command;

/**
 * Publishes Claude Code Skills (`.claude/skills/{name}/SKILL.md`) that
 * encode Marrow-specific best practices as invokable, step-by-step
 * procedures — the "how do I actually build this the Marrow way" counterpart
 * to `ai:context`'s "what does this app currently look like".
 *
 * Each skill is plain Markdown, safe to edit afterward — re-running this
 * command never touches an already-published skill unless you pass
 * `--force`, same convention as `marrow/warden`'s and `marrow/anvil`'s
 * install commands.
 *
 *   php forge ai:skills
 *   php forge ai:skills --only=marrow-crud,marrow-auth
 *   php forge ai:skills --force
 */
class AiSkillsCommand extends Command
{
    private const SKILLS = [
        'marrow-module',
        'marrow-crud',
        'marrow-auth',
        'marrow-service-integration',
        'marrow-ui-component',
    ];

    protected string $signature = 'ai:skills
        {--only= : Comma-separated subset of skills to publish (default: all)}
        {--force : Overwrite already-published skills}';
    protected string $description = 'Publish Claude Code Skills encoding Marrow best practices into .claude/skills/';

    protected function handle(): int
    {
        $requested = $this->resolveSkills();
        if ($requested === null) {
            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $stubsPath = dirname(__DIR__) . '/Stubs/Skills';
        $targetRoot = base_path('.claude/skills');

        foreach ($requested as $skill) {
            $this->writeFile(
                "{$stubsPath}/{$skill}/SKILL.md",
                "{$targetRoot}/{$skill}/SKILL.md",
                $force
            );
        }

        $this->newLine();
        $this->success('Skills published into .claude/skills/.');
        $this->line('   <fg=gray>They\'re invoked automatically by Claude Code when a request matches their description — no slash command needed, though /marrow-crud etc. also works.</>');

        return self::SUCCESS;
    }

    /** @return string[]|null Null means an error was already printed. */
    private function resolveSkills(): ?array
    {
        $option = $this->option('only');

        if ($option === null || trim((string) $option) === '') {
            return self::SKILLS;
        }

        $requested = array_filter(array_map('trim', explode(',', (string) $option)));
        $unknown = array_diff($requested, self::SKILLS);

        if (!empty($unknown)) {
            $this->error('Unknown skill(s): ' . implode(', ', $unknown) . '. Valid: ' . implode(', ', self::SKILLS));
            return null;
        }

        return array_values($requested);
    }

    private function writeFile(string $stubPath, string $targetPath, bool $force): void
    {
        if (!is_file($stubPath)) {
            $this->warn("Skipped (missing stub, package install may be corrupt): {$stubPath}");
            return;
        }

        if (is_file($targetPath) && !$force) {
            $interactiveOverwrite = $this->canPrompt()
                && $this->confirm("{$targetPath} already exists — overwrite it?", false);

            if (!$interactiveOverwrite) {
                $this->warn("Skipped (already exists): {$targetPath} — pass --force to overwrite.");
                return;
            }
        }

        @mkdir(dirname($targetPath), 0755, true);
        copy($stubPath, $targetPath);
        $this->success("Created: {$targetPath}");
    }
}
