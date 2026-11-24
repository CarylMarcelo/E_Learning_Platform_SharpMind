<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

test('default installation seeds all required catalogues without creating an account or overwriting edits', function (): void {
    $category = MembershipCategory::factory()->create(['name' => 'Student', 'discount_percent' => 15]);
    $level = MembershipLevel::factory()->create(['name' => 'Basic', 'fee' => 750]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(MembershipCategory::orderBy('name')->pluck('name')->all())->toBe(['Corporate', 'Regular', 'Senior Citizen', 'Student']);
    expect(MembershipLevel::orderBy('name')->pluck('name')->all())->toBe(['Basic', 'Gold', 'Platinum', 'Silver']);
    expect((float) $category->fresh()->discount_percent)->toBe(15.0);
    expect((float) $level->fresh()->fee)->toBe(750.0);
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('members', 0);
});

test('the demonstration seeder requires an explicit opt in', function (): void {
    config(['demo.enabled' => false]);

    expect(fn () => $this->seed(DemonstrationSeeder::class))->toThrow(LogicException::class, 'DEMO_MODE=true');

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('members', 0);
});

test('demonstration data includes every transaction status is repeatable and sends no email', function (): void {
    $this->freezeTime();
    config(['demo.enabled' => true]);
    Notification::fake();

    $this->seed(DemonstrationSeeder::class);
    $this->seed(DemonstrationSeeder::class);

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('members', 5);
    $this->assertDatabaseCount('membership_transactions', 7);
    $this->assertDatabaseCount('payments', 4);
    expect(DB::table('membership_transactions')->distinct()->pluck('status')->sort()->values()->all())->toBe(['Approved', 'Cancelled', 'Expired', 'Paid', 'Pending']);
    expect(Member::where('email', 'not like', '%@example.test')->count())->toBe(0);
    Notification::assertNothingSent();
    $this->post(route('login.post'), ['email' => 'admin@sharpmind.test', 'password' => 'SharpMindDemo2026!'])->assertRedirectToRoute('dashboard');
    $this->assertAuthenticatedAs(User::where('email', 'admin@sharpmind.test')->firstOrFail());
});

test('administrator setup creates a sign in account with a hashed password', function (): void {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Administrator name', 'Workspace Admin')
        ->expectsQuestion('Administrator email', 'ADMIN@example.test')
        ->expectsQuestion('Password (at least 12 characters)', 'CorrectHorseBattery2026!')
        ->expectsQuestion('Confirm password', 'CorrectHorseBattery2026!')
        ->expectsOutput('Administrator created. Sign in using the email and password you entered.')
        ->assertExitCode(0);

    $user = User::where('email', 'admin@example.test')->firstOrFail();
    expect(Hash::check('CorrectHorseBattery2026!', $user->password))->toBeTrue();
    $this->post(route('login.post'), ['email' => 'admin@example.test', 'password' => 'CorrectHorseBattery2026!'])->assertRedirectToRoute('dashboard');
    $this->assertAuthenticatedAs($user);
});

test('administrator setup rejects duplicate accounts without resetting their password', function (): void {
    $existing = User::factory()->create(['email' => 'admin@example.test']);
    $originalHash = $existing->password;

    $this->artisan('app:create-admin')
        ->expectsQuestion('Administrator name', 'Another Admin')
        ->expectsQuestion('Administrator email', 'admin@example.test')
        ->expectsQuestion('Password (at least 12 characters)', 'CorrectHorseBattery2026!')
        ->expectsQuestion('Confirm password', 'CorrectHorseBattery2026!')
        ->expectsOutput('The email has already been taken.')
        ->assertExitCode(2);

    $this->assertDatabaseCount('users', 1);
    expect($existing->fresh()->password)->toBe($originalHash);
});

test('every registered controller route has an implemented action', function (): void {
    $missing = [];

    foreach (Route::getRoutes() as $route) {
        $action = $route->getActionName();

        if (! str_starts_with($action, 'App\\Http\\Controllers\\') || ! str_contains($action, '@')) {
            continue;
        }

        [$controller, $method] = explode('@', $action, 2);

        if (! method_exists($controller, $method)) {
            $missing[] = $route->uri().' -> '.$action;
        }
    }

    expect($missing)->toBe([]);
});

test('all migrations can be installed rolled back and installed again on an isolated database', function (): void {
    $connection = config('database.default') === 'mysql'
        ? [...config('database.connections.mysql'), 'prefix' => 'rb_', 'prefix_indexes' => true]
        : ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
    config(['database.connections.migration_check' => $connection]);

    $this->artisan('migrate', ['--database' => 'migration_check', '--force' => true])->assertExitCode(0);
    $this->artisan('migrate:reset', ['--database' => 'migration_check', '--force' => true])->assertExitCode(0);

    expect(Schema::connection('migration_check')->hasTable('members'))->toBeFalse();
    expect(Schema::connection('migration_check')->hasTable('payments'))->toBeFalse();
    $this->artisan('migrate', ['--database' => 'migration_check', '--force' => true])->assertExitCode(0);
    expect(Schema::connection('migration_check')->hasColumns('membership_transactions', ['approved_at', 'expires_at', 'level_name', 'category_name', 'validity_days']))->toBeTrue();
    $this->artisan('migrate:reset', ['--database' => 'migration_check', '--force' => true])->assertExitCode(0);
    Schema::connection('migration_check')->dropIfExists('migrations');
});

test('upgrading legacy purchases preserves amounts and payment references and labels reconstructed details', function (): void {
    $connection = config('database.default') === 'mysql'
        ? [...config('database.connections.mysql'), 'prefix' => 'rb_', 'prefix_indexes' => true]
        : ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true];
    config(['database.connections.migration_check' => $connection]);
    $paths = array_values(array_filter(glob(database_path('migrations/*.php')), fn (string $path): bool => ! str_contains($path, '094308_') && ! str_contains($path, '094309_')));
    $this->artisan('migrate', ['--database' => 'migration_check', '--path' => $paths, '--realpath' => true, '--force' => true])->assertExitCode(0);
    $connection = DB::connection('migration_check');
    $categoryId = $connection->table('membership_categories')->insertGetId(['name' => 'Student', 'discount_percent' => 20]);
    $levelId = $connection->table('membership_levels')->insertGetId(['name' => 'Basic', 'fee' => 500, 'validity_days' => 30]);
    $memberId = $connection->table('members')->insertGetId(['membership_number' => 'MEM-LEGACY-01', 'membership_category_id' => $categoryId, 'membership_level_id' => $levelId, 'first_name' => 'Legacy', 'last_name' => 'Member', 'email' => 'legacy@example.test', 'status' => 'Active']);
    $transactionId = $connection->table('membership_transactions')->insertGetId(['reference_number' => 'TRX-LEGACY-01', 'member_id' => $memberId, 'type' => 'Application', 'amount' => 400, 'status' => 'Approved', 'created_at' => '2026-10-25 00:00:00', 'updated_at' => '2026-10-25 00:00:00']);
    $connection->table('payments')->insert(['membership_transaction_id' => $transactionId, 'payment_reference' => 'PAY-LEGACY-01', 'amount_paid' => 400, 'payment_method' => 'Cash']);

    $this->artisan('migrate', ['--database' => 'migration_check', '--force' => true])->assertExitCode(0);

    $transaction = $connection->table('membership_transactions')->where('id', $transactionId)->first();
    expect((float) $transaction->amount)->toBe(400.0);
    expect((int) $transaction->is_legacy)->toBe(1);
    expect($transaction->level_name)->toBe('Basic');
    expect($transaction->expires_at)->toBe('2026-11-24 00:00:00');
    expect($connection->table('payments')->where('membership_transaction_id', $transactionId)->value('payment_reference'))->toBe('PAY-LEGACY-01');
    expect($connection->table('members')->where('id', $memberId)->value('status'))->toBe('Active');
    $this->artisan('migrate:reset', ['--database' => 'migration_check', '--force' => true])->assertExitCode(0);
    Schema::connection('migration_check')->dropIfExists('migrations');
});

test('administrator setup rejects short or unconfirmed passwords', function (string $password, string $confirmation): void {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Administrator name', 'Workspace Admin')
        ->expectsQuestion('Administrator email', 'new.admin@example.test')
        ->expectsQuestion('Password (at least 12 characters)', $password)
        ->expectsQuestion('Confirm password', $confirmation)
        ->assertExitCode(2);

    $this->assertDatabaseCount('users', 0);
})->with(['short password' => ['short', 'short'], 'confirmation mismatch' => ['CorrectHorseBattery2026!', 'DifferentPassword2026!']]);

test('demonstration data is refused in production even when explicitly enabled', function (): void {
    config(['demo.enabled' => true]);
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => (new DemonstrationSeeder)->run())->toThrow(LogicException::class, 'DEMO_MODE=true');

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('members', 0);
});
