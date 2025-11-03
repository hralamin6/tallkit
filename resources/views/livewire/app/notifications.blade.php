<div>
    <x-header title="Notifications" subtitle="Manage your notifications and preferences" separator />

    {{-- Tab Navigation --}}
    <div class="mb-6">
        <div role="tablist" class="tabs tabs-boxed">
            <a
                role="tab"
                class="tab {{ $activeTab === 'center' ? 'tab-active' : '' }}"
                wire:click="$set('activeTab', 'center')"
            >
                <x-icon name="o-inbox" class="w-4 h-4 mr-2" />
                Notification Center
                @if($unreadCount > 0)
                    <x-badge value="{{ $unreadCount }}" class="badge-error badge-sm ml-2" />
                @endif
            </a>
            <a
                role="tab"
                class="tab {{ $activeTab === 'preferences' ? 'tab-active' : '' }}"
                wire:click="$set('activeTab', 'preferences')"
            >
                <x-icon name="o-cog-6-tooth" class="w-4 h-4 mr-2" />
                Preferences
            </a>
        </div>
    </div>

    {{-- Notification Center Tab --}}
    @if($activeTab === 'center')
        <div class="space-y-6">
            {{-- Actions --}}
            <x-card>
                <div class="flex items-center justify-between">
                    <div role="tablist" class="tabs tabs-boxed">
                        <a role="tab" class="tab {{ $selectedFilter === 'all' ? 'tab-active' : '' }}" wire:click="$set('selectedFilter', 'all')">
                            All
                        </a>
                        <a role="tab" class="tab {{ $selectedFilter === 'unread' ? 'tab-active' : '' }}" wire:click="$set('selectedFilter', 'unread')">
                            Unread
                            @if($unreadCount > 0)
                                <x-badge value="{{ $unreadCount }}" class="badge-primary ml-2" />
                            @endif
                        </a>
                        <a role="tab" class="tab {{ $selectedFilter === 'read' ? 'tab-active' : '' }}" wire:click="$set('selectedFilter', 'read')">
                            Read
                        </a>
                    </div>
                    <div class="flex gap-2">
                        <x-button label="Mark All Read" icon="o-check-circle" class="btn-primary btn-sm" wire:click="markAllAsRead" spinner />
                        <x-button label="Delete All" icon="o-trash" class="btn-error btn-sm" wire:click="deleteAll" wire:confirm="Delete all notifications?" spinner />
                    </div>
                </div>
            </x-card>

            {{-- Notifications List --}}
            <div class="space-y-3">
                @forelse($notifications as $notification)
                    <x-card class="{{ is_null($notification->read_at) ? 'bg-base-200' : '' }}">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                @php
                                    $data = $notification->data;
                                    $iconName = $data['icon'] ?? 'o-bell';
                                    $type = $data['type'] ?? 'info';
                                    $iconClass = match($type) {
                                        'success' => 'text-success',
                                        'error' => 'text-error',
                                        'warning' => 'text-warning',
                                        default => 'text-info',
                                    };
                                @endphp
                                <div class="p-3 rounded-full bg-base-300">
                                    <x-icon :name="$iconName" class="w-6 h-6 {{ $iconClass }}" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <h3 class="font-semibold">{{ $data['title'] ?? 'Notification' }}</h3>
                                        <p class="text-sm text-base-content/70 mt-1">{{ $data['message'] ?? '' }}</p>
                                        <div class="flex items-center gap-4 mt-2">
                                            <span class="text-xs text-base-content/50">{{ $notification->created_at->diffForHumans() }}</span>
                                            @if(is_null($notification->read_at))
                                                <x-badge value="New" class="badge-primary badge-sm" />
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(is_null($notification->read_at))
                                            <x-button icon="o-check" class="btn-ghost btn-sm btn-circle" wire:click="markAsRead('{{ $notification->id }}')" tooltip="Mark as read" spinner />
                                        @endif
                                        <x-button icon="o-trash" class="btn-ghost btn-sm btn-circle text-error" wire:click="deleteNotification('{{ $notification->id }}')" wire:confirm="Delete this notification?" tooltip="Delete" spinner />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-card>
                @empty
                    <x-card>
                        <div class="text-center py-12">
                            <x-icon name="o-bell-slash" class="w-16 h-16 mx-auto text-base-content/30" />
                            <h3 class="mt-4 text-lg font-semibold text-base-content/70">No notifications</h3>
                            <p class="mt-2 text-sm text-base-content/50">You're all caught up!</p>
                        </div>
                    </x-card>
                @endforelse
            </div>

            @if($notifications->hasPages())
                <div class="mt-6">{{ $notifications->links() }}</div>
            @endif
        </div>
    @endif

    {{-- Preferences Tab --}}
    @if($activeTab === 'preferences')
        <div class="space-y-6">
            <x-card>
                <div class="flex items-center justify-between mb-6">
                    <div class="alert alert-info">
                        <x-icon name="o-information-circle" class="w-5 h-5" />
                        <div class="text-sm">
                            <strong>Push:</strong> Browser notifications •
                            <strong>Email:</strong> Email messages •
                            <strong>Database:</strong> In-app notifications
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <x-button label="Enable All" icon="o-check-circle" class="btn-success btn-sm" wire:click="enableAll" spinner />
                        <x-button label="Disable All" icon="o-x-circle" class="btn-error btn-sm" wire:click="disableAll" spinner />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-zebra">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-center">
                                    <div class="flex flex-col items-center">
                                        <x-icon name="o-bell" class="w-5 h-5" />
                                        <span class="text-xs">Push</span>
                                    </div>
                                </th>
                                <th class="text-center">
                                    <div class="flex flex-col items-center">
                                        <x-icon name="o-envelope" class="w-5 h-5" />
                                        <span class="text-xs">Email</span>
                                    </div>
                                </th>
                                <th class="text-center">
                                    <div class="flex flex-col items-center">
                                        <x-icon name="o-inbox" class="w-5 h-5" />
                                        <span class="text-xs">Database</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category => $details)
                                <tr>
                                    <td>
                                        <div>
                                            <div class="font-semibold">{{ $details['name'] }}</div>
                                            <div class="text-sm text-base-content/60">{{ $details['description'] }}</div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-checkbox wire:model="preferences.{{ $category }}.push_enabled" class="checkbox-primary" />
                                    </td>
                                    <td class="text-center">
                                        <x-checkbox wire:model="preferences.{{ $category }}.email_enabled" class="checkbox-secondary" />
                                    </td>
                                    <td class="text-center">
                                        <x-checkbox wire:model="preferences.{{ $category }}.database_enabled" class="checkbox-accent" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-6">
                    <x-button label="Save Preferences" icon="o-check" class="btn-primary" wire:click="savePreferences" spinner />
                </div>
            </x-card>
        </div>
    @endif
</div>
