<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class MigrateLegacyCrudboosterExtends extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crudbooster:migrate-legacy-extends
        {path=app/Http/Controllers : Cartella da scansionare (ricorsivo)}
        {--apply : Applica le sostituzioni. Senza questo flag, mostra solo un\'anteprima (dry-run)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Riscrive negli extends dei controller generati da interfaccia il vecchio FQCN '
        . 'crocodicstudio\\crudbooster\\controllers\\{CBController,ApiController} con il nuovo '
        . 'App\\Http\\Controllers\\System\\{CBController,ApiController}. Da lanciare sui controller di UN cliente '
        . 'dopo averli copiati nella nuova versione di CustomerHive (docs/refactoring/012..015).';

    /**
     * Mappa vecchio-nome-classe => nuovo FQCN completo.
     */
    private const CLASS_MAP = [
        'CBController' => 'App\\Http\\Controllers\\System\\CBController',
        'ApiController' => 'App\\Http\\Controllers\\System\\ApiController',
    ];

    public function handle(): int
    {
        $path = base_path($this->argument('path'));
        $apply = (bool) $this->option('apply');

        if (!is_dir($path)) {
            $this->error("Cartella non trovata: {$path}");

            return self::FAILURE;
        }

        $this->info($apply
            ? "Applico le sostituzioni in: {$path}"
            : "Anteprima (dry-run) — nessun file verra' modificato. Usa --apply per scrivere davvero. Cartella: {$path}");
        $this->newLine();

        $finder = (new Finder())
            ->files()
            ->in($path)
            ->name('*.php')
            ->exclude('System');

        $changedFiles = 0;
        $skippedNoMatch = 0;
        $lintFailures = [];

        foreach ($finder as $file) {
            $originalContents = $file->getContents();
            $newContents = $originalContents;
            $matchesInFile = [];

            foreach (self::CLASS_MAP as $shortName => $newFqcn) {
                $pattern = '/extends\s+\\\\?crocodicstudio\\\\crudbooster\\\\controllers\\\\' . $shortName . '\b/';
                $replacement = 'extends \\' . $newFqcn;

                $count = 0;
                $newContents = preg_replace($pattern, $replacement, $newContents, -1, $count);
                if ($count > 0) {
                    $matchesInFile[] = "{$shortName} ({$count}x)";
                }
            }

            if ($newContents === $originalContents) {
                $skippedNoMatch++;
                continue;
            }

            $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());
            $this->line("<comment>{$relativePath}</comment>: " . implode(', ', $matchesInFile));

            if ($apply) {
                file_put_contents($file->getRealPath(), $newContents);

                if (function_exists('shell_exec') && ($lint = shell_exec('php -l ' . escapeshellarg($file->getRealPath()) . ' 2>&1'))) {
                    if (strpos($lint, 'No syntax errors detected') === false) {
                        $lintFailures[] = $relativePath;
                        $this->error("  -> php -l FALLITO: " . trim($lint));
                    }
                }
            }

            $changedFiles++;
        }

        $this->newLine();
        $this->info("File con match: {$changedFiles} — invariati: {$skippedNoMatch}.");

        if (!$apply && $changedFiles > 0) {
            $this->comment('Nessuna modifica scritta (dry-run). Rilancia con --apply per applicarle davvero.');
        }

        if ($lintFailures) {
            $this->error('ATTENZIONE: php -l ha fallito su ' . count($lintFailures) . ' file: ' . implode(', ', $lintFailures));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
