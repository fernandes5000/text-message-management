<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question');
            $table->json('options');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('poll_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('option_index');
            $table->timestamps();

            $table->unique(['poll_id', 'subscriber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('poll_responses');
        Schema::dropIfExists('polls');
    }
};
