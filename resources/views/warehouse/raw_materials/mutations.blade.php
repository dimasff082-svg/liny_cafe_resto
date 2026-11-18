@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('warehouse.raw_materials.index') }}" class="text-xs font-bold uppercase tracking-wider text-[#c59b27] hover:underline">
                    &larr; Kembali ke Inventori
                </a>
                <span class="text-stone-300">•</span>
                <span class="text-xs font-medium text-[#786e60]">Log Kartu Stok</span>
            </div>
            <h2 class="font-serif-classic text-2xl sm:text-3xl font-bold text-[#262017] tracking-tight mt-1">
                Buku Riwayat Mutasi Stok
            </h2>
            <p class="text-xs text-[#786e60] mt-0.5">
                Log histori pergerakan stok keluar-masuk seluruh bahan baku kafe secara transparan.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('warehouse.raw_materials.index') }}"
                class="inline-flex items-center gap-2 bg-white border border-[#d6cfc2] hover:bg-[#faf8f5] text-[#262017] text-xs font-bold px-3.5 py-2.5 rounded-xl shadow-2xs transition">
                <svg class="w-4 h-4 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span>Lihat Katalog Bahan</span>
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] p-4 shadow-xs">
        <form action="{{ route('warehouse.raw_materials.mutations') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center text-xs">
            <!-- Search Text -->
            <div class="sm:col-span-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari referensi, nama bahan, catatan..."
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3.5 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
            </div>

            <!-- Pilih Bahan Baku -->
            <div class="sm:col-span-3">
                <select name="raw_material_id" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    <option value="all">Semua Bahan Baku</option>
                    @foreach($rawMaterials as $mat)
                        <option value="{{ $mat->id }}" {{ request('raw_material_id') == $mat->id ? 'selected' : '' }}>
                            [{{ $mat->code }}] {{ $mat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Jenis Mutasi -->
            <div class="sm:col-span-2">
                <select name="mutation_type" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    <option value="all">Semua Jenis Mutasi</option>
                    <option value="in" {{ request('mutation_type') === 'in' ? 'selected' : '' }}>+ Masuk / Restock</option>
                    <option value="out" {{ request('mutation_type') === 'out' ? 'selected' : '' }}>- Keluar / Pemakaian</option>
                    <option value="adjustment" {{ request('mutation_type') === 'adjustment' ? 'selected' : '' }}>&plusmn; Opname Fisik</option>
                </select>
            </div>

            <!-- Rentang Tanggal -->
            <div class="sm:col-span-3 flex items-center gap-1.5">
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-2.5 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none" title="Dari Tanggal">
                <span class="text-stone-400">-</span>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-2.5 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none" title="Sampai Tanggal">
            </div>

            <!-- Submit Filter -->
            <div class="sm:col-span-1 flex gap-1">
                <button type="submit" class="w-full bg-[#262017] hover:bg-[#1a1610] text-[#f7f5f0] text-xs font-bold py-2 rounded-xl transition flex items-center justify-center" title="Filter Mutasi">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                @if(request()->hasAny(['search', 'raw_material_id', 'mutation_type', 'date_from', 'date_to']))
                    <a href="{{ route('warehouse.raw_materials.mutations') }}" class="px-2 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold rounded-xl transition flex items-center justify-center" title="Reset Filter">
                        &times;
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Mutasi -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#262017]">
                <thead class="bg-[#faf7f0] border-b border-[#e5dfd3] text-[#786e60] uppercase tracking-wider font-serif-classic text-[11px]">
                    <tr>
                        <th class="py-3 px-4 font-bold">Waktu & Tanggal</th>
                        <th class="py-3 px-4 font-bold">Bahan Baku</th>
                        <th class="py-3 px-4 font-bold text-center">Jenis Mutasi</th>
                        <th class="py-3 px-4 font-bold text-center">Jumlah (+/-)</th>
                        <th class="py-3 px-4 font-bold text-center">Stok Awal</th>
                        <th class="py-3 px-4 font-bold text-center">Stok Akhir</th>
                        <th class="py-3 px-4 font-bold">No. Ref & Keterangan</th>
                        <th class="py-3 px-4 font-bold">Petugas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0ece1]">
                    @forelse($mutations as $mut)
                        @php
                            $mat = $mut->rawMaterial;
                            $unit = $mat?->unit ?? '';
                            $mutationType = $mut->type ?? $mut->mutation_type;
                            $isIn = $mutationType === 'in';
                            $isOut = $mutationType === 'out';
                            $isAdj = $mutationType === 'adjustment';
                        @endphp
                        <tr class="hover:bg-[#fdfbf7] transition">
                            <!-- Waktu & Tanggal -->
                            <td class="py-3 px-4 font-mono text-[11px] text-stone-600 whitespace-nowrap">
                                <span class="font-bold text-[#262017] block">{{ $mut->created_at->translatedFormat('d M Y') }}</span>
                                <span class="text-[10px] text-stone-400">{{ $mut->created_at->format('H:i:s') }} WITA</span>
                            </td>

                            <!-- Bahan Baku -->
                            <td class="py-3 px-4">
                                @if($mat)
                                    <a href="{{ route('warehouse.raw_materials.history', $mat->id) }}" class="font-bold text-sm text-[#262017] hover:text-[#c59b27] block">
                                        {{ $mat->name }}
                                    </a>
                                    <span class="font-mono text-[10px] font-semibold text-[#8c6b1c]">{{ $mat->code }}</span>
                                @else
                                    <span class="text-stone-400 italic">Bahan telah dihapus</span>
                                @endif
                            </td>

                            <!-- Jenis Mutasi Badge -->
                            <td class="py-3 px-4 text-center">
                                @if($isIn)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                        Masuk / Restock
                                    </span>
                                @elseif($isOut)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-900 border border-rose-300">
                                        <svg class="w-3 h-3 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                        Keluar / Pakai
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        Opname Fisik
                                    </span>
                                @endif
                            </td>

                            <!-- Jumlah Mutasi (+/-) -->
                            <td class="py-3 px-4 text-center font-mono font-black text-sm">
                                <span class="{{ $isIn ? 'text-emerald-700' : ($isOut ? 'text-rose-700' : 'text-amber-800') }}">
                                    {{ $mut->quantitySign() }}{{ rtrim(rtrim(number_format($mut->quantity, 2, ',', '.'), '0'), ',') }}
                                </span>
                                <span class="text-[10px] font-normal text-stone-500 block">{{ $unit }}</span>
                            </td>

                            <!-- Stok Sebelum -->
                            <td class="py-3 px-4 text-center font-mono text-stone-500">
                                {{ rtrim(rtrim(number_format($mut->stock_before, 2, ',', '.'), '0'), ',') }}
                                <span class="text-[10px] text-stone-400 block">{{ $unit }}</span>
                            </td>

                            <!-- Stok Sesudah -->
                            <td class="py-3 px-4 text-center font-mono font-bold text-[#262017]">
                                {{ rtrim(rtrim(number_format($mut->stock_after, 2, ',', '.'), '0'), ',') }}
                                <span class="text-[10px] text-stone-400 block">{{ $unit }}</span>
                            </td>

                            <!-- No. Ref & Keterangan -->
                            <td class="py-3 px-4">
                                @if($mut->reference_number)
                                    <span class="font-mono font-bold text-[10.5px] text-[#8c6b1c] block">{{ $mut->reference_number }}</span>
                                @endif
                                <span class="text-[11px] text-[#5c5346] block">{{ $mut->notes ?? '-' }}</span>
                            </td>

                            <!-- Petugas -->
                            <td class="py-3 px-4 text-xs font-semibold text-stone-700">
                                {{ $mut->user?->name ?? 'Sistem' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-[#8f8573]">
                                <svg class="w-10 h-10 mx-auto mb-2 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-sm font-bold text-[#262017]">Belum Ada Riwayat Mutasi Stok</p>
                                <p class="text-xs text-[#8f8573] mt-0.5">Seluruh aktivitas barang masuk, pemakaian bar, dan audit opname akan tercatat di sini.</p>
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
@endsection
