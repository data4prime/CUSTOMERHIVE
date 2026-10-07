<?php

namespace App\Helpers;

use Illuminate\Support\Facades\View;

/**
 * Pagine nuovo/modifica/dettaglio con intestazione propria (briciole, titolo,
 * azioni a destra) e senza la card esterna: stile dei mockup di
 * amministrazione (intervento 232). Si attiva da cbInit() di un controller:
 *
 *   FlatForm::share(['Nuovo X', 'Modifica X', 'Dettaglio X'], 'users.form_actions');
 *
 * Condivide $flat_form_header (+ titoli e azioni) con le viste, che lo usano
 * solo se presente: senza questa chiamata le pagine restano come prima.
 */
class FlatForm
{
    /**
     * @param array       $titles  [nuovo, modifica, dettaglio]
     * @param string|null $actions vista con le azioni a destra del titolo (riceve $part = 'button' | 'after')
     */
    public static function share(array $titles, $actions = null)
    {
        if (!in_array(CRUDBooster::getCurrentMethod(), ['getEdit', 'getAdd', 'getDetail'], true)) {
            return;
        }
        View::share('flat_form_header', 'crudbooster::partials.flat_form_header');
        View::share('flat_form_titles', $titles);
        View::share('flat_form_actions', $actions);
    }
}
