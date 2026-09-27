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

    private function tenantForeign(Blueprint $t, string $column, string $parent): void
    {
        $t->unsignedBigInteger($column);
        $t->foreign([$column, 'business_id'])->references(['id', 'business_id'])->on($parent)->restrictOnDelete()->restrictOnUpdate();
    }

    private function check(string $table, string $name, string $expression): void
    {
        DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
    }

    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $this->business($t);
            $t->string('nama', 100);
            $t->string('no_hp', 20);
            $t->string('email', 150)->nullable();
            $t->integer('stamp_count')->default(0);
            $t->timestamps();
            $t->unique(['id', 'business_id']);
            $t->unique(['business_id', 'no_hp']);
            $t->index(['business_id', 'nama']);
        });
        Schema::create('promos', function (Blueprint $t) {
            $t->id();
            $this->business($t);
            $t->string('nama', 100);
            $t->enum('tipe', ['persen', 'nominal']);
            $t->unsignedInteger('nilai');
            $t->unsignedInteger('minimal_total')->nullable();
            $t->date('mulai');
            $t->date('selesai');
            $t->boolean('semua_cabang')->default(true);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['id', 'business_id']);
        });
        $this->check('promos', 'promo_values', "nilai > 0 AND (tipe <> 'persen' OR nilai <= 100) AND mulai <= selesai AND semua_cabang IN (0,1) AND is_active IN (0,1)");
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $this->business($t);
            $t->char('kode_resi', 6)->unique();
            $this->tenantForeign($t, 'branch_id', 'branches');
            $this->tenantForeign($t, 'customer_id', 'customers');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $t->string('notification_email', 150)->nullable();
            $t->string('pending_notification_email', 150)->nullable();
            $t->unsignedInteger('email_verification_version')->default(0);
            $t->dateTime('email_verification_expires_at')->nullable();
            $t->unsignedBigInteger('version')->default(1);
            $t->char('create_request_key', 36)->charset('ascii')->collation('ascii_bin');
            $t->char('create_request_hash', 64)->charset('ascii')->collation('ascii_bin');
            $t->decimal('stamp_reward_max_kg_snapshot', 5, 1)->nullable();
            $t->enum('status', ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL', 'SUDAH_DIAMBIL', 'DIBATALKAN'])->default('DITERIMA');
            $t->dateTime('waktu_masuk');
            $t->dateTime('estimasi_selesai');
            $t->dateTime('waktu_siap_diambil')->nullable();
            $t->dateTime('waktu_diambil')->nullable();
            $t->unsignedInteger('subtotal');
            $t->unsignedInteger('potongan_stempel')->default(0);
            $t->foreignId('promo_id')->nullable()->constrained()->nullOnDelete()->restrictOnUpdate();
            $t->string('promo_nama_snapshot', 100)->nullable();
            $t->enum('promo_tipe_snapshot', ['persen', 'nominal'])->nullable();
            $t->unsignedInteger('promo_nilai_snapshot')->nullable();
            $t->unsignedInteger('potongan_promo')->default(0);
            $t->unsignedInteger('total_akhir');
            $t->enum('status_bayar', ['BELUM_BAYAR', 'DP', 'LUNAS']);
            $t->text('catatan_kondisi')->nullable();
            $t->text('alasan_pembatalan')->nullable();
            $t->unsignedTinyInteger('reminder_count')->default(0);
            $t->dateTime('last_reminder_at')->nullable();
            $t->timestamps();
            $t->unique(['id', 'business_id']);
            $t->unique(['business_id', 'create_request_key']);
            $t->index(['business_id', 'branch_id', 'status']);
            $t->index(['business_id', 'status', 'waktu_siap_diambil'], 'tx_ready');
            $t->index(['business_id', 'customer_id']);
            $t->index(['business_id', 'branch_id', 'waktu_masuk']);
            $t->index(['business_id', 'waktu_masuk']);
            $t->index(['business_id', 'branch_id', 'status', 'estimasi_selesai'], 'tx_late');
        });
        $this->check('transactions', 'tx_totals', 'subtotal >= potongan_stempel AND subtotal - potongan_stempel >= potongan_promo AND total_akhir = subtotal - potongan_stempel - potongan_promo AND (total_akhir > 0 OR status_bayar = \'LUNAS\')');
        $this->check('transactions', 'tx_time_version', 'version >= 1 AND estimasi_selesai >= waktu_masuk AND ((reminder_count = 0 AND last_reminder_at IS NULL) OR (reminder_count > 0 AND last_reminder_at IS NOT NULL))');
        $this->check('transactions', 'tx_cancel_reason', "(status = 'DIBATALKAN' AND alasan_pembatalan IS NOT NULL AND CHAR_LENGTH(TRIM(alasan_pembatalan)) > 0) OR (status <> 'DIBATALKAN' AND alasan_pembatalan IS NULL)");
        $this->check('transactions', 'tx_state_times', "(status IN ('DITERIMA','DIPROSES') AND waktu_siap_diambil IS NULL AND waktu_diambil IS NULL) OR (status = 'SIAP_DIAMBIL' AND waktu_siap_diambil IS NOT NULL AND waktu_diambil IS NULL) OR (status = 'SUDAH_DIAMBIL' AND waktu_siap_diambil IS NOT NULL AND waktu_diambil IS NOT NULL AND status_bayar = 'LUNAS') OR (status = 'DIBATALKAN' AND waktu_diambil IS NULL)");
        $this->check('transactions', 'tx_promo_snapshot', '(promo_nama_snapshot IS NULL AND promo_tipe_snapshot IS NULL AND promo_nilai_snapshot IS NULL AND potongan_promo = 0) OR (promo_nama_snapshot IS NOT NULL AND promo_tipe_snapshot IS NOT NULL AND promo_nilai_snapshot IS NOT NULL)');
        $this->check('transactions', 'tx_reward_snapshot', '(stamp_reward_max_kg_snapshot IS NULL AND potongan_stempel = 0) OR (stamp_reward_max_kg_snapshot IS NOT NULL AND stamp_reward_max_kg_snapshot > 0)');
        Schema::create('transaction_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('transaction_id')->constrained()->restrictOnDelete()->restrictOnUpdate();
            $t->foreignId('service_id')->nullable()->constrained()->nullOnDelete()->restrictOnUpdate();
            $t->string('nama_layanan_snapshot', 100);
            $t->enum('satuan_snapshot', ['kg', 'item']);
            $t->unsignedInteger('harga_snapshot');
            $t->decimal('berat_minimum_snapshot', 5, 1)->nullable();
            $t->unsignedSmallInteger('durasi_jam_snapshot');
            $t->decimal('berat_kg', 5, 1)->nullable();
            $t->unsignedSmallInteger('jumlah_unit')->nullable();
            $t->unsignedSmallInteger('perkiraan_jumlah_baju')->nullable();
            $t->boolean('is_stamp_reward')->default(false);
            $t->unsignedInteger('subtotal');
            $t->timestamps();
        });
        $this->check('transaction_items', 'item_quantity', "(satuan_snapshot = 'kg' AND berat_kg IS NOT NULL AND berat_kg > 0 AND jumlah_unit IS NULL) OR (satuan_snapshot = 'item' AND jumlah_unit IS NOT NULL AND jumlah_unit > 0 AND berat_kg IS NULL AND berat_minimum_snapshot IS NULL)");
        $this->check('transaction_items', 'item_values', "harga_snapshot > 0 AND durasi_jam_snapshot > 0 AND (berat_minimum_snapshot IS NULL OR berat_minimum_snapshot > 0) AND is_stamp_reward IN (0,1) AND (is_stamp_reward = 0 OR satuan_snapshot = 'kg')");
        Schema::create('notification_logs', function (Blueprint $t) {
            $t->id();
            $this->business($t);
            $this->tenantForeign($t, 'transaction_id', 'transactions');
            $t->enum('kanal', ['email', 'whatsapp', 'whatsapp_manual']);
            $t->enum('tipe', ['siap_diambil', 'pengingat', 'resi', 'verifikasi_email']);
            $t->foreignId('requested_by')->nullable()->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $t->boolean('is_manual')->default(false);
            $t->unsignedTinyInteger('reminder_number')->nullable();
            $t->unsignedInteger('verification_version')->nullable();
            $t->string('notification_key', 150)->charset('ascii')->collation('ascii_bin')->unique();
            $t->char('request_hash', 64)->charset('ascii')->collation('ascii_bin')->nullable();
            $t->string('tujuan', 150);
            $t->enum('status', ['tertunda', 'diproses', 'berhasil', 'gagal', 'dilewati_batas', 'dilewati_kondisi', 'ditekan_demo', 'dibuka_manual', 'perlu_pemeriksaan']);
            $t->string('reason_code', 64)->nullable();
            $t->unsignedTinyInteger('attempt_count')->default(0);
            $t->char('processing_token', 36)->charset('ascii')->collation('ascii_bin')->nullable();
            foreach (['processing_started_at', 'delivery_started_at', 'next_attempt_at', 'last_enqueued_at', 'sent_at'] as $column) {
                $t->dateTime($column)->nullable();
            }
            $t->json('payload_snapshot')->nullable();
            $t->enum('provider_name_snapshot', ['smtp', 'fonnte', 'wablas', 'waba'])->nullable();
            $t->date('wa_quota_month')->nullable();
            $t->dateTime('created_at');
            $t->dateTime('updated_at');
            $t->index(['business_id', 'kanal', 'wa_quota_month', 'status'], 'notification_quota');
            $t->index(['status', 'next_attempt_at', 'last_enqueued_at'], 'notification_recovery');
            $t->index(['status', 'processing_started_at']);
            $t->index(['business_id', 'transaction_id']);
            $t->index(['business_id', 'requested_by', 'created_at'], 'notification_manual');
        });
        $this->check('notification_logs', 'notification_attempt', 'attempt_count <= 3 AND (delivery_started_at IS NULL OR attempt_count > 0) AND is_manual IN (0,1)');
        $this->check('notification_logs', 'notification_verification', "(tipe = 'verifikasi_email' AND verification_version IS NOT NULL AND kanal = 'email' AND is_manual = 0) OR (tipe <> 'verifikasi_email' AND verification_version IS NULL)");
        $this->check('notification_logs', 'notification_number', "(is_manual = 0 AND tipe = 'siap_diambil' AND reminder_number IS NOT NULL AND reminder_number = 0) OR (is_manual = 0 AND tipe = 'pengingat' AND reminder_number IS NOT NULL AND reminder_number >= 1) OR ((is_manual = 1 OR tipe NOT IN ('siap_diambil','pengingat')) AND reminder_number IS NULL)");
        $this->check('notification_logs', 'notification_manual', '(is_manual = 1 AND requested_by IS NOT NULL AND request_hash IS NOT NULL) OR (is_manual = 0 AND requested_by IS NULL AND request_hash IS NULL)');
        $this->check('notification_logs', 'notification_manual_channel', "kanal <> 'whatsapp_manual' OR (is_manual = 1 AND tipe IN ('resi','pengingat') AND status IN ('dibuka_manual','ditekan_demo'))");
        $this->check('notification_logs', 'notification_state', "(status <> 'diproses' OR (processing_token IS NOT NULL AND processing_started_at IS NOT NULL)) AND (status <> 'tertunda' OR next_attempt_at IS NOT NULL) AND ((status = 'berhasil' AND sent_at IS NOT NULL) OR (status <> 'berhasil' AND sent_at IS NULL)) AND (kanal = 'whatsapp' OR wa_quota_month IS NULL)");
    }

    public function down(): void
    {
        foreach (['notification_logs', 'transaction_items', 'transactions', 'promos', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
