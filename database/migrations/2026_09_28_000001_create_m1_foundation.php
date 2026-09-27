<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function business(Blueprint $t): void
    {
        $t->foreignId('business_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
    }

    private function check(string $table, string $name, string $expression): void
    {
        DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
    }

    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 150);
            $t->boolean('is_active')->default(true);
            $t->date('active_until')->nullable();
            $t->boolean('is_demo')->default(false);
            $t->dateTime('demo_expires_at')->nullable();
            $t->boolean('wa_enabled')->default(false);
            $t->enum('wa_provider', ['fonnte', 'wablas', 'waba'])->nullable();
            $t->text('wa_config')->nullable();
            $t->text('wa_token')->nullable();
            $t->string('wa_sender_number', 20)->nullable();
            $t->string('email_sender_name', 100)->nullable();
            $t->string('email_sender_address', 150)->nullable();
            $t->text('smtp_config')->nullable();
            $t->timestamps();
        });
        $this->check('businesses', 'business_lifecycle', '(is_demo = 0 AND active_until IS NOT NULL AND demo_expires_at IS NULL) OR (is_demo = 1 AND demo_expires_at IS NOT NULL)');
        $this->check('businesses', 'business_booleans', 'is_active IN (0,1) AND is_demo IN (0,1) AND wa_enabled IN (0,1)');
        Schema::create('branches', function (Blueprint $t) {
            $t->id();
            $this->business($t);
            $t->string('nama', 100);
            $t->text('alamat');
            $t->string('telepon', 20);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['id', 'business_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('business_id')->nullable()->constrained()->restrictOnDelete()->restrictOnUpdate();
            $t->unsignedBigInteger('branch_id')->nullable()->index();
            $t->enum('role', ['developer', 'owner', 'admin']);
            $t->boolean('is_active')->default(true);
            $t->boolean('must_change_password')->default(false);
            $t->unsignedBigInteger('owner_business_id')->storedAs("CASE WHEN role = 'owner' THEN business_id ELSE NULL END")->unique();
            $t->unique(['id', 'business_id']);
            $t->foreign(['branch_id', 'business_id'])->references(['id', 'business_id'])->on('branches')->restrictOnDelete()->restrictOnUpdate();
        });
        $this->check('users', 'user_role_assignment', "(role = 'developer' AND business_id IS NULL AND branch_id IS NULL) OR (role = 'owner' AND business_id IS NOT NULL AND branch_id IS NULL) OR (role = 'admin' AND business_id IS NOT NULL AND branch_id IS NOT NULL)");
        $this->check('users', 'user_booleans', 'is_active IN (0,1) AND must_change_password IN (0,1)');
        $this->check('branches', 'branch_boolean', 'is_active IN (0,1)');
        Schema::create('business_settings', function (Blueprint $t) {
            $t->id();
            $this->business($t);
            $t->unique('business_id');
            $t->boolean('dp_enabled')->default(false);
            $t->boolean('reminder_enabled')->default(true);
            $t->unsignedTinyInteger('reminder_first_days')->default(2);
            $t->unsignedTinyInteger('reminder_interval_days')->default(2);
            $t->unsignedTinyInteger('reminder_max_count')->default(3);
            $t->boolean('wa_on_ready')->default(true);
            $t->boolean('wa_on_reminder')->default(false);
            $t->unsignedInteger('wa_monthly_limit')->nullable();
            $t->timestamps();
        });
        $this->check('business_settings', 'reminder_positive', 'reminder_first_days >= 1 AND reminder_interval_days >= 1 AND reminder_max_count >= 1');
        $this->check('business_settings', 'setting_booleans', 'dp_enabled IN (0,1) AND reminder_enabled IN (0,1) AND wa_on_ready IN (0,1) AND wa_on_reminder IN (0,1)');
        foreach (['master_services', 'services'] as $table) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->id();
                $this->business($t);
                if ($table === 'services') {
                    $t->unsignedBigInteger('branch_id');
                    $t->foreign(['branch_id', 'business_id'])->references(['id', 'business_id'])->on('branches')->restrictOnDelete()->restrictOnUpdate();
                }
                $t->string('nama', 100);
                $t->enum('satuan', ['kg', 'item']);
                $t->unsignedInteger('harga');
                $t->unsignedSmallInteger('durasi_jam');
                $t->decimal('berat_minimum', 5, 1)->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->unique(['id', 'business_id']);
                $t->unique([$table === 'services' ? 'branch_id' : 'business_id', 'nama']);
            });
            $this->check($table, $table.'_values', "harga > 0 AND durasi_jam > 0 AND is_active IN (0,1) AND (berat_minimum IS NULL OR (satuan = 'kg' AND berat_minimum > 0))");
        }
        Schema::create('loyalty_settings', function (Blueprint $t) {
            $t->id();
            $this->business($t);
            $t->unique('business_id');
            $t->boolean('is_active')->default(false);
            $t->unsignedTinyInteger('stempel_dibutuhkan')->default(10);
            $t->unsignedBigInteger('master_service_id')->nullable();
            $t->decimal('berat_maks_gratis', 5, 1)->nullable();
            $t->timestamps();
            $t->foreign(['master_service_id', 'business_id'])->references(['id', 'business_id'])->on('master_services')->restrictOnDelete()->restrictOnUpdate();
        });
        $this->check('loyalty_settings', 'loyalty_values', 'is_active IN (0,1) AND stempel_dibutuhkan >= 1 AND (is_active = 0 OR (master_service_id IS NOT NULL AND berat_maks_gratis IS NOT NULL AND berat_maks_gratis > 0))');
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->nullable()->constrained()->restrictOnDelete()->restrictOnUpdate();
            $t->foreignId('user_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $t->string('aksi', 100);
            $t->json('detail');
            $t->dateTime('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('loyalty_settings');
        Schema::dropIfExists('services');
        Schema::dropIfExists('master_services');
        Schema::dropIfExists('business_settings');
        DB::statement('ALTER TABLE users DROP CHECK user_role_assignment, DROP CHECK user_booleans');
        Schema::table('users', function (Blueprint $t) {
            $t->dropForeign(['branch_id', 'business_id']);
            $t->dropForeign(['business_id']);
            $t->dropUnique(['id', 'business_id']);
            $t->dropColumn(['owner_business_id', 'business_id', 'branch_id', 'role', 'is_active', 'must_change_password']);
        });
        Schema::dropIfExists('branches');
        Schema::dropIfExists('businesses');
    }
};
