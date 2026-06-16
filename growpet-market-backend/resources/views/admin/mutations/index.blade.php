@extends('admin.layouts.app')

@section('title', 'Mutasi')

@section('content')
    <section class="panel" style="margin-bottom: 16px;">
        <h2>Tambah mutasi</h2>
        <form method="POST" action="{{ route('admin.mutations.store') }}" class="form-grid" style="margin-top: 14px;">
            @csrf
            <label>
                Nama mutasi
                <input name="name" required placeholder="Nightmare">
            </label>
            <label>
                Price modifier
                <input type="number" name="price_modifier" value="0" required>
                <span class="field-hint">Boleh 0. Harga final tetap diatur pada varian pet.</span>
            </label>
            <input type="hidden" name="active" value="0">
            <label class="check-row">
                <input type="checkbox" name="active" value="1" checked>
                Aktif
            </label>
            <div class="actions">
                <button class="button" type="submit">Simpan mutasi</button>
            </div>
        </form>
    </section>

    <section class="record-list">
        @forelse ($mutations as $mutation)
            <details class="record-card">
                <summary>
                    <div>
                        <div class="record-title">
                            <strong>{{ $mutation->name }}</strong>
                            <span class="badge">Modifier Rp {{ number_format($mutation->price_modifier, 0, ',', '.') }}</span>
                            <span class="badge {{ $mutation->active ? 'success' : 'muted' }}">{{ $mutation->active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <div class="record-meta">
                            <span>Klik kartu ini untuk edit nama atau modifier.</span>
                        </div>
                    </div>
                </summary>
                <div class="record-card__body">
                    <form method="POST" action="{{ route('admin.mutations.update', $mutation) }}" class="form-grid">
                        @csrf
                        @method('PUT')
                        <label>
                            Nama mutasi
                            <input name="name" value="{{ $mutation->name }}" required>
                        </label>
                        <label>
                            Modifier
                            <input type="number" name="price_modifier" value="{{ $mutation->price_modifier }}" required>
                        </label>
                        <input type="hidden" name="active" value="0">
                        <label class="check-row">
                            <input type="checkbox" name="active" value="1" @checked($mutation->active)>
                            Aktif
                        </label>
                        <div class="actions">
                            <button class="button small" type="submit">Simpan perubahan</button>
                            <button class="button small danger" type="submit" form="delete-mutation-{{ $mutation->id }}">Nonaktifkan</button>
                        </div>
                    </form>
                    <form id="delete-mutation-{{ $mutation->id }}" method="POST" action="{{ route('admin.mutations.destroy', $mutation) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </details>
        @empty
            <div class="empty-state">
                <h3>Belum ada mutasi</h3>
                <p>Buat mutasi pertama, lalu gunakan di menu Harga Pet.</p>
            </div>
        @endforelse
    </section>
@endsection
