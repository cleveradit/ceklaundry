<?php

use App\Jobs\SendNotification;
use App\Models\User;
use App\Services\AuthRateLimiter;
use App\Services\BranchService;
use App\Services\BusinessTransaction;
use App\Services\CustomerMergeService;
use App\Services\DemoPurgeService;
use App\Services\DemoRateLimiter;
use App\Services\ManualNotificationService;
use App\Services\MasterSyncService;
use App\Services\NotificationTransport;
use App\Services\PaymentService;
use App\Services\ReminderScheduler;
use App\Services\TransactionEmailVerificationService;
use App\Services\TransactionService;
use App\Services\TransactionStateMachine;
use App\Tenancy\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || config('database.connections.mysql.database') !== 'ceklaundry_test') {
    exit(90);
}
$args = json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR);
$actor = User::findOrFail($args['actor']);
// Signal after loading the intentionally stale actor, before attempting the root lock.
file_put_contents($args['barrier'], 'ready');
try {
    if ($args['operation'] === 'limiter') {
        Carbon::setTestNow($args['now']);
        app(AuthRateLimiter::class)->attempt('login', 'parallel@example.test', '192.0.2.55');
    } elseif ($args['operation'] === 'demo-limiter') {
        Carbon::setTestNow($args['now']);
        app(DemoRateLimiter::class)->check(Request::create('/', 'POST', [], [], [], ['REMOTE_ADDR' => $args['ip']]));
    } elseif ($args['operation'] === 'demo-purge') {
        app(DemoPurgeService::class)->purge($args['business']);
    } elseif ($args['operation'] === 'branch') {
        app(BranchService::class)->save($actor, ['nama' => 'Paralel', 'alamat' => 'Jalan Uji', 'telepon' => '081234567890', 'is_active' => true]);
    } elseif ($args['operation'] === 'deactivate') {
        app(BranchService::class)->save($actor, ['nama' => 'Utama', 'alamat' => 'Jalan Uji', 'telepon' => '081234567890', 'is_active' => false], $args['branch']);
    } elseif ($args['operation'] === 'sync') {
        app(MasterSyncService::class)->apply($actor, $args['branches'], $args['fingerprint']);
    } elseif ($args['operation'] === 'payment') {
        app(PaymentService::class)->store($actor, $args['transaction'], ['request_key' => $args['key'], 'jumlah' => $args['amount'], 'metode' => 'tunai']);
    } elseif ($args['operation'] === 'dp-off') {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, User $fresh) {
            abort_unless($fresh->role === 'owner', 403);
            DB::table('business_settings')->where('business_id', $business->id)->update(['dp_enabled' => false, 'updated_at' => now()]);
        });
    } elseif ($args['operation'] === 'status') {
        app(TransactionStateMachine::class)->move($actor, $args['transaction'], $args['target'], $args['version']);
    } elseif ($args['operation'] === 'reminder') {
        app(ReminderScheduler::class)->run();
    } elseif ($args['operation'] === 'send-notification') {
        config()->set('mail.default', 'array');
        app(TenantContext::class)->run($args['business'], fn () => (new SendNotification($args['business'], $args['log']))->handle(app(NotificationTransport::class)));
    } elseif ($args['operation'] === 'send-notification-capture') {
        $transport = new class($args['capture']) extends NotificationTransport
        {
            public function __construct(private string $path) {}

            public function send(array $envelope): array
            {
                file_put_contents($this->path, $envelope['recipient']);

                return ['outcome' => 'accepted', 'code' => null];
            }
        };
        app(TenantContext::class)->run($args['business'], fn () => (new SendNotification($args['business'], $args['log']))->handle($transport));
    } elseif ($args['operation'] === 'email-confirm') {
        app(TransactionEmailVerificationService::class)->confirm($args['code'], $args['version']);
    } elseif ($args['operation'] === 'manual-email') {
        app(ManualNotificationService::class)->email($actor, $args['transaction'], ['request_key' => $args['key']]);
    } elseif ($args['operation'] === 'create') {
        app(TransactionService::class)->create($actor, $args['data']);
    } elseif ($args['operation'] === 'merge') {
        app(CustomerMergeService::class)->merge($actor, $args['source'], $args['target']);
    } else {
        app(BusinessTransaction::class)->run($actor, $actor->business_id, function ($business, $fresh) use ($args) {
            abort_unless($fresh->branch_id === $args['branch'], 404);
            DB::table('branches')->where('id', $args['branch'])->update(['alamat' => 'ILLEGAL']);
        });
    }
    echo json_encode(['status' => 200]);
} catch (ValidationException $e) {
    echo json_encode(['status' => 422]);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['status' => $e->getStatusCode()]);
} catch (Throwable $e) {
    fwrite(STDERR, get_class($e).': '.$e->getMessage());
    exit(1);
}
