<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('number', 20)->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->json('aliases')->nullable();
            $table->json('workflow')->nullable();
            $table->unsignedInteger('uses_count')->default(0);
            $table->unsignedInteger('opt_ins_count')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'name']);
        });

        Schema::create('keyword_lists', function (Blueprint $table): void {
            $table->foreignId('keyword_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->constrained()->cascadeOnDelete();
            $table->primary(['keyword_id', 'list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keyword_lists');
        Schema::dropIfExists('keywords');
    }
};
