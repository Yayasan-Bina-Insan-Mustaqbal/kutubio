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
            // Query for students, teachers, and staff
            // We assume a 'person' table where 'type' defines the role
            $surql = "SELECT *, record::id(id) as sid FROM person WHERE type IN ['student', 'teacher', 'staff'];";
            
            $results = $client->query($surql);

            if (empty($results) || !isset($results[0]['result'])) {
                Log::warning('SurrealDB sync returned no results or failed.');
                return;
            }

            $persons = $results[0]['result'];
            $count = 0;

            foreach ($persons as $person) {
                $type = match ($person['type']) {
                    'student' => BorrowerType::Student,
                    'teacher' => BorrowerType::Teacher,
                    'staff' => BorrowerType::Staff,
                    default => null,
                };

                if (!$type) {
                    continue;
                }

                // Identifier mapping: NIS for students, NIP for others, or fallback to sid
                $identifier = $person['nis'] ?? $person['nip'] ?? $person['identifier'] ?? $person['sid'];

                Borrower::updateOrCreate(
                    ['identifier' => $identifier],
                    [
                        'name' => $person['name'] ?? $person['full_name'],
                        'type' => $type,
                        'class' => $person['class'] ?? null,
                        'surreal_id' => $person['id'],
                        'status' => 'active', // Assume active if synced
                        'notes' => $person['notes'] ?? null,
                    ]
                );

                $count++;
            }

            Log::info("Successfully synced {$count} borrowers from SurrealDB.");
            
        } catch (\Exception $e) {
            Log::error('SurrealDB Sync Job Failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
