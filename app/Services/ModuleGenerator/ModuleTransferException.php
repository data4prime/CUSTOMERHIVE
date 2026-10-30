<?php

namespace App\Services\ModuleGenerator;

use RuntimeException;

/**
 * Errore di dominio di export/import di un modulo: il messaggio e' gia'
 * tradotto, il tipo ('warning'|'danger') e' quello del flash message che il
 * controller mostra (ModulsController::getExport()/postImport()).
 */
class ModuleTransferException extends RuntimeException
{
    private string $type;

    public function __construct(string $message, string $type = 'warning')
    {
        parent::__construct($message);

        $this->type = $type;
    }

    public function type(): string
    {
        return $this->type;
    }
}
