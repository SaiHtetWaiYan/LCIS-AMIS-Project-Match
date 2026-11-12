@props(['headings' => []])

{{-- LCIS-style table; on small screens rows stack (cells use "block md:table-cell"). --}}
<div class="-mx-4 sm:-mx-6 overflow-x-auto">
    <table {{ $attributes->merge(['class' => 'min-w-full text-left text-sm']) }}>
        <thead class="hidden md:table-header-group">
            <tr>
                @foreach ($headings as $heading)
                    <th scope="col" class="px-6 py-4 border-y border-blue-gray-100 bg-blue-gray-50/50 font-normal text-blue-gray-900/70 whitespace-nowrap">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
