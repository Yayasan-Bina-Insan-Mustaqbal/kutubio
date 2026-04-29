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
    protected $description = 'Sync students and teachers from SurrealDB as borrowers';

    /**
     * Execute the console command.
     */
    public function handle(SurrealDbClient $client): int
    {
        $this->info('Starting sync from SurrealDB...');

        try {
            $this->syncStudents($client);
            $this->syncTeachers($client);
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
                <-is_student<-person[0].full_name AS full_name 
            FROM student;
        SURQL;

        $results = $client->query($surql);
        $students = $results[0]['result'] ?? [];

        $count = 0;
        foreach ($students as $data) {
            Borrower::updateOrCreate(
                ['surreal_id' => $data['id']],
                [
                    'name' => $data['full_name'] ?? 'Unknown Student',
                    'type' => BorrowerType::Student,
                    'identifier' => $data['student_code'],
                    'status' => 'active',
                ]
            );
            $count++;
        }

        $this->info("Synced {$count} students.");
    }

    private function syncTeachers(SurrealDbClient $client): void
    {
        $this->info('Syncing teachers...');
        
        // Teachers query - assuming similar structure based on GRAPH_DATABASE_GUIDE
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
}
