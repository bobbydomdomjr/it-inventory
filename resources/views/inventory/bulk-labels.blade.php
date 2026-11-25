@extends('layouts.app')
@section('title','Bulk QR Label Printing')
@section('breadcrumb','Bulk QR Label Printing')
@section('content')
<div class="page-heading no-print">
    <div>
        <div class="eyebrow">ASSET MANAGEMENT</div>
        <h1>Bulk QR Label Printing</h1>
        <p>{{ $assets->count() }} selected asset label(s) · A4 sheet · 50 × 30 mm per label</p>
    </div>
    <div class="heading-actions">
        <a href="{{ route('inventory.index','assets') }}" class="btn btn-light border">Back to assets</a>
        <button type="button" class="btn btn-primary" id="printBulkLabels"><i class="bi bi-printer me-2"></i>Print A4 sheet</button>
    </div>
</div>
<div class="alert alert-info no-print small">
    Print using A4 paper, Portrait, Scale 100% / Actual size, and disable browser headers and footers. Dashed borders are cutting guides. Labels are arranged four across and nine down per sheet.
</div>

<div id="bulkLabelPrintArea">
@foreach($assets->chunk(36) as $sheet)
    <section class="label-sheet">
        <div class="label-grid">
        @foreach($sheet as $asset)
            <article class="asset-label" data-qr="{{ route('inventory.show',['resource'=>'assets','id'=>$asset->id]) }}" data-tag="{{ $asset->asset_tag }}">
                <div class="label-qr"></div>
                <div class="label-content">
                    <div class="label-kicker">IT ASSET</div>
                    <div class="label-tag">{{ $asset->asset_tag }}</div>
                    <div class="label-name">{{ $asset->name }}</div>
                    @if($asset->serial_number)
                        <div class="label-serial">S/N: {{ $asset->serial_number }}</div>
                    @endif
                    <div class="label-serial">{{ $asset->category?->name ?? 'IT Asset' }} · {{ $asset->location?->name ?? 'Unassigned' }}</div>
                    <div class="label-serial">Status: {{ str_replace('_',' ',$asset->status) }}</div>
                    <svg class="label-barcode"></svg>
                </div>
            </article>
        @endforeach
        </div>
    </section>
@endforeach
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = Array.from(document.querySelectorAll('.asset-label'));
    labels.forEach(function (label) {
        const qrTarget = label.querySelector('.label-qr');
        if (typeof QRCode !== 'undefined') {
            new QRCode(qrTarget, {
                text: label.dataset.qr,
                width: 160,
                height: 160,
                colorDark: '#17243a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.M
            });
        } else {
            qrTarget.textContent = 'QR unavailable';
            qrTarget.classList.add('qr-error');
        }

        const barcode = label.querySelector('.label-barcode');
        if (typeof JsBarcode !== 'undefined') {
            try {
                JsBarcode(barcode, label.dataset.tag, {
                    format: 'CODE128',
                    width: 1,
                    height: 22,
                    displayValue: false,
                    margin: 0,
                    lineColor: '#17243a'
                });
            } catch (error) {
                console.warn('Barcode generation failed for', label.dataset.tag, error);
                barcode.remove();
            }
        } else {
            barcode.remove();
        }
    });

    document.getElementById('printBulkLabels')?.addEventListener('click', function () {
        if (typeof QRCode === 'undefined') {
            alert('The QR code library did not load. Check internet/CDN access, then reload this page.');
            return;
        }
        window.print();
    });
});
</script>
@endpush

@push('styles')
<style>
#bulkLabelPrintArea { width:100%; }
.label-sheet {
    width:200mm;
    min-height:270mm;
    margin:12px auto;
    background:#fff;
    box-shadow:0 2px 18px rgba(20,35,60,.12);
    break-after:page;
    page-break-after:always;
}
.label-sheet:last-child { break-after:auto; page-break-after:auto; }
.label-grid {
    width:200mm;
    min-height:270mm;
    display:grid;
    grid-template-columns:repeat(4,50mm);
    grid-auto-rows:30mm;
    align-content:start;
}
.asset-label {
    width:50mm;
    height:30mm;
    box-sizing:border-box;
    border:.25mm dashed #8c96a5;
    display:flex;
    align-items:center;
    gap:1.4mm;
    padding:1.6mm;
    overflow:hidden;
    color:#17243a;
    background:#fff;
    font-family:Arial,Helvetica,sans-serif;
}
.label-qr {
    width:17mm;
    height:17mm;
    flex:0 0 17mm;
    display:flex;
    align-items:center;
    justify-content:center;
}
.label-qr img,.label-qr canvas {
    width:17mm !important;
    height:17mm !important;
    display:block;
}
.label-content {
    min-width:0;
    flex:1;
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    justify-content:center;
    line-height:1.12;
}
.label-kicker { font-size:5.2pt; font-weight:700; letter-spacing:.45px; color:#53627a; }
.label-tag { font-size:7.4pt; font-weight:800; margin-top:.6mm; overflow-wrap:anywhere; }
.label-name { font-size:6.2pt; font-weight:600; margin-top:.6mm; max-height:5.5mm; overflow:hidden; overflow-wrap:anywhere; }
.label-serial { font-size:5pt; margin-top:.5mm; overflow-wrap:anywhere; max-height:3mm; overflow:hidden; }
.label-barcode { display:block; width:100%; max-width:27mm; height:5mm; margin-top:.7mm; }
.qr-error { font-size:5pt; color:#b42318; text-align:center; }

@page { size:A4 portrait; margin:13.5mm 5mm; }
@media print {
    html,body { width:210mm !important; margin:0 !important; padding:0 !important; background:#fff !important; }
    body * { visibility:hidden !important; }
    #bulkLabelPrintArea,#bulkLabelPrintArea * { visibility:visible !important; }
    #bulkLabelPrintArea {
        position:absolute !important;
        left:0 !important;
        top:0 !important;
        width:200mm !important;
        margin:0 !important;
        padding:0 !important;
    }
    .label-sheet {
        width:200mm !important;
        min-height:270mm !important;
        margin:0 !important;
        box-shadow:none !important;
        break-after:page !important;
        page-break-after:always !important;
    }
    .label-sheet:last-child { break-after:auto !important; page-break-after:auto !important; }
    .label-grid { width:200mm !important; min-height:270mm !important; }
    .asset-label { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
}
</style>
@endpush
