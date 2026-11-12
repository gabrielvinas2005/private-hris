<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertToPrivate201File extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add Private Sector & Philippine Labor Code columns to 'employees'
        Schema::table('employees', function (Blueprint $table) {
            // Labor Code Art. 295-296: Tenure & Milestones
            if (!Schema::hasColumn('employees', 'probationary_end_date')) {
                $table->date('probationary_end_date')->nullable()->after('date_hired');
            }
            if (!Schema::hasColumn('employees', 'date_regularized')) {
                $table->date('date_regularized')->nullable()->after('probationary_end_date');
            }
            if (!Schema::hasColumn('employees', 'contract_start_date')) {
                $table->date('contract_start_date')->nullable()->after('date_regularized');
            }
            if (!Schema::hasColumn('employees', 'contract_end_date')) {
                $table->date('contract_end_date')->nullable()->after('contract_start_date');
            }
            if (!Schema::hasColumn('employees', 'date_separated')) {
                $table->date('date_separated')->nullable();
                $table->string('separation_reason', 100)->nullable();
            }

            // Labor Code Book III: Wage Classification & Art. 82 Rank Level
            if (!Schema::hasColumn('employees', 'rate_type')) {
                $table->string('rate_type', 20)->default('monthly'); // 'monthly' or 'daily'
            }
            if (!Schema::hasColumn('employees', 'daily_rate')) {
                $table->decimal('daily_rate', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'hourly_rate')) {
                $table->decimal('hourly_rate', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('employees', 'is_mwe')) {
                $table->boolean('is_mwe')->default(false); // Minimum Wage Earner (Tax Exempt)
            }
            if (!Schema::hasColumn('employees', 'rank_level')) {
                $table->string('rank_level', 50)->default('Rank and File'); // 'Rank and File', 'Supervisory', 'Managerial'
            }

            // Emergency Contacts
            if (!Schema::hasColumn('employees', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable();
                $table->string('emergency_contact_relationship')->nullable();
                $table->string('emergency_contact_no')->nullable();
                $table->text('emergency_contact_address')->nullable();
            }

            // Health Insurance (HMO)
            if (!Schema::hasColumn('employees', 'hmo_provider')) {
                $table->string('hmo_provider')->nullable();
                $table->string('hmo_card_no')->nullable();
            }
        });

        // 2. Mirror columns to 'employees_temps' for self-service 201 change requests
        if (Schema::hasTable('employees_temps')) {
            Schema::table('employees_temps', function (Blueprint $table) {
                if (!Schema::hasColumn('employees_temps', 'emergency_contact_name')) {
                    $table->string('emergency_contact_name')->nullable();
                    $table->string('emergency_contact_relationship')->nullable();
                    $table->string('emergency_contact_no')->nullable();
                    $table->text('emergency_contact_address')->nullable();
                }
                if (!Schema::hasColumn('employees_temps', 'hmo_provider')) {
                    $table->string('hmo_provider')->nullable();
                    $table->string('hmo_card_no')->nullable();
                }
            });
        }

        // 3. Seed Private Sector Employment Types (Labor Code Art. 295-296)
        if (Schema::hasTable('employment_types')) {
            $privateTypes = [
                ['name' => 'Regular', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Probationary', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Project-Based', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Seasonal', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Casual', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Fixed-Term', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
                ['name' => 'Intern / OJT', 'active' => true, 'created_at' => now(), 'updated_at' => now()]
            ];

            foreach ($privateTypes as $type) {
                $exists = DB::table('employment_types')->where('name', $type['name'])->exists();
                if (!$exists) {
                    DB::table('employment_types')->insert($type);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'probationary_end_date',
                'date_regularized',
                'contract_start_date',
                'contract_end_date',
                'date_separated',
                'separation_reason',
                'rate_type',
                'daily_rate',
                'hourly_rate',
                'is_mwe',
                'rank_level',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_no',
                'emergency_contact_address',
                'hmo_provider',
                'hmo_card_no',
            ]);
        });
    }
}