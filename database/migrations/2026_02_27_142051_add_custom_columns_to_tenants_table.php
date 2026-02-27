<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('school_name')->after('id')->nullable();
            $table->boolean('is_active')->after('school_name')->default(true);
            $table->string('email')->after('is_active')->nullable();
            $table->string('address', 500)->after('email')->nullable();
            $table->string('logo_path')->after('address')->nullable();
            $table->string('admin_name')->after('logo_path')->nullable();
            $table->string('admin_email')->after('admin_name')->nullable();
            $table->string('admin_password_hash')->after('admin_email')->nullable();
        });

        // Migrate existing data from JSON blob to columns
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            $data = json_decode($tenant->data, true);
            if ($data) {
                DB::table('tenants')->where('id', $tenant->id)->update([
                    'school_name' => $data['school_name'] ?? null,
                    'is_active' => $data['is_active'] ?? true,
                    'email' => $data['email'] ?? null,
                    'address' => $data['address'] ?? null,
                    'logo_path' => $data['logo_path'] ?? null,
                    'admin_name' => $data['admin_name'] ?? null,
                    'admin_email' => $data['admin_email'] ?? null,
                    'admin_password_hash' => $data['admin_password_hash'] ?? null,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'school_name',
                'is_active',
                'email',
                'address',
                'logo_path',
                'admin_name',
                'admin_email',
                'admin_password_hash'
            ]);
        });
    }
};
