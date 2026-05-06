<?php

namespace App\Console\Commands;

use App\Enums\BorrowerType;
use App\Models\Borrower;
use App\Services\SurrealDbClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBorrowersFromSurrealDb extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kutubio:sync-borrowers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync students, teachers, and employees from SurrealDB as borrowers';

    /**
     * Execute the console command.
     */
    public function handle(SurrealDbClient $client): int
    {
        $this->info('Starting sync from SurrealDB...');

        try {
            $this->syncStudents($client);
            $this->syncTeachers($client);
            $this->syncEmployees($client);
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('SurrealDB Borrower Sync failed', ['error' => $e->getMessage()]);
            return 1;
        }

        $this->info('Sync completed successfully.');
        return 0;
    }

    private function syncStudents(SurrealDbClient $client): void
    {
        $this->info('Syncing students...');
        
        $surql = <<<'SURQL'
            SELECT 
                id, 
                student_code,
                active_status,
                <-is_student<-person[0].full_name AS full_name,
                ->has_enrollment->enrollment[0]->in_class->class_group[0].class_name AS class_name
            FROM student;
        SURQL;

        $results = $client->query($surql);
        $students = $results[0]['result'] ?? [];

        $count = 0;
        foreach ($students as $data) {
            $status = match ($data['active_status'] ?? 'active') {
                'active' => 'active',
                'attendance_only' => 'potential',
                default => 'inactive',
            };

            Borrower::updateOrCreate(
                ['surreal_id' => $data['id']],
                [
                    'name' => $data['full_name'] ?? 'Unknown Student',
                    'type' => BorrowerType::Student,
                    'identifier' => $data['student_code'] ?? $data['id'],
                    'class' => $data['class_name'] ?? null,
                    'status' => $status,
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} students.");
    }

    private function syncTeachers(SurrealDbClient $client): void
    {
        $this->info('Syncing teachers...');
        
        $surql = <<<'SURQL'
            SELECT 
                id, 
                teacher_code,
                <-is_teacher<-person[0].full_name AS full_name 
            FROM teacher;
        SURQL;

        $results = $client->query($surql);
        $teachers = $results[0]['result'] ?? [];

        $count = 0;
        foreach ($teachers as $data) {
            Borrower::updateOrCreate(
                ['surreal_id' => $data['id']],
                [
                    'name' => $data['full_name'] ?? 'Unknown Teacher',
                    'type' => BorrowerType::Teacher,
                    'identifier' => $data['teacher_code'] ?? $data['id'],
                    'status' => 'active',
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} teachers.");
    }

    private function syncEmployees(SurrealDbClient $client): void
    {
        $this->info('Syncing staff...');
        
        $surql = <<<'SURQL'
            SELECT 
                id, 
                employee_code,
                <-is_employee<-person[0].full_name AS full_name 
            FROM employee;
        SURQL;

        $results = $client->query($surql);
        $employees = $results[0]['result'] ?? [];

        $count = 0;
        foreach ($employees as $data) {
            Borrower::updateOrCreate(
                ['surreal_id' => $data['id']],
                [
                    'name' => $data['full_name'] ?? 'Unknown Staff',
                    'type' => BorrowerType::Staff,
                    'identifier' => $data['employee_code'] ?? $data['id'],
                    'status' => 'active',
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} staff.");
    }
}
