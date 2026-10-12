<?php

namespace App\Console\Commands;

use App\Helpers\TenantHelper;
use App\Jobs\SendTemplateEmailJob;
use CRUDBooster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UserExpiryNotification extends Command
{
    private const USERS_PLACEHOLDER = '|CUSTOMFUNCTION|TenantHelper::build_utenti_scadenza_tenantadmin|CUSTOMFUNCTIONEND|';

    /** Giorni di anticipo con cui si avvisa della scadenza di un account. */
    public const DAYS_BEFORE_EXPIRY = [30, 7, 1];

    /** Privilegi (superadmin, tenant admin) non mostrati nell'elenco ai tenant admin. */
    public const PRIVILEGES_HIDDEN_FROM_TENANT_ADMINS = [1, 2];

    private const SUPERADMIN_PRIVILEGE = 1;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:expiry-notification {--force : Reinvia anche gli avvisi gia\' inviati oggi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to users whose account is about to expire';

    /**
     * Execute the console command.
     *
     * Ogni email e' un SendTemplateEmailJob (coda 'mail' se
     * queue.mail_connection non e' 'sync'). La tabella degli utenti in
     * scadenza viene passata al job come sostituzione del segnaposto nel
     * contenuto, senza piu' modificare il template nel DB durante l'invio.
     * Ogni coppia destinatario/template si invia una sola volta al giorno
     * (rilanciare il comando a mano non duplica le mail), salvo --force.
     *
     * @return int
     */
    public function handle()
    {
        // Email disattivata dai Settings: niente notifiche.
        if (! CRUDBooster::isEmailEnabled()) {
            return 0;
        }

        $users = $this->expiringUsers();

        $users_tenants = [];

        foreach ($users as $user) {
            $user->data_scadenza = date('d/m/Y', strtotime($user->data_scadenza));
            $users_tenants[$user->tenant][] = $user;

            $this->queueEmail($user->email, $user, 'notifica_scadenza_utente_user');
        }

        foreach ($users_tenants as $tenant => $tenant_users) {
            // Utenti senza tenant: nessun tenant admin a cui scrivere.
            if (empty($tenant)) {
                continue;
            }

            $visible_users = array_values(array_filter($tenant_users, function ($user) {
                return ! in_array((int) $user->id_cms_privileges, self::PRIVILEGES_HIDDEN_FROM_TENANT_ADMINS, true);
            }));

            $replace = [self::USERS_PLACEHOLDER => TenantHelper::build_utenti_scadenza_tenantadmin($visible_users)];

            foreach (TenantHelper::getTenantAdmins($tenant) as $admin) {
                $this->queueEmail($admin->email, $admin, 'notifica_scadenza_utente_tenantadmin', $replace);
            }
        }

        // I superadmin ricevono l'elenco di tutti gli utenti in scadenza.
        if ($users->isNotEmpty()) {
            $replace = [self::USERS_PLACEHOLDER => TenantHelper::build_utenti_scadenza_tenantadmin($users->all())];

            $superadmins = DB::table('cms_users')
                ->where('id_cms_privileges', self::SUPERADMIN_PRIVILEGE)
                ->where('status', 'Active')
                ->get();

            foreach ($superadmins as $superadmin) {
                $this->queueEmail($superadmin->email, $superadmin, 'notifica_scadenza_utente_tenantadmin', $replace);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Utenti attivi la cui scadenza cade tra DAYS_BEFORE_EXPIRY giorni.
     */
    private function expiringUsers()
    {
        $dates = array_map(function ($days) {
            return now()->addDays($days)->toDateString();
        }, self::DAYS_BEFORE_EXPIRY);

        return DB::table('cms_users')
            ->where('status', 'Active')
            ->whereIn('data_scadenza', $dates)
            ->select(
                'cms_users.*',
                DB::raw('DATEDIFF(data_scadenza, NOW()) as giorni_mancanti')
            )
            ->get();
    }

    /**
     * Accoda una email da template, una sola volta al giorno per coppia
     * destinatario/template. Nel payload del job (tabella jobs) non finiscono
     * credenziali: password, token e segreti MFA vengono scartati.
     */
    private function queueEmail($to, $data, string $template, array $contentReplace = []): void
    {
        $key = 'user_expiry_notified:'.md5($template.'|'.$to.'|'.now()->toDateString());

        if (! $this->option('force') && Cache::has($key)) {
            return;
        }

        $data = array_filter((array) $data, function ($field) {
            return ! preg_match('/password|token|secret|mfa|otp/i', (string) $field);
        }, ARRAY_FILTER_USE_KEY);

        SendTemplateEmailJob::enqueue([
            'to' => $to,
            'data' => $data,
            'template' => $template,
            'content_replace' => $contentReplace,
        ]);

        // Dopo l'accodamento: se enqueue() lancia (connessione sync), un nuovo
        // giro puo' riprovare.
        Cache::put($key, true, now()->addDays(2));
    }
}
