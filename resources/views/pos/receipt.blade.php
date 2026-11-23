@extends('layouts.app')

@push('styles')
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-receipt, #printable-receipt * {
        visibility: visible;
    }
    #printable-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 80mm;
        margin: 0 auto;
        padding: 10px;
        box-shadow: none !important;
        border: none !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endpush

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <!-- Top Actions / Nav -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </span>
            <div>
                <h2 class="text-xl font-black text-slate-800">Transaksi Berhasil!</h2>
                <p class="text-xs text-slate-500">Struk digital telah dibuat dan tersimpan aman di sistem.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Tombol Kirim Struk ke WhatsApp Pelanggan -->
            <button type="button" onclick="sendReceiptToWhatsApp()" id="btn-send-wa-top"
                class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#20bd5a] text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition transform active:scale-95 cursor-pointer">
                <i class="fa-brands fa-whatsapp text-sm"></i>
                <span id="btn-send-wa-top-text">Kirim Struk ke WA</span>
            </button>

            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Struk
            </button>
            @if(auth()->user()->isCashier() || auth()->user()->isAdmin())
                <a href="{{ route('takeaway.index') }}"
                    class="inline-flex items-center gap-2 bg-[#c59b27] hover:bg-[#a87f1c] text-stone-950 text-xs font-extrabold px-4 py-2.5 rounded-xl shadow-md transition transform active:scale-95">
                    <svg class="w-4 h-4 text-stone-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>Pesanan Bawa Pulang Baru</span>
                </a>
            @else
                <a href="{{ route('pos.index') }}"
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/20 transition transform active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Transaksi Baru
                </a>
            @endif
        </div>
    </div>

    <!-- Digital Receipt Card -->
    <div id="printable-receipt" class="bg-white rounded-3xl border border-slate-200/90 shadow-xl p-8 font-sans max-w-md mx-auto">
        <!-- Store Header -->
        <div class="text-center pb-5 border-b border-dashed border-slate-300">
            <img src="{{ $storeLogo }}" alt="{{ $storeName }}" class="w-16 h-16 mx-auto object-contain mb-2 drop-shadow-sm" onerror="this.src='{{ asset('images/default-cake.png') }}'">
            <h3 class="font-extrabold text-lg text-slate-900 tracking-tight uppercase leading-tight">
                <span>{{ $storeNameLine1 }}</span>
                @if(!empty($storeNameLine2))
                    <span class="block text-sm font-bold text-slate-700 mt-0.5">{{ $storeNameLine2 }}</span>
                @endif
            </h3>
            <p class="text-xs text-slate-500 mt-1">{{ $storeAddress }}</p>
            <p class="text-xs font-semibold text-slate-700 mt-0.5">Telp / WA: {{ $storePhone }}</p>
            <p class="text-xs text-slate-400 mt-0.5">Struk Resmi Pembelian &bull; Jam Buka: {{ $storeHours }}</p>
        </div>

        <!-- Meta Info -->
        @php
            $isOpenBill = (bool) ($sale->open_bill_id && $sale->openBill);
            $receiptSales = $isOpenBill ? $sale->openBill->sales : collect([$sale]);
            $receiptItems = $isOpenBill ? $sale->openBill->sales->flatMap->items : $sale->items;
            $receiptTotal = $isOpenBill ? ($sale->openBill->total_amount > 0 ? $sale->openBill->total_amount : $receiptSales->sum('total_amount')) : $sale->total_amount;
            $receiptSubtotal = $isOpenBill ? $receiptSales->sum('subtotal') : $sale->subtotal;
            $receiptPayment = $sale->payment ?? $sale->openBill?->sales->pluck('payment')->filter()->first();
            $receiptCashier = $sale->openBill?->closer?->name ?? ($sale->openBill?->opener?->name ?? ($sale->confirmer?->name ?? ($sale->user?->name ?? 'Kasir Resto')));
        @endphp

        @if($isOpenBill)
            <div class="mb-3 px-3 py-2 rounded-xl bg-amber-50 border border-amber-200 text-center font-mono text-[11px] text-amber-900">
                <span class="font-bold uppercase tracking-wider block">Nota Tagihan Open Bill Meja</span>
                <span>Meja: <strong>{{ $sale->openBill->table_number }}</strong> &bull; {{ $receiptSales->count() }} Pesanan Tergabung</span>
            </div>
        @endif

        <div class="py-4 border-b border-dashed border-slate-300 text-xs space-y-1.5 font-mono">
            <div class="flex justify-between">
                <span class="text-slate-400">No. {{ $isOpenBill ? 'Bill / Sesi' : 'Transaksi' }}</span>
                <span class="font-bold text-slate-900">{{ $isOpenBill ? $sale->openBill->bill_number : $sale->transaction_number }}</span>
            </div>
            @if($isOpenBill)
                <div class="flex justify-between">
                    <span class="text-slate-400">Daftar Order</span>
                    <span class="text-slate-700 font-bold">{{ $receiptSales->pluck('transaction_number')->implode(', ') }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-slate-400">Tipe Pesanan / Meja</span>
                <span class="text-slate-700 font-bold">
                    @if($sale->table_number === 'TAKEAWAY')
                        <span class="text-purple-700 font-black">Bawa Pulang (Takeaway)</span>
                    @elseif($sale->table_number === 'ONLINE')
                        <span class="text-blue-700 font-black">Pesanan Online</span>
                    @elseif($sale->table_number === 'KIOSK')
                        <span class="text-indigo-700 font-black">Kiosk Mandiri</span>
                    @elseif($sale->table_number)
                        Meja {{ $sale->table_number }}
                    @else
                        Non-Meja
                    @endif
                </span>
            </div>
            @if($sale->customer_name || $sale->openBill?->customer_name)
                <div class="flex justify-between">
                    <span class="text-slate-400">Pelanggan</span>
                    <span class="text-slate-700 font-semibold">{{ $sale->customer_name ?: $sale->openBill->customer_name }}</span>
                </div>
            @endif
            @if($sale->customer_phone)
                <div class="flex justify-between">
                    <span class="text-slate-400">No. HP Pelanggan</span>
                    <span class="text-slate-700 font-semibold">{{ $sale->customer_phone }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-slate-400">Waktu</span>
                <span class="text-slate-700">{{ ($sale->openBill?->closed_at ?? $sale->transaction_date)->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Kasir</span>
                <span class="text-slate-700 font-semibold">{{ $receiptCashier }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Status</span>
                <span class="text-emerald-600 font-bold uppercase tracking-wider">Lunas</span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="py-4 border-b border-dashed border-slate-300">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-slate-400 text-left font-mono border-b border-slate-100 pb-2">
                        <th class="pb-1.5">Item</th>
                        <th class="pb-1.5 text-center">Qty</th>
                        <th class="pb-1.5 text-right">Harga</th>
                        <th class="pb-1.5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @foreach($receiptItems as $item)
                        <tr>
                            <td class="py-2 pr-1">
                                <div class="font-bold text-slate-900 leading-tight">{{ $item->product_name_snapshot }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item->sku_snapshot }}</div>
                            </td>
                            <td class="py-2 text-center text-slate-700">{{ $item->quantity }}</td>
                            <td class="py-2 text-right text-slate-600">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-2 text-right font-bold text-slate-900">{{ number_format($item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Totals -->
        <div class="py-4 border-b border-dashed border-slate-300 text-xs font-mono space-y-2">
            <div class="flex justify-between text-slate-600">
                <span>Subtotal Item</span>
                <span>Rp {{ number_format($receiptSubtotal, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between text-base font-black text-slate-900 pt-2 border-t border-slate-100">
                <span>TOTAL TAGIHAN</span>
                <span class="text-emerald-700">Rp {{ number_format($receiptTotal, 0, ',', '.') }}</span>
            </div>

            <div class="pt-2 border-t border-slate-100 space-y-1">
                <div class="flex justify-between text-slate-600">
                    <span>Metode Bayar</span>
                    <span class="font-bold uppercase">{{ $receiptPayment?->methodLabel() ?? 'TUNAI' }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Uang Diterima</span>
                    <span>Rp {{ number_format($receiptPayment->amount_tendered ?? $receiptTotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between font-bold text-slate-900">
                    <span>Uang Kembalian</span>
                    <span>Rp {{ number_format($receiptPayment->change_amount ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Footer Thank You Note -->
        <div class="text-center pt-6 space-y-1 text-xs text-slate-400">
            <p class="font-bold text-slate-700">Terima kasih atas kunjungan Anda!</p>
            <p class="text-[11px] font-semibold text-slate-600">Hubungi Toko: {{ $storePhone }}</p>
            <p class="text-[11px]">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa bukti struk ini.</p>
        </div>
    </div>

    <!-- WhatsApp Action Card (No Print) -->
    <div class="no-print bg-white rounded-3xl border border-emerald-200/90 shadow-md p-5 max-w-md mx-auto space-y-3">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                <i class="fa-brands fa-whatsapp text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-bold text-slate-800 text-sm">Kirim Struk Transaksi ke WhatsApp</h4>
                <p class="text-[11px] text-slate-500 mt-0.5 leading-snug">
                    @if($sale->customer_phone)
                        Tujuan: <strong class="text-emerald-700 font-mono">{{ $sale->customer_phone }}</strong> ({{ $sale->customer_name ?: 'Pelanggan' }})
                    @else
                        Nomor WhatsApp belum diisi pada keranjang. Klik tombol di bawah untuk memasukkan nomor tujuan pelanggan.
                    @endif
                </p>
            </div>
        </div>

        <div class="pt-2 flex items-center gap-2 border-t border-slate-100">
            <button type="button" onclick="sendReceiptToWhatsApp()" id="btn-send-wa-bottom"
                class="flex-1 inline-flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20bd5a] text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-sm transition transform active:scale-95 cursor-pointer">
                <i class="fa-brands fa-whatsapp text-base"></i>
                <span id="btn-send-wa-bottom-text">{{ $sale->customer_phone ? 'Kirim Foto Struk & Pesan ke WA' : 'Kirim via WhatsApp' }}</span>
            </button>
            <button type="button" onclick="openWaModal(true)" title="Sesuaikan Nomor / Pesan"
                class="px-3 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold cursor-pointer transition">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
        </div>
    </div>
</div>

<!-- Modal Input No. WA / Konfirmasi Pengiriman -->
<div id="modal-whatsapp-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-stone-900/70 backdrop-blur-xs p-4 overflow-y-auto no-print">
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-md w-full p-5 space-y-4 my-auto relative">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i class="fa-brands fa-whatsapp text-lg"></i>
                </span>
                <div>
                    <h3 class="font-bold text-sm text-slate-900">Kirim Struk ke WhatsApp</h3>
                    <p class="text-[11px] text-slate-500">Kirim foto struk dan rincian transaksi langsung ke pelanggan.</p>
                </div>
            </div>
            <button type="button" onclick="closeWaModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold p-1 leading-none">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Nomor WhatsApp Pelanggan</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 font-mono font-bold">+62 / 08</span>
                    <input type="tel" id="wa-input-phone" value="{{ $sale->customer_phone ?? '' }}" placeholder="08xxxxxxxxxx"
                        class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-20 pr-3 py-2 text-xs font-mono font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Nomor terisi otomatis dari keranjang. Dapat diubah jika diperlukan.</p>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Pesan Pengantar</label>
                <textarea id="wa-input-message" rows="6"
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-[11px] text-slate-700 font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500 leading-relaxed"></textarea>
            </div>

            <div id="wa-info-box" class="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-[11px] space-y-1">
                <p class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-emerald-600"></i>
                    <span>Informasi Pengiriman:</span>
                </p>
                <p class="text-emerald-800 leading-relaxed">
                    Sistem akan meng-capture struk ini menjadi foto resolusi tinggi dan membuka chat WhatsApp dengan pesan rincian belanja.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeWaModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                Tutup
            </button>
            <button type="button" onclick="executeSendWhatsApp()" id="btn-execute-send-wa"
                class="inline-flex items-center gap-2 bg-[#25D366] hover:bg-[#20bd5a] text-white text-xs font-bold px-5 py-2.5 rounded-xl shadow transition transform active:scale-95 cursor-pointer">
                <i class="fa-brands fa-whatsapp text-sm"></i>
                <span id="btn-execute-text">Buka WhatsApp & Kirim</span>
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
    const defaultCustomerName = @json($sale->customer_name ?: ($sale->openBill?->customer_name ?: 'Pelanggan'));
    const defaultCustomerPhone = @json($sale->customer_phone ?: '');
    const storeName = @json($storeName);
    const trxNumber = @json($isOpenBill ? $sale->openBill->bill_number : $sale->transaction_number);
    const trxDate = @json(($sale->openBill?->closed_at ?? $sale->transaction_date)->format('d/m/Y H:i:s'));
    const cashierName = @json($receiptCashier);
    const totalAmountFormatted = "{{ number_format($receiptTotal, 0, ',', '.') }}";
    const subtotalFormatted = "{{ number_format($receiptSubtotal, 0, ',', '.') }}";
    const paymentMethod = @json($receiptPayment?->methodLabel() ?? 'TUNAI');

    const orderItems = [
        @foreach($receiptItems as $it)
        {
            name: @json($it->product_name_snapshot),
            qty: @json($it->quantity),
            total: "{{ number_format($it->line_total, 0, ',', '.') }}"
        },
        @endforeach
    ];

    function buildDefaultWaMessage(custName) {
        let msg = `Halo *Kak ${custName || 'Pelanggan'}*!\n`;
        msg += `Terima kasih telah berbelanja di *${storeName}* 😊\n\n`;
        msg += `Berikut rincian struk transaksi Anda:\n`;
        msg += `🧾 No. Transaksi: *${trxNumber}*\n`;
        msg += `📅 Tanggal: *${trxDate}*\n`;
        msg += `👤 Kasir: ${cashierName}\n\n`;
        msg += `*Rincian Menu:*\n`;
        orderItems.forEach(item => {
            msg += `• ${item.name} (${item.qty}x) = Rp ${item.total}\n`;
        });
        msg += `\n*TOTAL: Rp ${totalAmountFormatted}* (Lunas - ${paymentMethod})\n\n`;
        msg += `(Foto struk resmi tercetak dilampirkan)\n`;
        msg += `Semoga harimu menyenangkan dan kami tunggu kedatangannya kembali! 🙏✨`;
        return msg;
    }

    function cleanWaNumber(phone) {
        if (!phone) return '';
        let cleaned = phone.toString().replace(/\D/g, '');
        if (cleaned.startsWith('0')) {
            cleaned = '62' + cleaned.substring(1);
        } else if (cleaned.startsWith('8')) {
            cleaned = '62' + cleaned;
        }
        return cleaned;
    }

    function openWaModal(force = false) {
        const msgTextarea = document.getElementById('wa-input-message');
        if (msgTextarea && !msgTextarea.value.trim()) {
            msgTextarea.value = buildDefaultWaMessage(defaultCustomerName);
        }
        document.getElementById('modal-whatsapp-dialog').classList.remove('hidden');
    }

    function closeWaModal() {
        document.getElementById('modal-whatsapp-dialog').classList.add('hidden');
    }

    function setButtonLoading(loading) {
        const btnTop = document.getElementById('btn-send-wa-top-text');
        const btnBottom = document.getElementById('btn-send-wa-bottom-text');
        const btnExec = document.getElementById('btn-execute-text');

        if (loading) {
            if (btnTop) btnTop.innerText = 'Menyiapkan Struk...';
            if (btnBottom) btnBottom.innerText = 'Menyiapkan Struk & WhatsApp...';
            if (btnExec) btnExec.innerText = 'Sedang Meng-capture Struk...';
        } else {
            if (btnTop) btnTop.innerText = 'Kirim Struk ke WA';
            if (btnBottom) btnBottom.innerText = defaultCustomerPhone ? 'Kirim Foto Struk & Pesan ke WA' : 'Kirim via WhatsApp';
            if (btnExec) btnExec.innerText = 'Buka WhatsApp & Kirim';
        }
    }

    function isMobileOrTabletDevice() {
        const ua = navigator.userAgent || navigator.vendor || window.opera || '';
        const isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 1);
        const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|Tablet/i;
        const isIPad = /Macintosh/i.test(ua) && navigator.maxTouchPoints > 2;
        return mobileRegex.test(ua) || isIPad || (isTouch && window.innerWidth <= 1024);
    }

    function showToastNotification(message, type = 'success') {
        let toastEl = document.getElementById('wa-floating-toast');
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.id = 'wa-floating-toast';
            toastEl.className = 'fixed bottom-6 right-6 z-50 max-w-sm px-4 py-3 rounded-2xl shadow-2xl border text-xs font-sans transition-all duration-300 transform translate-y-0 opacity-100 flex items-start gap-2.5';
            document.body.appendChild(toastEl);
        }

        if (type === 'success') {
            toastEl.className = 'fixed bottom-6 right-6 z-50 max-w-sm px-4 py-3 rounded-2xl shadow-2xl border text-xs font-sans transition-all duration-300 bg-emerald-950 text-white border-emerald-700/60 flex items-start gap-2.5';
            toastEl.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-400 text-base mt-0.5 shrink-0"></i><div class="leading-relaxed">${message}</div>`;
        } else if (type === 'info') {
            toastEl.className = 'fixed bottom-6 right-6 z-50 max-w-sm px-4 py-3 rounded-2xl shadow-2xl border text-xs font-sans transition-all duration-300 bg-stone-900 text-white border-stone-700 flex items-start gap-2.5';
            toastEl.innerHTML = `<i class="fa-solid fa-spinner fa-spin text-amber-400 text-base mt-0.5 shrink-0"></i><div class="leading-relaxed">${message}</div>`;
        } else {
            toastEl.className = 'fixed bottom-6 right-6 z-50 max-w-sm px-4 py-3 rounded-2xl shadow-2xl border text-xs font-sans transition-all duration-300 bg-rose-950 text-white border-rose-700/60 flex items-start gap-2.5';
            toastEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-rose-400 text-base mt-0.5 shrink-0"></i><div class="leading-relaxed">${message}</div>`;
        }

        toastEl.classList.remove('hidden');
        clearTimeout(window.__waToastTimer);
        window.__waToastTimer = setTimeout(() => {
            if (toastEl) toastEl.classList.add('hidden');
        }, 5500);
    }

    async function sendReceiptToWhatsApp() {
        const phoneInput = document.getElementById('wa-input-phone');
        const currentPhone = phoneInput ? phoneInput.value.trim() : defaultCustomerPhone;

        if (!currentPhone) {
            openWaModal();
            return;
        }

        await executeSendWhatsApp();
    }

    let isCapturing = false;

    /**
     * Konversi warna oklab/oklch dari Tailwind CSS v4 ke RGB standar
     * agar kompatibel dengan engine canvas browser tanpa error.
     */
    function sanitizeOklabColors(element) {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            const allEls = [element, ...element.querySelectorAll('*')];
            allEls.forEach(el => {
                const computed = window.getComputedStyle(el);
                ['color', 'backgroundColor', 'borderColor', 'outlineColor', 'fill', 'stroke'].forEach(prop => {
                    const val = computed[prop];
                    if (val && (val.includes('oklab') || val.includes('oklch') || val.includes('color('))) {
                        ctx.fillStyle = '#000000';
                        ctx.fillStyle = val;
                        el.style[prop] = ctx.fillStyle;
                    }
                });
            });
        } catch (e) {
            console.warn('Gagal men-sanitize warna oklab:', e);
        }
    }

    /**
     * Meng-capture elemen struk menjadi Blob gambar PNG beresolusi tinggi.
     * Menggunakan html-to-image (dukungan native oklab/Tailwind v4)
     * dengan fallback ke html2canvas yang sudah di-sanitize warnanya.
     */
    async function captureReceiptToBlob(receiptEl) {
        // Prioritas 1: Gunakan html-to-image (Mendukung native SVG foreignObject & warna oklab)
        if (window.htmlToImage && typeof window.htmlToImage.toBlob === 'function') {
            try {
                const blob = await window.htmlToImage.toBlob(receiptEl, {
                    pixelRatio: 2,
                    backgroundColor: '#ffffff',
                    cacheBust: true
                });
                if (blob) return blob;
            } catch (errHtmlToImage) {
                console.warn('html-to-image gagal, beralih ke html2canvas sanitized:', errHtmlToImage);
            }
        }

        // Prioritas 2: Gunakan html2canvas dengan onclone color sanitizer
        if (typeof html2canvas === 'function') {
            const canvas = await html2canvas(receiptEl, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                onclone: function(clonedDoc) {
                    const clonedReceipt = clonedDoc.getElementById('printable-receipt');
                    if (clonedReceipt) {
                        sanitizeOklabColors(clonedReceipt);
                    }
                }
            });
            return await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
        }

        throw new Error('Library pembuat gambar struk belum termuat. Periksa koneksi internet Anda.');
    }

    /**
     * Mengarahkan kasir langsung ke WhatsApp:
     * 1. Jika Desktop (PC / Laptop):
     *    - Coba cari & buka aplikasi WhatsApp Desktop terlebih dahulu (whatsapp://send?phone=...&text=...).
     *    - Jika aplikasi WhatsApp Desktop tidak ada / tidak merespons dalam 1200ms, langsung alihkan ke WhatsApp Web (https://web.whatsapp.com/send?phone=...&text=...).
     * 2. Jika HP atau Tablet:
     *    - Langsung cari & buka aplikasi WhatsApp yang terinstal di perangkat (whatsapp://send?phone=...&text=...).
     */
    function routeKasirToWhatsApp(cleanPhone, messageText) {
        const encodedText = encodeURIComponent(messageText);
        const waAppUri = `whatsapp://send?phone=${cleanPhone}&text=${encodedText}`;
        const waWebUrl = `https://web.whatsapp.com/send?phone=${cleanPhone}&text=${encodedText}`;
        const waUniversalUrl = `https://api.whatsapp.com/send?phone=${cleanPhone}&text=${encodedText}`;

        const isMobileOrTablet = isMobileOrTabletDevice();

        if (isMobileOrTablet) {
            // Perangkat HP atau Tablet:
            let appOpened = false;
            const onBlurOrHide = () => { appOpened = true; };
            window.addEventListener('blur', onBlurOrHide, { once: true });
            document.addEventListener('visibilitychange', onBlurOrHide, { once: true });

            // Langsung panggil aplikasi WhatsApp terinstal di HP/tablet
            window.location.href = waAppUri;

            // Fallback jika aplikasi belum terpasang di perangkat
            setTimeout(() => {
                window.removeEventListener('blur', onBlurOrHide);
                document.removeEventListener('visibilitychange', onBlurOrHide);
                if (!appOpened && !document.hidden) {
                    window.location.href = waUniversalUrl;
                }
            }, 1500);

        } else {
            // Perangkat Desktop (PC / Laptop):
            let desktopAppOpened = false;
            const onBlur = () => { desktopAppOpened = true; };
            const onVisibility = () => { if (document.hidden) desktopAppOpened = true; };

            window.addEventListener('blur', onBlur, { once: true });
            document.addEventListener('visibilitychange', onVisibility, { once: true });

            // 1. Coba luncurkan aplikasi WhatsApp Desktop melalui scheme URI
            const triggerLink = document.createElement('a');
            triggerLink.href = waAppUri;
            triggerLink.style.display = 'none';
            document.body.appendChild(triggerLink);
            triggerLink.click();
            setTimeout(() => {
                if (triggerLink.parentNode) {
                    triggerLink.parentNode.removeChild(triggerLink);
                }
            }, 1000);

            // 2. Pantau apakah WhatsApp Desktop terbuka (window kehilangan fokus/blur).
            // Jika dalam 1200ms tidak terbuka (aplikasi tidak terpasang di komputer),
            // sistem langsung mengalihkan kasir ke WhatsApp Web!
            setTimeout(() => {
                window.removeEventListener('blur', onBlur);
                document.removeEventListener('visibilitychange', onVisibility);

                if (!desktopAppOpened && !document.hidden) {
                    // WhatsApp Desktop tidak ada, buka WhatsApp Web di tab baru
                    const waWebTab = window.open(waWebUrl, '_blank');
                    if (!waWebTab) {
                        window.location.href = waWebUrl;
                    }
                }
            }, 1200);
        }
    }

    async function executeSendWhatsApp() {
        if (isCapturing) return;

        const phoneInput = document.getElementById('wa-input-phone');
        const rawPhone = phoneInput ? phoneInput.value.trim() : defaultCustomerPhone;
        const cleanPhone = cleanWaNumber(rawPhone);

        if (!cleanPhone || cleanPhone.length < 8) {
            alert('Silakan masukkan nomor WhatsApp pelanggan yang valid.');
            openWaModal();
            phoneInput?.focus();
            return;
        }

        const msgTextarea = document.getElementById('wa-input-message');
        const messageText = msgTextarea && msgTextarea.value.trim() ? msgTextarea.value : buildDefaultWaMessage(defaultCustomerName);

        // Jika modal sedang terbuka, segera tutup agar kasir langsung diarahkan tanpa menunggu
        closeWaModal();

        isCapturing = true;
        setButtonLoading(true);
        showToastNotification('Menyiapkan foto struk & mengarahkan ke WhatsApp...', 'info');

        try {
            const receiptEl = document.getElementById('printable-receipt');
            if (!receiptEl) {
                throw new Error('Elemen struk tidak ditemukan.');
            }

            // Capture struk secara aman tanpa error oklab
            const blob = await captureReceiptToBlob(receiptEl);
            if (!blob) {
                throw new Error('Gagal mengonversi struk menjadi file gambar.');
            }

            const fileName = `struk-${trxNumber}.png`;
            const file = new File([blob], fileName, { type: 'image/png' });

            // 1. Di Mobile / Tablet yang mendukung Web Share API file
            // Ini memungkinkan langsung memilih WhatsApp dan melampirkan foto struk + teks sekaligus
            if (isMobileOrTabletDevice() && navigator.canShare && navigator.canShare({ files: [file] })) {
                try {
                    await navigator.share({
                        files: [file],
                        title: `Struk Transaksi ${trxNumber}`,
                        text: messageText
                    });
                    showToastNotification('Struk berhasil dibagikan ke WhatsApp!', 'success');
                    return;
                } catch (shareErr) {
                    if (shareErr.name === 'AbortError') {
                        // User membatalkan sheet share
                        return;
                    }
                    console.warn('Web Share dialihkan ke aplikasi WhatsApp langsung:', shareErr);
                }
            }

            // 2. Salin gambar struk langsung ke clipboard (khususnya untuk desktop)
            let copied = false;
            if (navigator.clipboard && window.ClipboardItem) {
                try {
                    await navigator.clipboard.write([
                        new ClipboardItem({ 'image/png': blob })
                    ]);
                    copied = true;
                } catch (clipErr) {
                    console.warn('Clipboard write image gagal:', clipErr);
                }
            }

            // 3. Unduh file gambar sebagai cadangan
            try {
                const downloadUrl = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = fileName;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(() => URL.revokeObjectURL(downloadUrl), 2000);
            } catch(e) {}

            // 4. Langsung arahkan kasir ke WhatsApp sesuai perangkat (Desktop App -> Web, atau HP/Tablet App)
            routeKasirToWhatsApp(cleanPhone, messageText);

            if (copied) {
                showToastNotification('<strong>Membuka WhatsApp...</strong><br>✅ Foto struk telah <strong>otomatis disalin ke clipboard</strong>. Tekan <kbd class="px-1 py-0.5 bg-stone-800 rounded font-bold">Ctrl + V</kbd> di chat untuk menempelkan foto struk bersama pesan!', 'success');
            } else {
                showToastNotification('<strong>Membuka WhatsApp...</strong><br>Foto struk telah otomatis diunduh untuk dilampirkan.', 'success');
            }

        } catch (err) {
            alert('Pemberitahuan: ' + err.message);
            showToastNotification('Terjadi kendala: ' + err.message, 'error');
        } finally {
            setButtonLoading(false);
            isCapturing = false;
        }
    }
</script>
@endpush
