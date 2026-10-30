<?php

namespace App\Services\ChatAI;

/**
 * Parla con l'agente ChatAI (workflow esterno) per conto della chat.
 *
 * Non legge sessione, richiesta ne' CRUDBooster: riceve tutto per parametro, cosi'
 * il controller decide cosa fare del risultato (sessione, storico, echo).
 * Piano 331.
 */
class ChatAIClient
{
    /**
     * $url, $message e $userId senza tipo di proposito: il codice di prima non li
     * tipizzava e il payload deve restare identico per qualunque valore ricevuto.
     *
     * @param  mixed  $url
     * @param  mixed  $message
     * @param  mixed  $userId  id dell'utente corrente (CRUDBooster::myId())
     * @return array{raw: string, message: mixed} raw = cio' che il controller stampa;
     *         message = cio' che finisce in sessione e nello storico
     */
    public function send($url, string $token, $message, $userId): array
    {
        // I doppi apici dentro sessionId sono voluti: l'agente li riceve cosi' da sempre
        $data = [
            "action" => "sendMessage",
            "sessionId" => '"' . $userId . '"',
            "chatInput" => $message,
        ];

        $ch = curl_init($url);

        // Nessun timeout e nessun curl_close di proposito (timeout rimandato, piano 331 D2;
        // curl_close e' deprecato in PHP 8.5, doc 323)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            "Authorization: Bearer $token",
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        return $this->interpret(curl_exec($ch));
    }

    /**
     * Legge la risposta grezza dell'agente. Pubblica per poterla provare senza rete.
     *
     * @return array{raw: string, message: mixed}
     */
    public function interpret(string|false $raw): array
    {
        if ($raw === false) {
            $message = trans('crudbooster.chatai_request_error');

            return ['raw' => json_encode(["message" => $message]), 'message' => $message];
        }

        $decoded = json_decode($raw, true);

        if (isset($decoded["text"])) {
            return ['raw' => $raw, 'message' => $decoded["text"]];
        }

        if (isset($decoded["message"])) {
            return ['raw' => $raw, 'message' => $decoded["message"]];
        }

        // Ne' text ne' message (o non JSON): il JS legge sempre data.message
        return ['raw' => json_encode(["message" => $decoded]), 'message' => $decoded];
    }
}
