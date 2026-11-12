@foreach (['status' => 'bg-green-600', 'error' => 'bg-red-600'] as $key => $tone)
    @if (session($key))
        <div class="flex justify-end">
            <div role="{{ $key === 'error' ? 'alert' : 'status' }}" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 15000)"
                class="relative flex w-full sm:w-auto sm:max-w-lg p-3 pr-12 text-sm text-white {{ $tone }} rounded-md shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 mr-2 mt-0.5 shrink-0" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <p class="text-base">{{ session($key) }}</p>
                <button type="button" @click="show = false" aria-label="{{ __('app.dismiss') }}"
                    class="absolute top-1.5 right-1.5 flex h-8 w-8 items-center justify-center rounded-md text-white hover:bg-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="h-5 w-5" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    @endif
@endforeach
