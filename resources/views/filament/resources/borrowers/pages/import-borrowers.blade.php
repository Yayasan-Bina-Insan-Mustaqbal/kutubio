<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-gray-900">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Google Sheet borrower import</h2>
            <p class="mt-2 text-sm text-gray-500">Fetch a snapshot, review the result, then approve only after needs review and invalid rows are resolved.</p>
        </div>
        @if ($this->selectedImportId)
            @php($rows = $this->getSelectedImportRows())
            @php($counts = $this->getSelectedImportCounts())
            <div class="grid gap-4 sm:grid-cols-4">
                @foreach (['new' => 'New', 'matched' => 'Matched', 'needs_review' => 'Needs Review', 'invalid' => 'Invalid'] as $status => $label)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
                        <div class="text-xs uppercase text-gray-500">{{ $label }}</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $counts[$status] ?? 0 }}</div>
                    </div>
                @endforeach
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-gray-900">
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach (['all' => 'All', 'invalid' => 'Invalid', 'needs_review' => 'Needs review', 'corrected' => 'Corrected', 'ignored' => 'Ignored', 'new' => 'New', 'matched' => 'Matched'] as $filter => $label)
                    <button type="button" wire:click="setRowFilter('{{ $filter }}')" class="rounded-md border px-3 py-1 text-sm {{ $this->rowFilter === $filter ? 'border-primary-600 bg-primary-600 text-white' : 'border-gray-300 text-gray-700' }}">{{ $label }}</button>
                @endforeach
            </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b border-gray-200 dark:border-white/10"><th class="p-3">Row</th><th class="p-3">Name</th><th class="p-3">Class</th><th class="p-3">Gender</th><th class="p-3">Status</th><th class="p-3">Reason</th></tr></thead>
                        <tbody>
                            @foreach ($rows as $row)
                                @php($data = $row->raw_data ?? [])
                                <tr class="border-b border-gray-100 dark:border-white/5"><td class="p-3">{{ $row->row_number }}</td><td class="p-3">{{ $data['nama_siswa'] ?? '—' }}</td><td class="p-3">{{ $data['kelas'] ?? '—' }}</td><td class="p-3">{{ $data['jenis_kelamin'] ?? '—' }}</td><td class="p-3">{{ $row->resolution_status }}</td><td class="p-3">{{ $row->error_message ?? ($row->matched_borrower_id ? 'Matched borrower' : 'No candidate') }}</td><td class="p-3"><details><summary class="cursor-pointer text-primary-600">Review</summary>@if ($row->resolution_status === 'needs_review')<div class="mt-2 space-y-2"><select wire:change="matchRow({{ $row->id }}, $event.target.value)" class="rounded border p-1"><option value="">Select borrower</option>@foreach ($this->getCandidates($row->id) as $id => $label)<option value="{{ $id }}">{{ $label }}</option>@endforeach</select></div>@elseif ($row->resolution_status === 'invalid')<form class="mt-2 space-y-2" wire:submit="updateInvalidRow({{ $row->id }}, $event.target.name.value, $event.target.class.value, $event.target.gender.value, $event.submitter.value)"><input name="name" value="{{ $data['nama_siswa'] ?? '' }}" class="w-full rounded border p-1" placeholder="Name"><input name="class" value="{{ $data['kelas'] ?? '' }}" class="w-full rounded border p-1" placeholder="Class"><input name="gender" value="{{ $data['jenis_kelamin'] ?? '' }}" class="w-full rounded border p-1" placeholder="Gender"><button name="action" value="corrected" class="rounded bg-primary-600 px-2 py-1 text-white">Save correction</button><button name="action" value="ignored" class="rounded bg-gray-600 px-2 py-1 text-white">Ignore</button></form>@endif</details></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-gray-900">
            <h3 class="font-bold text-gray-900 dark:text-white">Recent previews</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead><tr><th class="p-2">Academic year</th><th class="p-2">Status</th><th class="p-2">Rows</th><th class="p-2">Fetched</th><th class="p-2">Action</th></tr></thead>
                    <tbody>
                        @foreach ($this->getImports() as $import)
                            <tr class="border-t border-gray-100 dark:border-white/10"><td class="p-2">{{ $import->academic_year }}</td><td class="p-2">{{ $import->status }}</td><td class="p-2">{{ $import->rows_count }}</td><td class="p-2">{{ $import->fetched_at?->format('Y-m-d H:i') }}</td><td class="p-2"><button type="button" wire:click="selectImport({{ $import->id }})" class="font-medium text-primary-600 hover:underline">Review</button></td>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
