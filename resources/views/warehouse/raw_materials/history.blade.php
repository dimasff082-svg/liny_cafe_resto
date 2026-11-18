@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('warehouse.raw_materials.index') }}" class="text-xs font-bold uppercase tracking-wider text-[#c59b27] hover:underline">
                    &larr; Kembali ke Katalog
                </a>
                <span class="text-stone-300">•</span>
                <span class="font-mono text-xs font-bold text-[#8c6b1c]">{{ $rawMaterial->code }}</span>
            </div>
            <h2 class="font-serif-classic text-2xl sm:text-3xl font-bold text-[#262017] tracking-tight mt-1">
                Kartu Stok: {{ $rawMaterial->name }}
            </h2>
            <p class="text-xs text-[#786e60] mt-0.5">
                Kategori: <strong class="text-stone-800">{{ $rawMaterial->category }}</strong> &bull; Satuan: <strong class="text-stone-800">{{ $rawMaterial->unit }}</strong> &bull; Batas Min: <strong class="text-stone-800">{{ rtrim(rtrim(number_format($rawMaterial->min_stock, 2, ',', '.'), '0'), ',') }} {{ $rawMaterial->unit }}</strong>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Tombol Restock -->
            <button type="button" onclick="document.getElementById('modal-restock-history').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                <span>Restock Masuk (+)</span>
            </button>

            <!-- Tombol Pemakaian -->
            <button type="button" onclick="document.getElementById('modal-usage-history').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 12H6"/></svg>
                <span>Catat Pakai (-)</span>
            </button>

            <!-- Tombol Opname -->
            <button type="button" onclick="document.getElementById('modal-opname-history').classList.remove('hidden')"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-[#c59b27] hover:bg-[#a98119] text-stone-950 font-bold text-xs shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span>Opname Fisik</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs flex items-center justify-between shadow-2xs">
            <span class="font-semibold">{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold text-sm">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 text-xs flex items-center justify-between shadow-2xs">
            <span class="font-semibold">{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900 font-bold text-sm">&times;</button>
        </div>
    @endif

    <!-- 4 Kartu Metrik Khusus Bahan Ini -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Sisa Stok Fisik -->
        <div class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)]">
            <span class="text-[11px] font-bold text-[#8f8573] uppercase tracking-wider block">Sisa Stok Fisik Saat Ini</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black {{ $rawMaterial->isOutOfStock() ? 'text-rose-700' : ($rawMaterial->isLowStock() ? 'text-amber-800' : 'text-emerald-800') }}">
                    {{ rtrim(rtrim(number_format($rawMaterial->current_stock, 2, ',', '.'), '0'), ',') }}
                </span>
                <span class="text-xs font-bold text-stone-500">{{ $rawMaterial->unit }}</span>
            </div>
            <div class="mt-2">
                @if($rawMaterial->isOutOfStock())
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300">Stok Habis (0)</span>
                @elseif($rawMaterial->isLowStock())
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300">Stok Menipis</span>
                @else
                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300">Stok Aman</span>
                @endif
            </div>
        </div>

        <!-- Akumulasi Stok Masuk -->
        <div class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)]">
            <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block">+ Total Restock Masuk</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-emerald-700">
                    +{{ rtrim(rtrim(number_format($stats['total_in'], 2, ',', '.'), '0'), ',') }}
                </span>
                <span class="text-xs font-bold text-stone-500">{{ $rawMaterial->unit }}</span>
            </div>
            <p class="text-[11px] text-[#8f8573] mt-2">Akumulasi seluruh barang masuk</p>
        </div>

        <!-- Akumulasi Stok Keluar -->
        <div class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)]">
            <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider block">- Total Pemakaian Keluar</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-rose-700">
                    -{{ rtrim(rtrim(number_format($stats['total_out'], 2, ',', '.'), '0'), ',') }}
                </span>
                <span class="text-xs font-bold text-stone-500">{{ $rawMaterial->unit }}</span>
            </div>
            <p class="text-[11px] text-[#8f8573] mt-2">Bar, dapur & waste/tumpah</p>
        </div>

        <!-- Nilai Persediaan Aset Item Ini -->
        <div class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)]">
            <span class="text-[11px] font-bold text-amber-900 uppercase tracking-wider block">Nilai Persediaan Aset</span>
            <span class="text-xl font-black text-amber-900 mt-1 block truncate">
                Rp {{ number_format($rawMaterial->totalAssetValue(), 0, ',', '.') }}
            </span>
            <p class="text-[11px] text-[#8f8573] mt-2">Harga Beli: Rp {{ number_format($rawMaterial->unit_price, 0, ',', '.') }} / {{ $rawMaterial->unit }}</p>
        </div>
    </div>

    <!-- Filters Bar History -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] p-4 shadow-xs">
        <form action="{{ route('warehouse.raw_materials.history', $rawMaterial->id) }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center text-xs">
            <div class="sm:col-span-4">
                <select name="mutation_type" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    <option value="all">Semua Jenis Mutasi</option>
                    <option value="in" {{ request('mutation_type') === 'in' ? 'selected' : '' }}>+ Masuk / Restock</option>
                    <option value="out" {{ request('mutation_type') === 'out' ? 'selected' : '' }}>- Keluar / Pemakaian</option>
                    <option value="adjustment" {{ request('mutation_type') === 'adjustment' ? 'selected' : '' }}>&plusmn; Opname Fisik</option>
                </select>
            </div>

            <div class="sm:col-span-6 flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-2.5 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none" title="Dari Tanggal">
                <span class="text-stone-400">-</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-2.5 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none" title="Sampai Tanggal">
            </div>

            <div class="sm:col-span-2 flex gap-1">
                <button type="submit" class="w-full bg-[#262017] hover:bg-[#1a1610] text-[#f7f5f0] text-xs font-bold py-2 rounded-xl transition flex items-center justify-center">
                    Filter
                </button>
                @if(request()->hasAny(['mutation_type', 'date_from', 'date_to']))
                    <a href="{{ route('warehouse.raw_materials.history', $rawMaterial->id) }}" class="px-2.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold rounded-xl transition flex items-center justify-center">
                        &times;
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Mutasi Bahan Ini -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#262017]">
                <thead class="bg-[#faf7f0] border-b border-[#e5dfd3] text-[#786e60] uppercase tracking-wider font-serif-classic text-[11px]">
                    <tr>
                        <th class="py-3 px-4 font-bold">Waktu & Tanggal</th>
                        <th class="py-3 px-4 font-bold text-center">Jenis Mutasi</th>
                        <th class="py-3 px-4 font-bold text-center">Jumlah (+/-)</th>
                        <th class="py-3 px-4 font-bold text-center">Stok Awal</th>
                        <th class="py-3 px-4 font-bold text-center">Stok Akhir</th>
                        <th class="py-3 px-4 font-bold">No. Ref & Keterangan</th>
                        <th class="py-3 px-4 font-bold">Pencatat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0ece1]">
                    @forelse($mutations as $mut)
                        @php
                            $isIn = ($mut->type ?? $mut->mutation_type) === 'in';
                            $isOut = ($mut->type ?? $mut->mutation_type) === 'out';
                        @endphp
                        <tr class="hover:bg-[#fdfbf7] transition">
                            <td class="py-3 px-4 font-mono text-[11px] text-stone-600 whitespace-nowrap">
                                <span class="font-bold text-[#262017] block">{{ $mut->created_at->translatedFormat('d M Y') }}</span>
                                <span class="text-[10px] text-stone-400">{{ $mut->created_at->format('H:i:s') }} WITA</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($isIn)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        Masuk / Restock
                                    </span>
                                @elseif($isOut)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-300">
                                        Keluar / Pakai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        Opname Fisik
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-black text-sm">
                                <span class="{{ $isIn ? 'text-emerald-700' : ($isOut ? 'text-rose-700' : 'text-amber-800') }}">
                                    {{ $mut->quantitySign() }}{{ rtrim(rtrim(number_format($mut->quantity, 2, ',', '.'), '0'), ',') }}
                                </span>
                                <span class="text-[10px] font-normal text-stone-400 block">{{ $rawMaterial->unit }}</span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-stone-500">
                                {{ rtrim(rtrim(number_format($mut->stock_before, 2, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-[#262017]">
                                {{ rtrim(rtrim(number_format($mut->stock_after, 2, ',', '.'), '0'), ',') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($mut->reference_number)
                                    <span class="font-mono font-bold text-[10.5px] text-[#8c6b1c] block">{{ $mut->reference_number }}</span>
                                @endif
                                <span class="text-[11px] text-[#5c5346] block">{{ $mut->notes ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs font-semibold text-stone-700">
                                {{ $mut->user?->name ?? 'Sistem' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-[#8f8573]">
                                Belum ada riwayat mutasi untuk bahan baku ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mutations->hasPages())
            <div class="p-4 border-t border-[#e5dfd3] bg-[#faf7f0]">
                {{ $mutations->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Restock Quick -->
<div id="modal-restock-history" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl shadow-2xl border border-emerald-200 w-full max-w-md p-6 space-y-4">
        <h3 class="font-serif-classic font-bold text-base text-[#262017]">Restock: {{ $rawMaterial->name }}</h3>
        <form action="{{ route('warehouse.raw_materials.restock', $rawMaterial->id) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-stone-600 mb-1">Jumlah Masuk ({{ $rawMaterial->unit }}) *</label>
                <input type="number" step="any" min="0.01" name="quantity" required placeholder="0.00" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold">
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 mb-1">Keterangan / No. Nota</label>
                <input type="text" name="notes" placeholder="Restock masuk..." class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs">
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-stone-200">
                <button type="button" onclick="document.getElementById('modal-restock-history').classList.add('hidden')" class="px-3 py-1.5 text-xs font-semibold text-stone-600">Batal</button>
                <button type="submit" class="px-4 py-1.5 rounded-xl text-xs font-bold bg-emerald-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Usage Quick -->
<div id="modal-usage-history" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl shadow-2xl border border-rose-200 w-full max-w-md p-6 space-y-4">
        <h3 class="font-serif-classic font-bold text-base text-[#262017]">Catat Pemakaian: {{ $rawMaterial->name }}</h3>
        <form action="{{ route('warehouse.raw_materials.usage', $rawMaterial->id) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-stone-600 mb-1">Jumlah Keluar ({{ $rawMaterial->unit }}) *</label>
                <input type="number" step="any" min="0.01" max="{{ (float) $rawMaterial->current_stock }}" name="quantity" required placeholder="0.00" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-rose-700">
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 mb-1">Keperluan *</label>
                <select name="usage_type" required class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs">
                    <option value="bar_usage">Pemakaian Bar (Minuman)</option>
                    <option value="kitchen_usage">Pemakaian Kitchen (Makanan/Kue)</option>
                    <option value="damaged_spill">Bahan Rusak / Tumpah</option>
                    <option value="expired">Kadaluarsa</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-stone-200">
                <button type="button" onclick="document.getElementById('modal-usage-history').classList.add('hidden')" class="px-3 py-1.5 text-xs font-semibold text-stone-600">Batal</button>
                <button type="submit" class="px-4 py-1.5 rounded-xl text-xs font-bold bg-rose-600 text-white">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Opname Quick -->
<div id="modal-opname-history" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4">
    <div class="bg-white rounded-3xl shadow-2xl border border-amber-300 w-full max-w-md p-6 space-y-4">
        <h3 class="font-serif-classic font-bold text-base text-[#262017]">Stock Opname: {{ $rawMaterial->name }}</h3>
        <form action="{{ route('warehouse.raw_materials.opname', $rawMaterial->id) }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-stone-600 mb-1">Hitungan Nyata Fisik ({{ $rawMaterial->unit }}) *</label>
                <input type="number" step="any" min="0" name="physical_stock" value="{{ (float) $rawMaterial->current_stock }}" required class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold">
            </div>
            <div>
                <label class="block text-xs font-bold text-stone-600 mb-1">Catatan Audit</label>
                <textarea name="notes" rows="2" placeholder="Catatan hasil opname..." class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-1.5 text-xs"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-stone-200">
                <button type="button" onclick="document.getElementById('modal-opname-history').classList.add('hidden')" class="px-3 py-1.5 text-xs font-semibold text-stone-600">Batal</button>
                <button type="submit" class="px-4 py-1.5 rounded-xl text-xs font-bold bg-[#c59b27] text-stone-950">Simpan Opname</button>
            </div>
        </form>
    </div>
</div>
@endsection
