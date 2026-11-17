<x-app-layout>
    <x-slot name="title">{{ __('guide.title') }}</x-slot>
    <x-slot name="header">
        <div>
            <x-page-title>{{ __('guide.title') }}</x-page-title>
            <p class="mt-1 text-sm text-gray-500">{{ __('guide.intro') }}</p>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[15rem_1fr] lg:items-start">
        <nav class="rounded-xl bg-white p-5 shadow-md lg:sticky lg:top-24" aria-label="{{ __('guide.contents') }}">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('guide.contents') }}</p>
            <ol class="mt-3 space-y-2 text-sm">
                @foreach (__('guide.sections') as $key => $heading)
                    <li><a href="#{{ $key }}" class="text-slate-600 hover:text-slate-900 hover:underline">{{ $loop->iteration }}. {{ $heading }}</a></li>
                @endforeach
            </ol>
        </nav>

        <div class="min-w-0 space-y-6 text-sm leading-relaxed">
            <x-card :title="'1. '.__('guide.sections.overview')" id="overview" class="scroll-mt-24">
                <div class="space-y-3">
                    <p>{{ __('guide.overview.p1') }}</p>
                    <p class="rounded-lg border border-green-200 bg-green-50 p-3 text-green-900">{{ __('guide.overview.p2') }}</p>
                    <p>{{ __('guide.overview.p3') }}</p>
                </div>
            </x-card>

            <x-card :title="'2. '.__('guide.sections.roles')" id="roles" class="scroll-mt-24">
                <x-data-table :headings="__('guide.roles.headings')">
                    @foreach (App\Enums\UserSide::cases() as $role)
                        <tr @class(['block md:table-row px-4 py-3 md:p-0 border-b border-blue-gray-50', 'bg-blue-50/60' => $role === $side])>
                            <td class="block md:table-cell md:px-6 md:py-3 align-top font-medium whitespace-nowrap">
                                {{ $role->label() }}
                                @if ($role === $side)
                                    <x-badge tone="blue" class="ml-1">{{ __('guide.you') }}</x-badge>
                                @endif
                            </td>
                            <td class="block md:table-cell mt-1 md:mt-0 md:px-6 md:py-3 align-top">{{ __('guide.roles.'.$role->value) }}</td>
                        </tr>
                    @endforeach
                </x-data-table>
            </x-card>

            <x-card :title="'3. '.__('guide.sections.find')" id="find" class="scroll-mt-24">
                <ol class="space-y-4">
                    @foreach (__('guide.find.steps') as $step)
                        <li class="flex gap-4">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-700 text-xs font-bold text-white">{{ $loop->iteration }}</span>
                            <div>
                                <p class="font-semibold text-blue-gray-900">{{ $step['title'] }}</p>
                                <p class="text-gray-700">{{ str_replace(':limit', $candidateLimit, $step['text']) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
                <p class="mt-5 border-t border-blue-gray-50 pt-4 text-gray-600">{{ __('guide.find.withdraw') }}</p>
            </x-card>

            <x-card :title="'4. '.__('guide.sections.score')" id="score" class="scroll-mt-24">
                <p>{{ __('guide.score.p1') }}</p>
                <div class="mt-4">
                    <x-data-table :headings="__('guide.score.headings')">
                        @foreach ($rules as $rule)
                            <tr class="block md:table-row px-4 py-3 md:p-0 border-b border-blue-gray-50">
                                <td class="block md:table-cell md:px-6 md:py-3 align-top font-medium whitespace-nowrap">{{ $rule['label'] }}</td>
                                <td class="block md:table-cell md:px-6 md:py-3 align-top tabular-nums">{{ $rule['weight'] }}%</td>
                                <td class="block md:table-cell mt-1 md:mt-0 md:px-6 md:py-3 align-top">
                                    {{ Lang::has('guide.score.how.'.$rule['key'])
                                        ? __('guide.score.how.'.$rule['key'], [
                                            'province' => $locationPoints['province'], 'district' => $locationPoints['district'], 'village' => $locationPoints['village'],
                                            'full' => $areaFullWithin, 'zero' => $areaZeroFrom,
                                        ])
                                        : __('guide.score.other_rule') }}
                                </td>
                            </tr>
                        @endforeach
                    </x-data-table>
                </div>
                <ul class="mt-4 list-disc space-y-2 pl-5">
                    <li>{{ __('guide.score.colors') }}</li>
                    <li>{{ __('guide.score.rules_with_data') }}</li>
                    <li>{{ __('guide.score.caps', ['cap' => $capPercent]) }}</li>
                    <li>{{ __('guide.score.compared') }}</li>
                    <li>{{ __('guide.score.pending') }}</li>
                </ul>
                <div class="mt-4 grid gap-3 lg:grid-cols-2">
                    <div class="rounded-lg border border-blue-gray-50 bg-blue-gray-50/40 p-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('guide.score.example_title') }}</p>
                        <p class="mt-1 font-mono text-xs sm:text-sm">{{ __('guide.score.example') }}</p>
                    </div>
                    <div class="rounded-lg border border-blue-gray-50 bg-blue-gray-50/40 p-3">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('guide.score.capped_example_title') }}</p>
                        <p class="mt-1 font-mono text-xs sm:text-sm">{{ __('guide.score.capped_example', ['cap' => $capPercent]) }}</p>
                    </div>
                </div>
            </x-card>

            <x-card :title="'5. '.__('guide.sections.confirm')" id="confirm" class="scroll-mt-24">
                <p>{{ __('guide.confirm.p1') }}</p>
                <ul class="mt-3 space-y-2">
                    <li class="flex gap-2"><x-badge tone="green" class="shrink-0">{{ __('matches.buttons.confirm') }}</x-badge> <span>{{ __('guide.confirm.confirm') }}</span></li>
                    <li class="flex gap-2"><x-badge tone="red" class="shrink-0">{{ __('matches.buttons.reject') }}</x-badge> <span>{{ __('guide.confirm.reject') }}</span></li>
                </ul>
                <p class="mt-3 text-gray-600">{{ __('guide.confirm.p2') }}</p>
                <div class="mt-4">
                    <x-link-button variant="view" :href="route('matches.queue')">{{ __('app.nav.queue') }}</x-link-button>
                </div>
            </x-card>

            <x-card :title="'6. '.__('guide.sections.statuses')" id="statuses" class="scroll-mt-24">
                <dl class="space-y-3">
                    @foreach ($statuses as $status)
                        <div class="flex gap-3">
                            <dt class="w-28 shrink-0"><x-badge :tone="$status->tone()">{{ $status->label() }}</x-badge></dt>
                            <dd>{{ __('guide.statuses.'.$status->value) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>

            <x-card :title="'7. '.__('guide.sections.review')" id="review" class="scroll-mt-24">
                <div class="space-y-3">
                    <p><x-badge tone="amber">{{ __('matches.banner.needs_review') }}</x-badge> {{ __('guide.review.p1') }}</p>
                    <p>{{ __('guide.review.p2') }}</p>
                </div>
            </x-card>

            <x-card :title="'8. '.__('guide.sections.list')" id="list" class="scroll-mt-24">
                <div class="space-y-3">
                    <p>{{ __('guide.list.p1') }}</p>
                    <p>{{ __('guide.list.p2') }}</p>
                    <p>{{ __('guide.list.p3') }}</p>
                </div>
                <div class="mt-4">
                    <x-link-button variant="view" :href="route('matches.index')">{{ __('app.nav.matches') }}</x-link-button>
                </div>
            </x-card>

            <x-card :title="'9. '.__('guide.sections.admin')" id="admin" class="scroll-mt-24">
                <ul class="list-disc space-y-2 pl-5">
                    @foreach (['users', 'sync', 'unmatch', 'review'] as $task)
                        <li>{{ __('guide.admin.'.$task) }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card :title="'10. '.__('guide.sections.api')" id="api" class="scroll-mt-24">
                <div class="space-y-4">
                    <p>{{ __('guide.api.p1') }}</p>

                    @foreach ($apiSides as $apiSide)
                        <div>
                            <p class="font-semibold text-blue-gray-900">{{ __('guide.api.key', ['side' => $apiSide->label()]) }}</p>
                            @if (isset($apiClients[$apiSide->value]))
                                <input type="text" readonly value="{{ $apiClients[$apiSide->value]->token }}" onclick="this.select()"
                                    aria-label="{{ __('guide.api.key', ['side' => $apiSide->label()]) }}"
                                    class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50 font-mono text-xs text-gray-800 focus:border-slate-500 focus:ring-slate-500">
                                <p class="mt-1 text-xs text-gray-500">{{ __('guide.api.select_hint') }}</p>
                            @else
                                <p class="mt-1 rounded-lg border border-amber-200 bg-amber-50 p-3 text-amber-900">{{ __('guide.api.no_key', ['side' => $apiSide->label()]) }}</p>
                            @endif
                        </div>
                    @endforeach

                    <div>
                        <p class="font-semibold text-blue-gray-900">{{ __('guide.api.endpoint') }}</p>
                        <p class="mt-1 break-all font-mono text-xs">GET {{ route('api.v1.matches.index') }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-blue-gray-900">{{ __('guide.api.example') }}</p>
                        <pre class="mt-1 overflow-x-auto rounded-lg bg-gray-800 p-3 font-mono text-xs text-gray-100">curl -H "Authorization: Bearer &lt;key&gt;" "{{ route('api.v1.matches.index') }}?page=1"</pre>
                    </div>

                    <ul class="list-disc space-y-2 pl-5 text-gray-700">
                        @foreach (__('guide.api.notes') as $note)
                            <li>{{ $note }}</li>
                        @endforeach
                    </ul>
                </div>
            </x-card>

            <x-card :title="'11. '.__('guide.sections.tips')" id="tips" class="scroll-mt-24">
                <dl class="divide-y divide-blue-gray-50">
                    @foreach (__('guide.tips') as $tip)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <dt class="font-semibold text-blue-gray-900">{{ $tip['q'] }}</dt>
                            <dd class="mt-1 text-gray-700">{{ $tip['a'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </x-card>
        </div>
    </div>
</x-app-layout>
