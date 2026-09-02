<?php

namespace Database\Seeders;

use App\Models\AqsatContract;
use App\Models\Expense;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Spatie roles & permissions FIRST (required by assignSyncRole in UserSeeder).
        $this->call(RolesAndPermissionsSeeder::class);

        if (User::count() === 0) {
            $this->call(UserSeeder::class);
        }

        // Realistic operating data: vendors, stock, POs, payment plans,
        // cash-flow ledger. Idempotent — safe on every deploy.
        $this->call(OperationsSeeder::class);

        $patients = [
            [
                'name' => 'شیلان ئەحمەد (Shilan Ahmad)',
                'phone' => '+964 750 123 4501',
                'age' => 29,
                'appointment_date' => '2026-08-24 09:00',
                'is_smoker' => false,
                'medical_notes' => 'No known allergies. نەخۆشییە دروستکراوەکان نییە. Sensitive lower molar; prefers Kurdish communication.',
            ],
            [
                'name' => 'ئارام محەمەد (Aram Mohammed)',
                'phone' => '+964 750 123 4502',
                'age' => 41,
                'appointment_date' => '2026-08-24 09:30',
                'is_smoker' => true,
                'medical_notes' => 'Controlled hypertension. پەستانی خوێنی کۆنترۆڵکراو. Review medication history before extraction.',
            ],
            [
                'name' => 'نیشتمان عەلی (Nishtiman Ali)',
                'phone' => '+964 750 123 4503',
                'age' => 17,
                'appointment_date' => '2026-08-24 10:15',
                'is_smoker' => false,
                'medical_notes' => 'Orthodontic consultation with parent consent. راوێژکاری ڕێکخستنی ددان لەگەڵ ڕەزامەندی دایک و باوک.',
            ],
            [
                'name' => 'روژین قادر (Rojin Qadir)',
                'phone' => '+964 750 123 4504',
                'age' => 35,
                'appointment_date' => '2026-08-24 11:00',
                'is_smoker' => false,
                'medical_notes' => 'Pregnancy not reported. ألم متقطع في الضرس العلوي الأيسر. / Intermittent upper-left molar pain.',
            ],
            [
                'name' => 'کاروان حەمە (Karwan Hama)',
                'phone' => '+964 750 123 4505',
                'age' => 52,
                'appointment_date' => '2026-08-24 11:45',
                'is_smoker' => true,
                'medical_notes' => 'Diabetes reported; ask for latest glucose reading. نەخۆشی شەکر هەیە؛ دوا قەبارەی شەکر بپرسە.',
            ],
            [
                'name' => 'هێڤی سامان (Hêvi Saman)',
                'phone' => '+964 750 123 4506',
                'age' => 24,
                'appointment_date' => '2026-08-25 09:15',
                'is_smoker' => false,
                'medical_notes' => 'No significant medical history. History of sensitivity to cold drinks.',
            ],
            [
                'name' => 'دڵشاد عوسمان (Dilshad Othman)',
                'phone' => '+964 750 123 4507',
                'age' => 63,
                'appointment_date' => '2026-08-25 10:00',
                'is_smoker' => false,
                'medical_notes' => 'Uses removable partial denture. بەکارهێنەری ددانی دەستکردی لابراوە.',
            ],
            [
                'name' => 'باران عبدالله (Baran Abdullah)',
                'phone' => '+964 750 123 4508',
                'age' => 31,
                'appointment_date' => '2026-08-25 10:45',
                'is_smoker' => false,
                'medical_notes' => 'Follow-up after root canal treatment. پشکنینی دوای چارەسەری ڕەگی ددان.',
            ],
        ];

        $savedPatients = [];
        foreach ($patients as $patientData) {
            $savedPatients[$patientData['phone']] = Patient::updateOrCreate(
                ['phone' => $patientData['phone']],
                $patientData,
            );
        }

        $contracts = [
            ['phone' => '+964 750 123 4501', 'treatment_name' => 'Dental crown / تاجی ددان', 'total_amount' => 750000, 'remaining_balance' => 450000, 'status' => 'active'],
            ['phone' => '+964 750 123 4503', 'treatment_name' => 'Orthodontic plan / پلانی ڕێکخستنی ددان', 'total_amount' => 1800000, 'remaining_balance' => 1200000, 'status' => 'active'],
            ['phone' => '+964 750 123 4507', 'treatment_name' => 'Partial denture / ددانی دەستکردی بەشدار', 'total_amount' => 950000, 'remaining_balance' => 0, 'status' => 'completed'],
        ];

        $savedContracts = [];
        foreach ($contracts as $contractData) {
            $patient = $savedPatients[$contractData['phone']];
            $savedContracts[$contractData['phone']] = AqsatContract::updateOrCreate(
                ['patient_id' => $patient->id, 'treatment_name' => $contractData['treatment_name']],
                collect($contractData)->except('phone')->all(),
            );
        }

        $visits = [
            ['phone' => '+964 750 123 4501', 'contract_phone' => '+964 750 123 4501', 'queue_status' => 'pending', 'visit_type' => 'phone', 'treatment_notes' => 'Crown preparation review / پشکنینی ئامادەکردنی تاج', 'total_cost' => 300000, 'amount_paid' => 150000, 'short_term_debt' => 150000, 'created_at' => '2026-08-23 08:30:00'],
            ['phone' => '+964 750 123 4502', 'contract_phone' => null, 'queue_status' => 'active', 'visit_type' => 'walk_in', 'treatment_notes' => 'Scaling and polishing / پاککردنەوە و پۆلیشکردن', 'total_cost' => 85000, 'amount_paid' => 85000, 'short_term_debt' => 0, 'created_at' => '2026-08-22 09:10:00'],
            ['phone' => '+964 750 123 4503', 'contract_phone' => '+964 750 123 4503', 'queue_status' => 'pending', 'visit_type' => 'whatsapp', 'treatment_notes' => 'Orthodontic records and consultation / تۆمارەکانی ڕێکخستن و راوێژ', 'total_cost' => 250000, 'amount_paid' => 100000, 'short_term_debt' => 150000, 'created_at' => '2026-08-23 10:20:00'],
            ['phone' => '+964 750 123 4504', 'contract_phone' => null, 'queue_status' => 'completed', 'visit_type' => 'phone', 'treatment_notes' => 'Composite filling, upper-left molar / پڕکردنەوەی کۆمپۆزیت', 'total_cost' => 120000, 'amount_paid' => 120000, 'short_term_debt' => 0, 'created_at' => '2026-08-21 11:00:00'],
            ['phone' => '+964 750 123 4505', 'contract_phone' => null, 'queue_status' => 'completed', 'visit_type' => 'walk_in', 'treatment_notes' => 'Emergency examination / پشکنینی فۆری', 'total_cost' => 50000, 'amount_paid' => 30000, 'short_term_debt' => 20000, 'created_at' => '2026-08-20 14:15:00'],
            ['phone' => '+964 750 123 4508', 'contract_phone' => null, 'queue_status' => 'completed', 'visit_type' => 'whatsapp', 'treatment_notes' => 'Root canal follow-up / پشکنینی دوای چارەسەری ڕەگ', 'total_cost' => 100000, 'amount_paid' => 100000, 'short_term_debt' => 0, 'created_at' => '2026-08-19 15:30:00'],
        ];

        foreach ($visits as $visitData) {
            $patient = $savedPatients[$visitData['phone']];
            $contract = $visitData['contract_phone'] ? $savedContracts[$visitData['contract_phone']] : null;
            Visit::updateOrCreate(
                ['patient_id' => $patient->id, 'created_at' => $visitData['created_at']],
                collect($visitData)->except(['phone', 'contract_phone'])->merge([
                    'aqsat_contract_id' => $contract?->id,
                ])->all(),
            );
        }

        $expenses = [
            ['amount' => 185000, 'description' => 'Dental materials - Erbil / کەرەستەی ددان - هەولێر', 'created_at' => '2026-08-22 16:00:00'],
            ['amount' => 95000, 'description' => 'Sterilization supplies / کەرەستەی پاککردنەوە', 'created_at' => '2026-08-21 16:00:00'],
            ['amount' => 420000, 'description' => 'Laboratory crown work / کاری لابۆراتۆری تاجی ددان', 'created_at' => '2026-08-19 16:00:00'],
        ];

        foreach ($expenses as $expenseData) {
            Expense::updateOrCreate(
                ['description' => $expenseData['description'], 'created_at' => $expenseData['created_at']],
                $expenseData,
            );
        }
    }
}
