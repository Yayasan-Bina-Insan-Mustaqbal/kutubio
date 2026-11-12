<div class="flex min-h-80 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-gray-900">
    @php
        $coverExists = filled($coverPath) && \Illuminate\Support\Facades\Storage::disk('public')->exists($coverPath);
        $coverUrl = $coverExists
            ? route('storage.public', ['path' => $coverPath])
            : \Illuminate\Support\Facades\URL::asset('images/book-cover-placeholder.svg');
        $coverAlt = $coverExists ? 'Book cover' : 'No book cover available';
    @endphp
    <img src="{{ $coverUrl }}" alt="{{ $coverAlt }}" class="max-h-[340px] w-full object-contain">
</div>
