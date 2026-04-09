@props([
    'users',
    'editRoute' => null,
    'destroyRoute' => null,
    'showRoute' => null,
    'actionMode' => 'manage',
    'emptyTitle' => 'Belum ada data',
    'emptyCopy' => '',
])

<section class="dme-section-stack">
    <div class="dme-table-wrap">
        <table class="dme-table">
            <thead>
                <tr>
                    <th scope="col" class="dme-table__center-column">Nama</th>
                    <th scope="col" class="dme-table__actions-column">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="dme-table__center-cell">
                            <strong>{{ $user->name }}</strong>
                        </td>
                        <td class="dme-table__actions-cell">
                            <div class="dme-action-row">
                                @if ($actionMode === 'info' && filled($showRoute ?? $editRoute))
                                    <a href="{{ route($showRoute ?? $editRoute, $user) }}" class="dme-button--secondary">Info</a>
                                @else
                                    <a href="{{ route($editRoute, $user) }}" class="dme-button--secondary">Ubah</a>

                                    <form method="POST" action="{{ route($destroyRoute, $user) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dme-button--danger">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">
                            <div class="dme-empty-state">
                                <h3 class="dme-empty-state__title">{{ $emptyTitle }}</h3>
                                @if ($emptyCopy !== '')
                                    <p class="dme-empty-state__copy">{{ $emptyCopy }}</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($users, 'links'))
        <div>
            {{ $users->links() }}
        </div>
    @endif
</section>
