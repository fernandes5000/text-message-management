<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('body');
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft');
            $table->enum('send_type', ['now', 'scheduled', 'recurring'])->default('now');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('recurrence', 50)->nullable(); // daily, weekly, monthly
            $table->string('from_number', 20)->nullable();
            $table->boolean('use_header')->default(false);
            $table->string('header', 100)->nullable();
            $table->string('media_url')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('credits_used')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_lists', function (Blueprint $table): void {
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('list_id')->constrained()->cascadeOnDelete();
            $table->primary(['message_id', 'list_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_lists');
        Schema::dropIfExists('messages');
    }
};
