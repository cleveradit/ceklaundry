<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('transaction_id');
            $table->char('request_key', 36)->charset('ascii')->collation('ascii_bin');
            $table->char('request_hash', 64)->charset('ascii')->collation('ascii_bin');
            $table->unsignedInteger('jumlah');
            $table->enum('metode', ['tunai', 'transfer']);
            $table->dateTime('waktu');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->unique(['business_id', 'request_key']);
            $table->index(['business_id', 'transaction_id']);
            $table->index(['business_id', 'waktu']);
            $table->foreign(['transaction_id', 'business_id'])->references(['id', 'business_id'])->on('transactions')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE payments ADD CONSTRAINT payment_positive CHECK (jumlah > 0)');

        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('transaction_id');
            $table->enum('status', ['DITERIMA', 'DIPROSES', 'SIAP_DIAMBIL', 'SUDAH_DIAMBIL', 'DIBATALKAN']);
            $table->foreignId('user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('created_at');
            $table->unique(['transaction_id', 'status']);
            $table->index(['business_id', 'transaction_id']);
            $table->foreign(['transaction_id', 'business_id'])->references(['id', 'business_id'])->on('transactions')->restrictOnDelete();
        });

        Schema::create('loyalty_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('transaction_id');
            $table->enum('jenis', ['perolehan', 'penukaran', 'pengembalian_penukaran', 'pencabutan_perolehan']);
            $table->integer('jumlah');
            $table->dateTime('created_at');
            $table->unique(['transaction_id', 'jenis']);
            $table->index(['business_id', 'customer_id']);
            $table->foreign(['customer_id', 'business_id'])->references(['id', 'business_id'])->on('customers')->restrictOnDelete();
            $table->foreign(['transaction_id', 'business_id'])->references(['id', 'business_id'])->on('transactions')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE loyalty_histories ADD CONSTRAINT loyalty_sign CHECK ((jenis = 'perolehan' AND jumlah = 1) OR (jenis = 'pencabutan_perolehan' AND jumlah = -1) OR (jenis = 'penukaran' AND jumlah < 0) OR (jenis = 'pengembalian_penukaran' AND jumlah > 0))");
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_histories');
        Schema::dropIfExists('status_histories');
        Schema::dropIfExists('payments');
    }
};
