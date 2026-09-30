<?php

namespace App\Services\QlikSync;

/**
 * Elenca app e fogli di una configurazione Qlik, con l'identita' (JWT)
 * dell'utente che ha lanciato la sincronizzazione. Un'implementazione per
 * SaaS e una per On-Premise (docs/piano-qlik-sync-app-items.md).
 */
interface QlikDriver
{
    /**
     * @return array<int, array{id:string, name:string}> id = appid Qlik
     *
     * @throws QlikSyncException
     */
    public function listApps(): array;

    /**
     * @return array<int, array{id:string, title:string, description:string}> id = id del foglio
     *
     * @throws QlikSyncException
     */
    public function listSheets(string $appId): array;

    /**
     * URL con cui un item punta al foglio: lo stesso formato che si scrive a
     * mano nel campo "Url" dell'item (viene usato come src dell'iframe).
     */
    public function sheetUrl(string $appId, string $sheetId): string;

    /**
     * Sottostringa che, cercata nell'URL di un item creato a mano, lo
     * identifica come lo stesso foglio (per agganciarlo invece di duplicarlo).
     */
    public function sheetUrlNeedle(string $appId, string $sheetId): string;
}
