@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-[#c59b27]">Inventori & Gudang</span>
                <span class="text-stone-300">•</span>
                <span class="text-xs font-medium text-[#786e60]">{{ $storeNameLine1 }}</span>
            </div>
            <h2 class="font-serif-classic text-2xl sm:text-3xl font-bold text-[#262017] tracking-tight mt-1">
                Inventori Bahan Baku Kafe
            </h2>
            <p class="text-xs text-[#786e60] mt-0.5">
                Kelola master bahan baku bar & dapur, pantau sisa stok fisik, peringatan batas minimum, dan kartu mutasi barang.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('warehouse.raw_materials.mutations') }}"
                class="inline-flex items-center gap-2 bg-white border border-[#d6cfc2] hover:bg-[#faf8f5] text-[#262017] text-xs font-bold px-3.5 py-2.5 rounded-xl shadow-2xs transition">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span>Buku Mutasi Stok</span>
            </a>

            <button type="button" onclick="document.getElementById('modal-add-material').classList.remove('hidden')"
                class="inline-flex items-center gap-2 bg-[#c59b27] hover:bg-[#a98119] text-stone-950 text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span>Tambah Bahan Baku</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold text-sm">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-300 text-rose-900 text-xs flex items-center justify-between shadow-2xs">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-700 hover:text-rose-900 font-bold text-sm">&times;</button>
        </div>
    @endif

    <!-- 4 Kartu Ringkasan KPI -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Item Bahan Baku -->
        <div class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-[#8f8573] uppercase tracking-wider block">Total Master Bahan</span>
                <span class="text-2xl font-black text-[#262017] mt-1 block">
                    {{ $summary['total_items'] }} <span class="text-xs font-normal text-stone-500">Item</span>
                </span>
                <p class="text-[11px] text-[#8f8573] mt-0.5">Bahan baku bar, kopi & kitchen</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-[#faf7f0] border border-[#e5dfd3] flex items-center justify-center text-[#c59b27] shadow-2xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
        </div>

        <!-- 2. Nilai Persediaan Aset Bahan Baku (Rp) -->
        <div class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold text-[#8f8573] uppercase tracking-wider block">Nilai Persediaan Bahan</span>
                <span class="text-xl font-black text-amber-800 mt-1 block truncate">
                    Rp {{ number_format($summary['total_asset_value'], 0, ',', '.') }}
                </span>
                <p class="text-[11px] text-[#8f8573] mt-0.5">Total aset fisik di gudang/bar</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-800 shadow-2xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- 3. Peringatan Stok Menipis -->
        <a href="{{ route('warehouse.raw_materials.index', ['stock_status' => 'low']) }}"
            class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] flex items-center justify-between hover:border-amber-400 transition group">
            <div>
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider block flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Stok Menipis
                </span>
                <span class="text-2xl font-black text-amber-900 mt-1 block">
                    {{ $summary['low_stock_count'] }} <span class="text-xs font-normal text-stone-500">Item</span>
                </span>
                <p class="text-[11px] text-amber-700/80 mt-0.5">Segera lakukan pembelian ulang</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-300 flex items-center justify-center text-amber-700 shadow-2xs group-hover:scale-105 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </a>

        <!-- 4. Peringatan Stok Habis -->
        <a href="{{ route('warehouse.raw_materials.index', ['stock_status' => 'out']) }}"
            class="bg-white rounded-2xl border border-[#e5dfd3] p-5 shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] flex items-center justify-between hover:border-rose-400 transition group">
            <div>
                <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider block flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                    Stok Habis (0)
                </span>
                <span class="text-2xl font-black text-rose-900 mt-1 block">
                    {{ $summary['out_of_stock_count'] }} <span class="text-xs font-normal text-stone-500">Item</span>
                </span>
                <p class="text-[11px] text-rose-700/80 mt-0.5">Bahan tidak bisa dipakai operasional</p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-300 flex items-center justify-center text-rose-700 shadow-2xs group-hover:scale-105 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>
        </a>
    </div>

    <!-- Filter & Pencarian Bar -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] p-4 shadow-xs">
        <form action="{{ route('warehouse.raw_materials.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-5">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari kode (BHN-...), nama bahan baku, kategori..."
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3.5 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
            </div>

            <div class="sm:col-span-3">
                <select name="category" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    <option value="all">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3">
                <select name="stock_status" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    <option value="">Semua Kondisi Stok</option>
                    <option value="safe" {{ request('stock_status') === 'safe' ? 'selected' : '' }}>🟢 Stok Aman</option>
                    <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>🟡 Stok Menipis (&le; Batas Min)</option>
                    <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>🔴 Stok Habis (&le; 0)</option>
                </select>
            </div>

            <div class="sm:col-span-1 flex gap-1">
                <button type="submit" class="w-full bg-[#262017] hover:bg-[#1a1610] text-[#f7f5f0] text-xs font-bold py-2 rounded-xl transition flex items-center justify-center" title="Terapkan Filter">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
                @if(request()->hasAny(['search', 'category', 'stock_status']))
                    <a href="{{ route('warehouse.raw_materials.index') }}" class="px-2.5 py-2 bg-stone-100 hover:bg-stone-200 text-stone-700 text-xs font-bold rounded-xl transition flex items-center justify-center" title="Reset Filter">
                        &times;
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Data Master Bahan Baku -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-[#262017]">
                <thead class="bg-[#faf7f0] border-b border-[#e5dfd3] text-[#786e60] uppercase tracking-wider font-serif-classic text-[11px]">
                    <tr>
                        <th class="py-3 px-4 font-bold">Kode</th>
                        <th class="py-3 px-4 font-bold">Nama Bahan & Kategori</th>
                        <th class="py-3 px-4 font-bold text-center">Sisa Stok Fisik</th>
                        <th class="py-3 px-4 font-bold text-center">Batas Min.</th>
                        <th class="py-3 px-4 font-bold text-right">Harga Beli / Satuan</th>
                        <th class="py-3 px-4 font-bold text-right">Nilai Aset</th>
                        <th class="py-3 px-4 font-bold text-center">Status</th>
                        <th class="py-3 px-4 font-bold text-center">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0ece1]">
                    @forelse($rawMaterials as $item)
                        @php
                            $isOut = $item->isOutOfStock();
                            $isLow = $item->isLowStock();
                        @endphp
                        <tr class="hover:bg-[#fdfbf7] transition {{ $isOut ? 'bg-rose-50/40' : ($isLow ? 'bg-amber-50/30' : '') }}">
                            <!-- Kode Bahan -->
                            <td class="py-3.5 px-4 font-mono font-bold text-xs text-[#8c6b1c]">
                                {{ $item->code }}
                            </td>

                            <!-- Nama Bahan & Kategori -->
                            <td class="py-3.5 px-4">
                                <span class="font-bold text-sm text-[#262017] block leading-snug">{{ $item->name }}</span>
                                <div class="flex items-center gap-1.5 text-[10px] text-[#786e60] mt-0.5">
                                    <span class="inline-block px-1.5 py-0.2 bg-stone-100 rounded text-stone-700 font-medium">{{ $item->category }}</span>
                                    @if($item->notes)
                                        <span>&bull;</span>
                                        <span class="truncate max-w-xs text-stone-400 italic">{{ $item->notes }}</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Sisa Stok Fisik -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-black text-sm {{ $isOut ? 'text-rose-700' : ($isLow ? 'text-amber-800' : 'text-emerald-800') }}">
                                    {{ rtrim(rtrim(number_format($item->current_stock, 2, ',', '.'), '0'), ',') }}
                                </span>
                                <span class="text-[10px] text-stone-500 font-semibold block">{{ $item->unit }}</span>
                            </td>

                            <!-- Batas Minimum -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="font-bold text-stone-600">
                                    {{ rtrim(rtrim(number_format($item->min_stock, 2, ',', '.'), '0'), ',') }}
                                </span>
                                <span class="text-[10px] text-stone-400 block">{{ $item->unit }}</span>
                            </td>

                            <!-- Harga Beli / Satuan -->
                            <td class="py-3.5 px-4 text-right">
                                <span class="font-semibold text-stone-700">
                                    Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                </span>
                                <span class="text-[10px] text-stone-400 block">per {{ $item->unit }}</span>
                            </td>

                            <!-- Nilai Aset (Rp) -->
                            <td class="py-3.5 px-4 text-right font-black text-amber-900">
                                Rp {{ number_format($item->totalAssetValue(), 0, ',', '.') }}
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4 text-center">
                                @if($isOut)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"></span>
                                        Habis
                                    </span>
                                @elseif($isLow)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600 animate-pulse"></span>
                                        Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Aman
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi Cepat Modal -->
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Tombol Restock (+) -->
                                    <button type="button" onclick="openRestockModal('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ $item->unit }}', '{{ (float) $item->unit_price }}')"
                                        class="px-2 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 font-bold text-[11px] transition shadow-2xs"
                                        title="Restock Bahan Masuk (+)">
                                        + Restock
                                    </button>

                                    <!-- Tombol Pemakaian Bar (-) -->
                                    <button type="button" onclick="openUsageModal('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ $item->unit }}', '{{ (float) $item->current_stock }}')"
                                        class="px-2 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-800 font-bold text-[11px] transition shadow-2xs"
                                        title="Catat Pemakaian Bar / Rusak (-)">
                                        - Pakai
                                    </button>

                                    <!-- Tombol Opname Fisik -->
                                    <button type="button" onclick="openOpnameModal('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ $item->unit }}', '{{ (float) $item->current_stock }}')"
                                        class="p-1 rounded-lg bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-800 font-bold text-[11px] transition"
                                        title="Audit Stock Opname Fisik">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                    </button>

                                    <!-- Kartu Riwayat Mutasi Item -->
                                    <a href="{{ route('warehouse.raw_materials.history', $item->id) }}"
                                        class="p-1 rounded-lg bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold transition"
                                        title="Kartu Stok & Riwayat Mutasi">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </a>

                                    <!-- Edit Bahan -->
                                    <button type="button" onclick="openEditModal('{{ $item->id }}', '{{ $item->code }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->category) }}', '{{ $item->unit }}', '{{ (float) $item->min_stock }}', '{{ (float) $item->unit_price }}', '{{ addslashes($item->notes ?? '') }}')"
                                        class="p-1 rounded-lg text-stone-500 hover:text-stone-800 hover:bg-stone-100 transition"
                                        title="Edit Data Master">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>

                                    <!-- Hapus Bahan -->
                                    <form action="{{ route('warehouse.raw_materials.destroy', $item->id) }}" method="POST"
                                        data-confirm="Apakah Anda yakin ingin menghapus bahan baku [{{ $item->code }}] {{ $item->name }} beserta seluruh riwayat kartu stoknya?"
                                        data-confirm-title="Hapus Master Bahan Baku"
                                        data-confirm-btn="Ya, Hapus"
                                        data-confirm-type="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 rounded-lg text-rose-500 hover:text-rose-800 hover:bg-rose-50 transition" title="Hapus Bahan">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-[#8f8573]">
                                <svg class="w-12 h-12 mx-auto mb-2 text-stone-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p class="text-sm font-bold text-[#262017]">Belum Ada Data Bahan Baku</p>
                                <p class="text-xs text-[#8f8573] mt-0.5">Silakan klik tombol "Tambah Bahan Baku" di atas untuk mendaftarkan bahan baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($rawMaterials->hasPages())
            <div class="p-4 border-t border-[#e5dfd3] bg-[#faf7f0]">
                {{ $rawMaterials->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ================= MODALS SECTION ================= -->

<!-- 1. Modal Tambah Bahan Baku Baru -->
<div id="modal-add-material" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-[#e5dfd3] w-full max-w-lg p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-[#f0ebd9]">
            <div>
                <h3 class="font-serif-classic font-bold text-base text-[#262017]">Tambah Bahan Baku Baru</h3>
                <p class="text-[11px] text-[#786e60]">Daftarkan bahan baku cafe untuk operasional bar atau kitchen.</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-add-material').classList.add('hidden')" class="text-stone-400 hover:text-stone-600 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('warehouse.raw_materials.store') }}" method="POST" class="space-y-3.5">
            @csrf

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Kode Bahan</label>
                    <input type="text" name="code" placeholder="Auto BHN-..."
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-mono font-bold text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Nama Bahan Baku *</label>
                    <input type="text" name="name" required placeholder="Contoh: Biji Kopi Arabika House Blend"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Kategori Bahan *</label>
                    <input type="text" name="category" list="category-suggestions" required placeholder="Pilih / ketik kategori"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    <datalist id="category-suggestions">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Satuan Ukuran *</label>
                    <input type="text" name="unit" list="unit-suggestions" required placeholder="kg / liter / pcs / botol"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    <datalist id="unit-suggestions">
                        <option value="kg">
                        <option value="gram">
                        <option value="liter">
                        <option value="ml">
                        <option value="pcs">
                        <option value="botol">
                        <option value="kaleng">
                        <option value="pack">
                        <option value="dus">
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Saldo Awal</label>
                    <input type="number" step="any" min="0" name="current_stock" value="0"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Batas Min. Stok</label>
                    <input type="number" step="any" min="0" name="min_stock" value="5"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Harga Beli (Rp)</label>
                    <input type="text" name="unit_price" placeholder="Contoh: 120.000" oninput="formatRupiahSimple(this)"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Catatan / Keterangan (Opsional)</label>
                <textarea name="notes" rows="2" placeholder="Nama supplier langganan, merek produk, spesifikasi..."
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-1.5 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-[#f0ebd9]">
                <button type="button" onclick="document.getElementById('modal-add-material').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-[#5c5346] hover:bg-[#f0ebd9]">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#c59b27] hover:bg-[#a98119] text-stone-950 shadow-xs">
                    Simpan Bahan Baku
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Restock Cepat (+) -->
<div id="modal-restock" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-emerald-200 w-full max-w-md p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-[#f0ebd9]">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold">
                    +
                </div>
                <div>
                    <h3 class="font-serif-classic font-bold text-base text-[#262017]">Restock Bahan Masuk (+)</h3>
                    <p id="restock-material-title" class="text-xs font-bold text-emerald-800"></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-restock').classList.add('hidden')" class="text-stone-400 hover:text-stone-600 text-xl font-bold">&times;</button>
        </div>

        <form id="form-restock" method="POST" class="space-y-3.5">
            @csrf

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Jumlah Masuk *</label>
                    <div class="relative">
                        <input type="number" step="any" min="0.01" name="quantity" required placeholder="0.00"
                            class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-[#262017] focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        <span id="restock-unit-badge" class="absolute right-3 top-2 text-xs font-semibold text-stone-400"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Harga Beli Baru (Rp)</label>
                    <input type="text" name="unit_price" id="restock-price-input" placeholder="Perbarui harga" oninput="formatRupiahSimple(this)"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-[#262017] focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">No. Nota / Referensi (Opsional)</label>
                <input type="text" name="reference_number" placeholder="Contoh: NOTA-SUPPLIER-102"
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Catatan Keterangan</label>
                <textarea name="notes" rows="2" placeholder="Catatan pembelian bahan, supplier, dll..."
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-1.5 text-xs text-[#262017] focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-[#f0ebd9]">
                <button type="button" onclick="document.getElementById('modal-restock').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-[#5c5346] hover:bg-[#f0ebd9]">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs">
                    Simpan Stok Masuk
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Catat Pemakaian Bar / Rusak (-) -->
<div id="modal-usage" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-rose-200 w-full max-w-md p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-[#f0ebd9]">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center font-bold">
                    -
                </div>
                <div>
                    <h3 class="font-serif-classic font-bold text-base text-[#262017]">Catat Pemakaian / Rusak (-)</h3>
                    <p id="usage-material-title" class="text-xs font-bold text-rose-800"></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-usage').classList.add('hidden')" class="text-stone-400 hover:text-stone-600 text-xl font-bold">&times;</button>
        </div>

        <form id="form-usage" method="POST" class="space-y-3.5">
            @csrf

            <div class="p-3 bg-[#faf7f0] rounded-xl border border-[#e5dfd3] flex justify-between items-center text-xs">
                <span class="text-stone-500">Sisa Stok Fisik Saat Ini:</span>
                <span id="usage-current-stock" class="font-bold text-[#262017]"></span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Jumlah Keluar *</label>
                    <div class="relative">
                        <input type="number" step="any" min="0.01" name="quantity" id="usage-qty-input" required placeholder="0.00"
                            class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-rose-700 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        <span id="usage-unit-badge" class="absolute right-3 top-2 text-xs font-semibold text-stone-400"></span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Keperluan / Alasan *</label>
                    <select name="usage_type" required class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-rose-500 focus:outline-none">
                        <option value="bar_usage" selected>Pemakaian Bar (Minuman)</option>
                        <option value="kitchen_usage">Pemakaian Kitchen (Makanan/Kue)</option>
                        <option value="damaged_spill">Bahan Rusak / Tumpah (Waste)</option>
                        <option value="expired">Kadaluarsa / Basi</option>
                        <option value="other">Keperluan Lainnya</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Catatan Keterangan Tambahan</label>
                <textarea name="notes" rows="2" placeholder="Keterangan barista/chef, penyebab tumpah/rusak, dll..."
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-1.5 text-xs text-[#262017] focus:ring-2 focus:ring-rose-500 focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-[#f0ebd9]">
                <button type="button" onclick="document.getElementById('modal-usage').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-[#5c5346] hover:bg-[#f0ebd9]">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white shadow-xs">
                    Simpan Pemakaian
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Stock Opname Fisik -->
<div id="modal-opname" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-amber-300 w-full max-w-md p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-[#f0ebd9]">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-900 flex items-center justify-center font-bold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-serif-classic font-bold text-base text-[#262017]">Audit Stock Opname Fisik</h3>
                    <p id="opname-material-title" class="text-xs font-bold text-amber-800"></p>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('modal-opname').classList.add('hidden')" class="text-stone-400 hover:text-stone-600 text-xl font-bold">&times;</button>
        </div>

        <form id="form-opname" method="POST" class="space-y-3.5">
            @csrf

            <div class="p-3 bg-[#faf7f0] rounded-xl border border-[#e5dfd3] space-y-1 text-xs">
                <div class="flex justify-between">
                    <span class="text-stone-500">Stok Tercatat di Sistem:</span>
                    <span id="opname-system-stock" class="font-bold text-[#262017]"></span>
                </div>
                <div class="flex justify-between pt-1 border-t border-stone-200">
                    <span class="text-stone-500">Selisih Penyesuaian:</span>
                    <span id="opname-diff" class="font-black text-amber-800">0</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Hitungan Nyata Fisik di Bar / Gudang *</label>
                <div class="relative">
                    <input type="number" step="any" min="0" name="physical_stock" id="opname-physical-input" required placeholder="0.00"
                        oninput="calculateOpnameDiff()"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3.5 py-2.5 text-sm font-black text-[#262017] focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <span id="opname-unit-badge" class="absolute right-3.5 top-2.5 text-xs font-bold text-stone-500"></span>
                </div>
                <p class="text-[11px] text-stone-400 mt-1">Sistem otomatis mencatat selisih (+/-) ke kartu stok sebagai penyesuaian opname.</p>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Keterangan Hasil Audit (Opsional)</label>
                <textarea name="notes" rows="2" placeholder="Catatan audit fisik bulanan/mingguan, alasan selisih..."
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-1.5 text-xs text-[#262017] focus:ring-2 focus:ring-amber-500 focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-[#f0ebd9]">
                <button type="button" onclick="document.getElementById('modal-opname').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-[#5c5346] hover:bg-[#f0ebd9]">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#c59b27] hover:bg-[#a98119] text-stone-950 shadow-xs">
                    Simpan Penyesuaian Opname
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 5. Modal Edit Data Master Bahan Baku -->
<div id="modal-edit-material" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 backdrop-blur-xs p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl border border-[#e5dfd3] w-full max-w-lg p-6 space-y-4 my-8">
        <div class="flex items-center justify-between pb-3 border-b border-[#f0ebd9]">
            <div>
                <h3 class="font-serif-classic font-bold text-base text-[#262017]">Edit Data Master Bahan Baku</h3>
                <p class="text-[11px] text-[#786e60]">Perbarui informasi kode, kategori, batas minimum, dan harga beli.</p>
            </div>
            <button type="button" onclick="document.getElementById('modal-edit-material').classList.add('hidden')" class="text-stone-400 hover:text-stone-600 text-xl font-bold">&times;</button>
        </div>

        <form id="form-edit-material" method="POST" class="space-y-3.5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-1">
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Kode Bahan *</label>
                    <input type="text" name="code" id="edit-code" required
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-mono font-bold text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Nama Bahan Baku *</label>
                    <input type="text" name="name" id="edit-name" required
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Kategori Bahan *</label>
                    <input type="text" name="category" id="edit-category" list="category-suggestions" required
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Satuan Ukuran *</label>
                    <input type="text" name="unit" id="edit-unit" required
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Batas Minimum Stok</label>
                    <input type="number" step="any" min="0" name="min_stock" id="edit-min-stock"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Harga Beli Satuan (Rp)</label>
                    <input type="text" name="unit_price" id="edit-unit-price" oninput="formatRupiahSimple(this)"
                        class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs font-bold text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#5c5346] mb-1 font-serif-classic">Catatan / Keterangan</label>
                <textarea name="notes" id="edit-notes" rows="2"
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-1.5 text-xs text-[#262017] focus:ring-2 focus:ring-[#c59b27] focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-[#f0ebd9]">
                <button type="button" onclick="document.getElementById('modal-edit-material').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-[#5c5346] hover:bg-[#f0ebd9]">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold bg-[#c59b27] hover:bg-[#a98119] text-stone-950 shadow-xs">
                    Perbarui Data
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentSystemStockForOpname = 0;
    let currentOpnameUnit = '';

    function formatRupiahSimple(input) {
        let clean = input.value.replace(/\D/g, '');
        if (clean === '') {
            input.value = '';
            return;
        }
        input.value = new Intl.NumberFormat('id-ID').format(clean);
    }

    function openRestockModal(id, name, unit, price) {
        document.getElementById('form-restock').action = "{{ url('warehouse/raw-materials') }}/" + id + "/restock";
        document.getElementById('restock-material-title').innerText = name;
        document.getElementById('restock-unit-badge').innerText = unit;
        if (price > 0) {
            document.getElementById('restock-price-input').value = new Intl.NumberFormat('id-ID').format(price);
        } else {
            document.getElementById('restock-price-input').value = '';
        }
        document.getElementById('modal-restock').classList.remove('hidden');
    }

    function openUsageModal(id, name, unit, currentStock) {
        document.getElementById('form-usage').action = "{{ url('warehouse/raw-materials') }}/" + id + "/usage";
        document.getElementById('usage-material-title').innerText = name;
        document.getElementById('usage-unit-badge').innerText = unit;
        document.getElementById('usage-current-stock').innerText = currentStock + ' ' + unit;
        document.getElementById('usage-qty-input').max = currentStock;
        document.getElementById('modal-usage').classList.remove('hidden');
    }

    function openOpnameModal(id, name, unit, currentStock) {
        currentSystemStockForOpname = parseFloat(currentStock) || 0;
        currentOpnameUnit = unit;
        document.getElementById('form-opname').action = "{{ url('warehouse/raw-materials') }}/" + id + "/opname";
        document.getElementById('opname-material-title').innerText = name;
        document.getElementById('opname-unit-badge').innerText = unit;
        document.getElementById('opname-system-stock').innerText = currentStock + ' ' + unit;
        document.getElementById('opname-physical-input').value = currentStock;
        calculateOpnameDiff();
        document.getElementById('modal-opname').classList.remove('hidden');
    }

    function calculateOpnameDiff() {
        let inputVal = parseFloat(document.getElementById('opname-physical-input').value) || 0;
        let diff = inputVal - currentSystemStockForOpname;
        let diffEl = document.getElementById('opname-diff');
        let sign = diff >= 0 ? '+' : '';
        diffEl.innerText = sign + diff.toFixed(2) + ' ' + currentOpnameUnit;
        if (diff > 0) {
            diffEl.className = "font-black text-emerald-700";
        } else if (diff < 0) {
            diffEl.className = "font-black text-rose-700";
        } else {
            diffEl.className = "font-black text-stone-500";
        }
    }

    function openEditModal(id, code, name, category, unit, minStock, unitPrice, notes) {
        document.getElementById('form-edit-material').action = "{{ url('warehouse/raw-materials') }}/" + id;
        document.getElementById('edit-code').value = code;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-category').value = category;
        document.getElementById('edit-unit').value = unit;
        document.getElementById('edit-min-stock').value = minStock;
        document.getElementById('edit-unit-price').value = unitPrice > 0 ? new Intl.NumberFormat('id-ID').format(unitPrice) : '';
        document.getElementById('edit-notes').value = notes;
        document.getElementById('modal-edit-material').classList.remove('hidden');
    }
</script>
@endsection
