<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sickbay_items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('category'); 
            $table->integer('stock_quantity')->default(0);
            $table->integer('alert_threshold')->default(10); 
            $table->date('expiry_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('sickbay_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade'); // Patient
            $table->foreignUuid('attended_by')->nullable()->constrained('users')->onDelete('set null'); // Nurse/Doctor
            
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->string('symptoms');
            $table->enum('visit_type', ['walk_in', 'appointment', 'emergency'])->default('walk_in');
            $table->enum('status', ['waiting', 'under_observation', 'treated', 'referred', 'discharged'])->default('waiting');
            
            // Bed tracking
            $table->string('bed_number')->nullable(); 
            $table->dateTime('admitted_to_bed_at')->nullable();
            
            $table->timestamps();
        });

        Schema::create('sickbay_medical_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sickbay_visit_id')->constrained('sickbay_visits')->onDelete('cascade');
            
            // Vitals
            $table->string('blood_pressure')->nullable();
            $table->decimal('temperature', 4, 1)->nullable(); 
            $table->decimal('weight', 5, 2)->nullable();
            
            // Treatments
            $table->text('findings'); 
            $table->text('treatment_given'); 
            $table->text('medicines_dispensed')->nullable(); // JSON list
            
            // Emergency logging
            $table->boolean('parent_contacted')->default(false);
            $table->dateTime('parent_contacted_at')->nullable();
            $table->text('parent_contact_notes')->nullable();

            // Referrals
            $table->string('referral_hospital')->nullable(); 
            $table->text('referral_notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sickbay_medical_logs');
        Schema::dropIfExists('sickbay_visits');
        Schema::dropIfExists('sickbay_items');
    }
};
