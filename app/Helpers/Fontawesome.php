<?php
namespace App\Helpers;

/**
 * Elenco icone proposte nel selettore di menu/moduli.
 *
 * Il nome della classe resta "Fontawesome" per compatibilita' con i
 * controller dei clienti che la richiamano, ma l'elenco e' ora quello di
 * Bootstrap Icons (nomi senza prefisso `bi-`, ordinati). Le icone gia'
 * salvate come `fa fa-*` continuano a funzionare: vedi IconMap e
 * public/css/ch-icons-compat.css.
 */
class Fontawesome {
	public static function getIcons() {
		$file = public_path('vendor/bootstrap-icons/bootstrap-icons.json');
		$names = is_file($file) ? array_map('strval', array_keys(json_decode(file_get_contents($file), true) ?: [])) : [];
		sort($names);

		return $names;
	}
}
