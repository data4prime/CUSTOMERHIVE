<?php

namespace App\Services\QlikSync;

use RuntimeException;

/**
 * Errore della sincronizzazione con messaggio gia' pulito (mai token o
 * segreti): e' il testo che finisce nel report del run.
 */
class QlikSyncException extends RuntimeException
{
}
