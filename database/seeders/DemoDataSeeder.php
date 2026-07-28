<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\Dependent;
use App\Models\Employee;
use App\Models\MedicalDepartment;
use App\Models\OrganizationUnit;
use App\Models\SurveySubmission;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDepartment;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private int $nationalIdSequence = 500000001;

    public function run(): void
    {
        if (Employee::where('full_name', 'أحمد خالد ياسين')->exists()) {
            $this->command->info('بيانات تجريبية موجودة مسبقاً — لن يتم تكرارها.');

            return;
        }

        $reviewer = User::first();

        if (! $reviewer) {
            $this->command->warn('لا يوجد مستخدم بالنظام — شغّل DatabaseSeeder أولاً.');

            return;
        }

        $sections = OrganizationUnit::where('level', 3)->inRandomOrder()->take(6)->get();

        if ($sections->count() < 6) {
            $this->command->warn('لا توجد وحدات تنظيمية كافية (مستوى 3) — شغّل OrganizationUnitSeeder أولاً.');

            return;
        }

        $clinics = collect(['عيادة باطنة', 'عيادة عظام', 'عيادة أطفال', 'عيادة نساء وتوليد'])
            ->map(fn (string $name) => Clinic::firstOrCreate(['name' => $name], ['is_active' => true]));

        $employees = collect();

        $employees->push($this->createEmployee(
            fullName: 'أحمد خالد ياسين',
            gender: 'male',
            maritalStatus: 'married',
            sectionId: $sections[0]->id,
            reviewerId: $reviewer->id,
            dependents: [
                ['type' => 'spouse', 'full_name' => 'هبة سامي ياسين', 'gender' => 'female'],
                ['type' => 'child', 'full_name' => 'خالد أحمد ياسين', 'gender' => 'male'],
                ['type' => 'child', 'full_name' => 'لين أحمد ياسين', 'gender' => 'female'],
                ['type' => 'parent', 'full_name' => 'خالد يوسف ياسين', 'gender' => 'male', 'parent_type' => 'father'],
            ],
        ));

        $employees->push($this->createEmployee(
            fullName: 'سارة عبد الرحمن نصار',
            gender: 'female',
            maritalStatus: 'single',
            sectionId: $sections[1]->id,
            reviewerId: $reviewer->id,
            dependents: [
                ['type' => 'parent', 'full_name' => 'عبد الرحمن محمود نصار', 'gender' => 'male', 'parent_type' => 'father'],
                ['type' => 'parent', 'full_name' => 'منى إبراهيم نصار', 'gender' => 'female', 'parent_type' => 'mother'],
            ],
        ));

        $employees->push($this->createEmployee(
            fullName: 'محمود عيسى الشريف',
            gender: 'male',
            maritalStatus: 'divorced',
            sectionId: $sections[2]->id,
            reviewerId: $reviewer->id,
            dependents: [
                ['type' => 'child', 'full_name' => 'يوسف محمود الشريف', 'gender' => 'male'],
            ],
        ));

        $employees->push($this->createEmployee(
            fullName: 'رنا فتحي دياب',
            gender: 'female',
            maritalStatus: 'married',
            sectionId: $sections[3]->id,
            reviewerId: $reviewer->id,
            dependents: [
                ['type' => 'spouse', 'full_name' => 'باسل نبيل عودة', 'gender' => 'male'],
            ],
        ));

        $employees->push($this->createEmployee(
            fullName: 'وليد سمير أبو حمدة',
            gender: 'male',
            maritalStatus: 'widowed',
            sectionId: $sections[4]->id,
            reviewerId: $reviewer->id,
            dependents: [],
        ));

        // Pending survey submissions (not yet approved) — for testing the approval flow.
        $this->createPendingSubmission(
            fullName: 'ليلى منصور غانم',
            gender: 'female',
            maritalStatus: 'married',
            sectionId: $sections[5]->id,
            dependents: [
                ['type' => 'spouse', 'full_name' => 'منصور غانم غانم', 'gender' => 'male'],
                ['type' => 'child', 'full_name' => 'جود منصور غانم', 'gender' => 'female'],
            ],
        );

        $this->createPendingSubmission(
            fullName: 'إياد كمال حماد',
            gender: 'male',
            maritalStatus: 'single',
            sectionId: $sections[0]->id,
            dependents: [],
        );

        $employees = $employees->filter();

        $clinicsDept = MedicalDepartment::where('name', 'clinics')->first();
        $pharmacy = MedicalDepartment::where('name', 'pharmacy')->first();
        $laboratory = MedicalDepartment::where('name', 'laboratory')->first();
        $dental = MedicalDepartment::where('name', 'dental')->first();

        // Visit 1: employee patient, today, with a clinic exam + pharmacy.
        if ($employee = $employees->get(0)) {
            $visit = $this->createVisit($employee, patientEmployee: $employee, clinicId: null, visitDate: now()->toDateString(), reviewerId: $reviewer->id);
            $this->addDepartment($visit, $clinicsDept, $clinics[0]->id, 60, $reviewer->id);
            $this->addDepartment($visit, $pharmacy, null, 40, $reviewer->id);
        }

        // Visit 2: dependent (spouse) patient, today, lab only, no clinic.
        if (($employee = $employees->get(0)) && ($spouse = $employee->dependents->firstWhere('type', 'spouse'))) {
            $visit = $this->createVisit($employee, patientDependent: $spouse, clinicId: null, visitDate: now()->toDateString(), reviewerId: $reviewer->id);
            $this->addDepartment($visit, $laboratory, null, 25, $reviewer->id);
        }

        // Visit 3: another employee, today, dental only.
        if ($employee = $employees->get(1)) {
            $visit = $this->createVisit($employee, patientEmployee: $employee, clinicId: null, visitDate: now()->toDateString(), reviewerId: $reviewer->id);
            $this->addDepartment($visit, $dental, null, 80, $reviewer->id);
        }

        // Visit 4: an older visit (past date) for history/date-filter testing.
        if ($employee = $employees->get(2)) {
            $visit = $this->createVisit($employee, patientEmployee: $employee, clinicId: null, visitDate: now()->subDays(5)->toDateString(), reviewerId: $reviewer->id);
            $this->addDepartment($visit, $clinicsDept, $clinics[1]->id, null, $reviewer->id);
        }

        // Visit 5: employee's child patient, today, no department yet (open visit).
        if (($employee = $employees->get(3)) && $employee->dependents->firstWhere('type', 'spouse')) {
            $spouse = $employee->dependents->firstWhere('type', 'spouse');
            $this->createVisit($employee, patientDependent: $spouse, clinicId: null, visitDate: now()->toDateString(), reviewerId: $reviewer->id);
        }

        $this->command->info('تم إنشاء بيانات تجريبية: '.$employees->count().' موظفين، طلبات استبيان معلّقة، عيادات، وزيارات.');
    }

    private function nextNationalId(): string
    {
        while (
            Employee::where('national_id', (string) $this->nationalIdSequence)->exists()
            || Dependent::where('national_id', (string) $this->nationalIdSequence)->exists()
            || SurveySubmission::where('national_id', (string) $this->nationalIdSequence)->exists()
        ) {
            $this->nationalIdSequence++;
        }

        return (string) $this->nationalIdSequence++;
    }

    private function createEmployee(
        string $fullName,
        string $gender,
        string $maritalStatus,
        int $sectionId,
        int $reviewerId,
        array $dependents,
    ): ?Employee {
        $nationalId = $this->nextNationalId();

        if (Employee::where('full_name', $fullName)->exists()) {
            return Employee::where('full_name', $fullName)->first();
        }

        $employee = Employee::create([
            'full_name' => $fullName,
            'national_id' => $nationalId,
            'gender' => $gender,
            'marital_status' => $maritalStatus,
            'organization_unit_id' => $sectionId,
            'status' => 'active',
            'source' => 'admin',
            'approved_by' => $reviewerId,
            'approved_at' => now(),
        ]);

        foreach ($dependents as $dependent) {
            Dependent::create([
                'employee_id' => $employee->id,
                'type' => $dependent['type'],
                'full_name' => $dependent['full_name'],
                'national_id' => $this->nextNationalId(),
                'gender' => $dependent['gender'],
                'parent_type' => $dependent['parent_type'] ?? null,
            ]);
        }

        return $employee->fresh('dependents');
    }

    private function createPendingSubmission(
        string $fullName,
        string $gender,
        string $maritalStatus,
        int $sectionId,
        array $dependents,
    ): void {
        if (SurveySubmission::whereJsonContains('raw_data->full_name', $fullName)->exists()) {
            return;
        }

        $nationalId = $this->nextNationalId();

        $rawDependents = array_map(function (array $dependent) {
            return [
                'type' => $dependent['type'],
                'full_name' => $dependent['full_name'],
                'national_id' => $this->nextNationalId(),
                'gender' => $dependent['gender'],
                'parent_type' => $dependent['parent_type'] ?? null,
            ];
        }, $dependents);

        SurveySubmission::create([
            'raw_data' => [
                'full_name' => $fullName,
                'national_id' => $nationalId,
                'gender' => $gender,
                'marital_status' => $maritalStatus,
                'organization_unit_id' => $sectionId,
                'dependents' => $rawDependents,
            ],
            'national_id' => $nationalId,
            'status' => 'pending',
        ]);
    }

    private function createVisit(
        Employee $quotaOwner,
        ?Employee $patientEmployee = null,
        ?Dependent $patientDependent = null,
        ?int $clinicId = null,
        ?string $visitDate = null,
        ?int $reviewerId = null,
    ): Visit {
        return Visit::create([
            'employee_id' => $quotaOwner->id,
            'patient_employee_id' => $patientEmployee?->id,
            'patient_dependent_id' => $patientDependent?->id,
            'clinic_id' => $clinicId,
            'visit_date' => $visitDate ?? now()->toDateString(),
            'recorded_by' => $reviewerId,
        ]);
    }

    private function addDepartment(Visit $visit, ?MedicalDepartment $department, ?int $clinicId, ?float $amountBeforeDiscount, int $reviewerId): void
    {
        if (! $department) {
            return;
        }

        $visitDepartment = VisitDepartment::create([
            'visit_id' => $visit->id,
            'medical_department_id' => $department->id,
            'applied_discount_percentage' => $department->discount_percentage,
            'applied_max_discount_amount' => $department->max_discount_amount,
            'amount_before_discount' => $amountBeforeDiscount,
            'amount_after_discount' => null,
            'added_at' => now(),
            'added_by' => $reviewerId,
        ]);

        if ($amountBeforeDiscount !== null) {
            $visitDepartment->update(['amount_after_discount' => $visitDepartment->calculateAmountAfterDiscount()]);
        }

        if ($department->name === 'clinics' && $clinicId) {
            $visit->update(['clinic_id' => $clinicId]);
        }

        $visit->recalculateTotals();
    }
}
