<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Scrippy\Enums\ExecutionTypeEnum;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('scrippy_executions', 'execution_type')) {
            Schema::table('scrippy_executions', function (Blueprint $table) {
                $table->string('execution_type')->default(ExecutionTypeEnum::SYNC->value)->after('scrippy_class');
            });
        }
    }
};
