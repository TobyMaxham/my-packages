<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ecosystem');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('repository_url')->nullable();
            $table->boolean('abandoned')->default(false);
            $table->string('latest_version')->nullable();
            $table->string('php_constraint')->nullable();
            $table->string('framework_constraint')->nullable();
            $table->timestamp('last_release_at')->nullable();
            $table->string('status')->default('unclear');
            $table->unsignedBigInteger('downloads_total')->default(0);
            $table->unsignedInteger('favers')->default(0);
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['ecosystem', 'name']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
