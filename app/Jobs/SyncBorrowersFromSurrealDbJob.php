<?php

namespace App\Jobs;

use App\Enums\BorrowerType;
use App\Models\Borrower;
use App\Services\SurrealDbClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBorrowersFromSurrealDbJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SurrealDbClient $client): void
    {
        Log::info('Starting SurrealDB Borrower Sync...');

        try {
            $this->syncStudents($client);
            $this->syncTeachers($client);
            $this->syncEmployees($client);
            
            Log::info("SurrealDB Borrower Sync completed.");
        } catch (\Exception $e) {
            Log::error('SurrealDB Sync Job Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function syncStudents(SurrealDbClient $client): void
    {
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

        if (!is_array($students)) {
            Log::warning('SurrealDB student sync result is not iterable.');
            return;
        }

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

        Log::info("Synced {$count} students from SurrealDB.");
    }

    private function syncTeachers(SurrealDbClient $client): void
    {
        $surql = <<<'SURQL'
            SELECT 
                id, 
                teacher_code,
                <-is_teacher<-person[0].full_name AS full_name 
            FROM teacher;
        SURQL;

        $results = $client->query($surql);
        $teachers = $results[0]['result'] ?? [];

        if (!is_array($teachers)) {
            Log::warning('SurrealDB teacher sync result is not iterable.');
            return;
        }

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

        Log::info("Synced {$count} teachers from SurrealDB.");
    }

    private function syncEmployees(SurrealDbClient $client): void
    {
        $surql = <<<'SURQL'
            SELECT 
                id, 
                employee_code,
                <-is_employee<-person[0].full_name AS full_name 
            FROM employee;
        SURQL;

        $results = $client->query($surql);
        $employees = $results[0]['result'] ?? [];

        if (!is_array($employees)) {
            Log::warning('SurrealDB employee sync result is not iterable.');
            return;
        }

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

        Log::info("Synced {$count} staff from SurrealDB.");
    }
}
