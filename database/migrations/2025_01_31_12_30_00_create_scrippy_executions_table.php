<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scrippy_executions', function (Blueprint $table) {
            $table->id();
            $table->string('scrippy_name')->unique();
            $table->string('scrippy_class');
            $table->integer('run_count')->default(0);
            $table->integer('max_runs')->default(1);
            $table->timestamp('last_run_at')->nullable();
            $table->string('scrippy_status')->default('pending');
            $table->text('failure_message')->nullable();
            $table->timestamps();
        });
    }
};
