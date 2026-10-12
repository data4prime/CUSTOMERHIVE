#!/bin/sh
# Entrypoint di PRODUZIONE (immagine docker/Dockerfile.prod). Quello di sviluppo
# e' docker/entrypoint.sh e non c'entra con questo.
#
# L'immagine e' la stessa per tre servizi (app, scheduler, worker): il RUOLO si
# decide dal comando ricevuto. Solo "apache2-foreground" e' il ruolo app e fa
# i controlli + le migrazioni; per qualunque altro comando (schedule:work,
# queue:work, ...) si fa solo exec. Gli altri servizi aspettano l'app con
# depends_on/service_healthy nel compose, non qui.
#
# Ruolo app, in ordine: variabili obbligatorie -> cartelle di storage -> attesa
# del DB -> php artisan migrate --force -> exec Apache. Se un passo fallisce NON
# si esce con errore (la restart policy ripartirebbe in loop senza servire a
# niente): si stampa il motivo su stderr, si resta vivi SENZA avviare Apache e
# l'healthcheck resta rosso (fail-stop). Il primo avvio (admin, dati di base,
# licenza) NON sta qui: vedi "php artisan instance:bootstrap".
#
# Tutti i messaggi hanno il prefisso [entrypoint] su stderr (docker logs).

CHILD=""

log() {
    echo "[entrypoint] $*" >&2
}

# docker stop manda il STOPSIGNAL dell'immagine, che php:apache imposta a
# SIGWINCH (stop graduale di Apache), non SIGTERM: vanno gestiti entrambi (piu'
# INT), altrimenti il PID 1 dell'entrypoint (shell) ignora il segnale e docker
# aspetta il SIGKILL. Il figlio in corso (migrate, attesa DB) viene fermato
# prima di uscire. Dopo l'exec finale il segnale lo riceve Apache.
on_signal() {
    log "segnale ricevuto, esco."
    if [ -n "$CHILD" ]; then
        kill "$CHILD" 2>/dev/null
    fi
    exit 143
}
trap on_signal TERM INT WINCH

# Resta vivo senza fare nulla (mai exit): la salute resta rossa, Apache non parte.
fail_stop() {
    log "ERRORE: $*"
    log "Apache NON viene avviato: il container resta attivo ma non sano. Correggere la causa e ricreare/riavviare il servizio app."
    while :; do
        sleep 1 &
        CHILD=$!
        wait "$CHILD"
    done
}

# Esegue un comando in background e ne aspetta la fine, cosi' il trap puo'
# interromperlo. Ritorna l'exit code del comando.
run_child() {
    "$@" &
    CHILD=$!
    wait "$CHILD"
    rc=$?
    CHILD=""
    return "$rc"
}

# Prova di connessione al DB con PDO (nell'immagine non c'e' il client mysql).
# ATTR_TIMEOUT breve per non restare appesi su un host che non risponde.
db_probe() {
    # shellcheck disable=SC2016  # il codice PHP sta volutamente tra apici singoli
    php -r '
        try {
            new PDO(
                sprintf("mysql:host=%s;port=%d;dbname=%s", getenv("DB_HOST"), (int) (getenv("DB_PORT") ?: 3306), getenv("DB_DATABASE")),
                getenv("DB_USERNAME"),
                (string) getenv("DB_PASSWORD"),
                [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (Throwable $e) {
            fwrite(STDERR, $e->getMessage());
            exit(1);
        }
    ' 2>"$PROBE_ERR"
}

# --- Ruolo: solo "apache2-foreground" e' l'app ------------------------------
if [ "$1" != "apache2-foreground" ]; then
    exec "$@"
fi

# --- a) Controlli di base ----------------------------------------------------
missing=""
[ -n "$APP_KEY" ] || missing="$missing APP_KEY"
[ -n "$DB_HOST" ] || missing="$missing DB_HOST"
[ -n "$DB_DATABASE" ] || missing="$missing DB_DATABASE"
[ -n "$DB_USERNAME" ] || missing="$missing DB_USERNAME"
# La password puo' essere vuota, ma la variabile deve esistere.
[ -n "${DB_PASSWORD+set}" ] || missing="$missing DB_PASSWORD"
if [ -n "$missing" ]; then
    fail_stop "variabili d'ambiente obbligatorie mancanti o vuote:$missing"
fi

# --- b) Cartelle di storage --------------------------------------------------
# Niente chown: il processo e' www-data e non puo' (e non deve) cambiare i
# proprietari. Con un bind mount il proprietario va sistemato sull'host.
for d in \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    storage/app/public \
    storage/app/custom-controllers \
    bootstrap/cache
do
    if ! mkdir -p "$d" 2>/dev/null; then
        fail_stop "impossibile creare '$d' (utente $(id -un), uid $(id -u)). Se storage/ e' un bind mount, sull'host: chown -R $(id -u):$(id -g) <cartella montata su storage>"
    fi
    probe="$d/.entrypoint-write-test.$$"
    if ! ( : > "$probe" ) 2>/dev/null; then
        fail_stop "'$d' non e' scrivibile da $(id -un) (uid $(id -u):$(id -g)). Se storage/ e' un bind mount, sull'host: chown -R $(id -u):$(id -g) <cartella montata su storage>"
    fi
    rm -f "$probe"
done

# --- c) Attesa del DB --------------------------------------------------------
wait_secs="${DB_WAIT_TIMEOUT:-60}"
case "$wait_secs" in
    ''|*[!0-9]*) wait_secs=60 ;;
esac
PROBE_ERR="/tmp/entrypoint-db-probe.$$"
log "attendo il database ${DB_HOST}:${DB_PORT:-3306} (massimo ${wait_secs}s)..."

started=$(date +%s)
while :; do
    if run_child db_probe; then
        break
    fi
    elapsed=$(( $(date +%s) - started ))
    if [ "$elapsed" -ge "$wait_secs" ]; then
        fail_stop "database ${DB_HOST}:${DB_PORT:-3306} non raggiungibile dopo ${wait_secs}s: $(cat "$PROBE_ERR" 2>/dev/null)"
    fi
    sleep 2 &
    CHILD=$!
    wait "$CHILD"
    CHILD=""
done
rm -f "$PROBE_ERR"
log "database raggiungibile."

# --- d) Migrazioni -----------------------------------------------------------
# A ogni avvio dell'app (anche dopo un aggiornamento). Niente config:cache ne'
# route:cache. Se fallisce: fail-stop, niente crash-loop.
log "eseguo le migrazioni (php artisan migrate --force)..."
if ! run_child php artisan migrate --force; then
    fail_stop "php artisan migrate --force e' fallito (vedi l'errore qui sopra)."
fi
log "migrazioni completate."

# --- e) Apache ---------------------------------------------------------------
# exec: Apache diventa PID 1 e riceve direttamente i segnali (docker stop).
log "avvio Apache."
exec "$@"
