<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sender_account_id')
            ->constrained('accounts')
            ->onDelete('cascade');

            $table->foreignId('receiver_account_id')
            ->nullable()
            ->constrained('accounts')
            ->onDelete('set null');

            $table->string('external_account_number')
            ->nullable();

            $table->decimal('amount', 15, 2);

            $table->string('currency', 3);

            $table->text('description')->nullable();

            $table->string('category')->nullable();

            $table->enum('transaction_type', ['internal', 'external']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
