<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();

            $table->string('account_number')->unique();
            $table->enum('type', ['dinarski', 'devizni']);
            $table->char('currency', 3)->default('RSD');
            $table->decimal('balance', 15, 2)->default(0);
            $table->enum('status', ['active', 'frozen', 'closed'])->default('active');
            $table->boolean('is_shared')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('account_number');
        });

        Schema::create('account_user', function (Blueprint $table) {
            $table->foreignUuid('account_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['account_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_user');
        Schema::dropIfExists('accounts');
    }
};