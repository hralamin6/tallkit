<div>
    <x-header title="Activity Feed" subtitle="All system activities" separator>
        <x-slot:actions>
            <x-button
                wire:click="toggleStats"
                :label="$showStats ? 'Hide Stats' : 'Show Stats'"
                icon="o-chart-bar"
                class="btn-ghost btn-sm"
            />
            @can('activity.delete')
                <x-button
                    wire:click="openClearModal"
                    label="Clear Activities"
                    icon="o-trash"
                    class="btn-error btn-sm"
                />
            @endcan
        </x-slot:actions>
    </x-header>

    {{-- Dashboard Statistics --}}
    @if($showStats && $stats)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            {{-- Total Activities --}}
            <x-stat
                title="Total Activities"
                :value="number_format($stats['total'])"
                icon="o-document-text"
                color="text-primary"
            />

            {{-- Unique Users --}}
            <x-stat
                title="Active Users"
                :value="number_format($stats['unique_users'])"
                icon="o-user-group"
                color="text-success"
            />

            {{-- By Log Type --}}
            <x-card class="col-span-1">
                <div class="text-sm font-semibold mb-2">By Log Type</div>
                <div class="space-y-1">
                    @forelse($stats['by_log']->take(3) as $log)
                        <div class="flex justify-between text-xs">
                            <span class="badge badge-sm badge-ghost">{{ $log->log_name }}</span>
                            <span class="font-medium">{{ number_format($log->count) }}</span>
                        </div>
                    @empty
                        <span class="text-xs text-base-content/60">No data</span>
                    @endforelse
                </div>
            </x-card>

            {{-- By Event --}}
            <x-card class="col-span-1">
                <div class="text-sm font-semibold mb-2">By Event</div>
                <div class="space-y-1">
                    @forelse($stats['by_event']->take(3) as $event)
                        <div class="flex justify-between text-xs">
                            <span class="badge badge-sm badge-{{ $this->getEventColor($event->event) }}">{{ $event->event }}</span>
                            <span class="font-medium">{{ number_format($event->count) }}</span>
                        </div>
                    @empty
                        <span class="text-xs text-base-content/60">No data</span>
                    @endforelse
                </div>
            </x-card>
        </div>

        {{-- Time Range Selector --}}
        <x-card class="mb-6">
            <div class="flex items-center gap-4">
                <span class="text-sm font-medium">Stats Time Range:</span>
                <x-radio wire:model.live="timeRange" :options="[
                    ['id' => '7', 'name' => '7 Days'],
                    ['id' => '30', 'name' => '30 Days'],
                    ['id' => '90', 'name' => '90 Days'],
                ]" inline />
            </div>
        </x-card>
    @endif

    {{-- Filters --}}
    <x-card title="Filters" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-input
                wire:model.live.debounce="filters.search"
                label="Search"
                placeholder="Search activities..."
                icon="o-magnifying-glass"
            />

            <x-select
                wire:model.live="filters.log_name"
                label="Log Type"
                :options="$logNames->map(fn($name) => ['id' => $name, 'name' => ucfirst($name)])"
                placeholder="All types"
            />

            <x-select
                wire:model.live="filters.event"
                label="Event"
                :options="$events->map(fn($event) => ['id' => $event, 'name' => ucfirst(str_replace('_', ' ', $event))])"
                placeholder="All events"
            />

            <x-input
                wire:model.live="filters.date_from"
                label="From Date"
                type="date"
            />

            <x-input
                wire:model.live="filters.date_to"
                label="To Date"
                type="date"
            />

            <div class="flex items-end">
                <x-button
                    wire:click="clearFilters"
                    label="Clear Filters"
                    icon="o-x-mark"
                    class="btn-ghost w-full"
                />
            </div>
        </div>
    </x-card>

    {{-- Activity Timeline --}}
    <x-card>
        <div class="space-y-4">
            @forelse($activities as $activity)
                <div class="flex gap-4 pb-4 border-b border-base-300 last:border-0">
                    {{-- Icon --}}
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-{{ $this->getEventColor($activity->event) }} flex items-center justify-center">
                            <x-icon name="{{ $this->getEventIcon($activity->event) }}" class="w-5 h-5 text-white" />
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-base-content">
                                    {{ $activity->description }}
                                </p>
                                <div class="flex items-center gap-2 mt-1 text-sm text-base-content/60">
                                    @if($activity->causer)
                                        <span class="flex items-center gap-1">
                                            <x-icon name="o-user" class="w-4 h-4" />
                                            {{ $activity->causer->name }}
                                        </span>
                                        <span>•</span>
                                    @endif
                                    <span class="flex items-center gap-1">
                                        <x-icon name="o-clock" class="w-4 h-4" />
                                        {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                    @if($activity->log_name)
                                        <span>•</span>
                                        <span class="badge badge-sm badge-ghost">{{ $activity->log_name }}</span>
                                    @endif
                                </div>

                                {{-- Properties/Changes --}}
                                @if($activity->properties && (isset($activity->properties['attributes']) || isset($activity->properties['old'])))
                                    <div class="mt-2">
                                        <button
                                            onclick="document.getElementById('details-{{ $activity->id }}').classList.toggle('hidden')"
                                            class="text-xs text-primary hover:underline"
                                        >
                                            View Details
                                        </button>
                                        <div id="details-{{ $activity->id }}" class="hidden mt-2 p-3 bg-base-200 rounded text-xs">
                                            @if($activity->event === 'updated' && isset($activity->properties['old']))
                                                <div class="font-semibold mb-1">Changes:</div>
                                                @foreach($activity->properties['attributes'] as $key => $value)
                                                    @if(isset($activity->properties['old'][$key]) && $activity->properties['old'][$key] != $value)
                                                        <div class="mb-1">
                                                            <span class="text-base-content/60">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                            <span class="line-through text-error">
                                                                {{ is_array($activity->properties['old'][$key]) ? json_encode($activity->properties['old'][$key]) : $activity->properties['old'][$key] }}
                                                            </span>
                                                            →
                                                            <span class="text-success">
                                                                {{ is_array($value) ? json_encode($value) : $value }}
                                                            </span>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @else
                                                <pre class="whitespace-pre-wrap">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- IP & User Agent --}}
                                @if($activity->ip_address || $activity->user_agent)
                                    <div class="mt-1 text-xs text-base-content/40">
                                        @if($activity->ip_address)
                                            <span>IP: {{ $activity->ip_address }}</span>
                                        @endif
                                        @if($activity->user_agent)
                                            <span class="ml-2">{{ \Illuminate\Support\Str::limit($activity->user_agent, 50) }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-base-content/60">
                    <x-icon name="o-inbox" class="w-12 h-12 mx-auto mb-2" />
                    <p>No activities found</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $activities->links() }}
        </div>
    </x-card>

    {{-- Clear Activities Modal --}}
    <x-modal wire:model="showClearModal" title="Clear Activities" subtitle="Permanently delete activities from the system">
        @if($showClearModal)
            <div class="space-y-4">
                {{-- Stats --}}
                @if($clearStats)
                    <x-alert icon="o-information-circle" class="alert-info">
                        <div class="text-sm">
                            <div><strong>Total Activities:</strong> {{ number_format($clearStats['total']) }}</div>
                            @if($clearStats['oldest'])
                                <div><strong>Oldest:</strong> {{ $clearStats['oldest']->format('M d, Y H:i') }}</div>
                            @endif
                            @if($clearStats['newest'])
                                <div><strong>Newest:</strong> {{ $clearStats['newest']->format('M d, Y H:i') }}</div>
                            @endif
                        </div>
                    </x-alert>
                @endif

                {{-- Clear Filters --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-input
                        wire:model.live="clearFilters.days"
                        label="Delete activities older than (days)"
                        type="number"
                        min="1"
                        placeholder="e.g., 90"
                        hint="Leave empty to not filter by date"
                    />

                    <x-select
                        wire:model.live="clearFilters.log_name"
                        label="Log Type"
                        :options="$logNames->map(fn($name) => ['id' => $name, 'name' => ucfirst($name)])"
                        placeholder="All types"
                    />

                    <x-select
                        wire:model.live="clearFilters.event"
                        label="Event"
                        :options="$events->map(fn($event) => ['id' => $event, 'name' => ucfirst(str_replace('_', ' ', $event))])"
                        placeholder="All events"
                    />
                </div>

                {{-- Preview Count --}}
                <x-alert icon="o-exclamation-triangle" class="alert-warning">
                    <strong>{{ number_format($previewCount) }}</strong> activities will be deleted based on current filters.
                </x-alert>

                {{-- Confirmation Checkbox --}}
                <x-checkbox
                    wire:model="confirmDelete"
                    label="I understand this action cannot be undone"
                    hint="Please confirm deletion"
                />

                {{-- Action Buttons --}}
                <div class="flex justify-between gap-2 mt-6">
                    <div class="flex gap-2">
                        <x-button
                            wire:click="clearActivities"
                            label="Clear Filtered Activities"
                            icon="o-trash"
                            class="btn-error"
                            :disabled="!$confirmDelete || $previewCount === 0"
                        />
                        <x-button
                            wire:click="clearAllActivities"
                            label="Clear All Activities"
                            icon="o-trash"
                            class="btn-error btn-outline"
                            :disabled="!$confirmDelete"
                        />
                    </div>
                    <x-button
                        wire:click="closeClearModal"
                        label="Cancel"
                        class="btn-ghost"
                    />
                </div>
            </div>
        @endif
    </x-modal>
</div>

@script
<script>
    // Helper methods would be better in the component, but for quick implementation:
    window.getEventColor = (event) => {
        const colors = {
            'created': 'success',
            'updated': 'info',
            'deleted': 'error',
            'login': 'primary',
            'logout': 'warning',
            'failed_login': 'error',
        };
        return colors[event] || 'base-300';
    };

    window.getEventIcon = (event) => {
        const icons = {
            'created': 'o-plus-circle',
            'updated': 'o-pencil-square',
            'deleted': 'o-trash',
            'login': 'o-arrow-right-on-rectangle',
            'logout': 'o-arrow-left-on-rectangle',
            'failed_login': 'o-exclamation-triangle',
        };
        return icons[event] || 'o-document-text';
    };
</script>
@endscript
