<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Store Issue Voucher - {{ $requisition->requisition_number }}</title>
    <style>
        @page {
            margin: 0;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 0;
            background: #fff;
            font-size: 11px;
        }

        .container {
            padding: 35px 45px;
        }

        .top-accent {
            height: 6px;
            background: #4f46e5;
            width: 100%;
        }

        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 15px;
        }

        .header-table {
            width: 100%;
        }

        .uni-info h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }

        .uni-info p {
            margin: 0;
            color: #64748b;
            font-size: 10px;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title h2 {
            font-size: 16px;
            font-weight: 800;
            color: #4f46e5;
            margin: 0;
            text-transform: uppercase;
        }

        .doc-title p {
            font-size: 11px;
            color: #475569;
            font-family: monospace;
            margin: 2px 0 0 0;
        }

        .details-grid {
            width: 100%;
            margin-bottom: 25px;
        }

        .details-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
        }

        .details-box table {
            width: 100%;
        }

        .details-box td {
            padding: 3px 0;
            font-size: 10.5px;
        }

        .label {
            color: #64748b;
            font-weight: 600;
            width: 35%;
        }

        .value {
            color: #0f172a;
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .badge-issued {
            background: #dcfce7;
            color: #15803d;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 8px 12px;
            font-size: 10px;
            text-transform: uppercase;
            border-bottom: 2px solid #cbd5e1;
        }

        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10.5px;
        }

        .signatures {
            margin-top: 40px;
            width: 100%;
        }

        .sig-box {
            border-top: 1px dashed #94a3b8;
            padding-top: 5px;
            text-align: center;
            font-size: 10px;
            color: #475569;
            width: 30%;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }
    </style>
</head>

<body>
    <div class="top-accent"></div>
    <div class="container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="uni-info">
                        <h1>{{ config('app.name', 'UNIVERSITY PORTAL') }}</h1>
                        <p>Central Inventory & Supplies Store</p>
                        <p>Official Store Issue Voucher (SIV)</p>
                    </td>
                    <td class="doc-title">
                        <h2>STORE ISSUE VOUCHER</h2>
                        <p>#{{ $requisition->requisition_number }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="details-grid">
            <table style="width:100%;">
                <tr>
                    <td style="width: 49%; vertical-align: top;">
                        <div class="details-box">
                            <table>
                                <tr>
                                    <td class="label">Requisitioner:</td>
                                    <td class="value">{{ $requisition->user?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Email:</td>
                                    <td class="value">{{ $requisition->user?->email ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Department:</td>
                                    <td class="value">{{ $requisition->department?->name ?? 'General Store' }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td style="width: 2%;"></td>
                    <td style="width: 49%; vertical-align: top;">
                        <div class="details-box">
                            <table>
                                <tr>
                                    <td class="label">Issued Date:</td>
                                    <td class="value">{{ $requisition->issued_at ? $requisition->issued_at->format('d M Y, h:i A') : 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Status:</td>
                                    <td class="value">
                                        <span class="badge badge-issued">{{ $requisition->status }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">Approved By:</td>
                                    <td class="value">{{ $requisition->approvedBy?->name ?? 'Store Manager' }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Item Description</th>
                    <th style="width: 20%;">Category</th>
                    <th style="width: 15%;">Unit</th>
                    <th style="width: 20%; text-align: right;">Qty Issued</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requisition->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->item?->name ?? 'Unknown Item' }}</strong>
                        @if($item->item?->sku)
                            <br><span style="font-family: monospace; font-size: 9px; color: #64748b;">SKU: {{ $item->item->sku }}</span>
                        @endif
                    </td>
                    <td>{{ $item->item?->category?->name ?? 'General' }}</td>
                    <td><span style="text-transform: capitalize;">{{ $item->unit_of_measure ?? $item->item?->unit_of_measure ?? 'Pieces' }}</span></td>
                    <td style="text-align: right; font-weight: 800; font-size: 12px; color: #0f172a;">
                        {{ $item->approved_quantity > 0 ? $item->approved_quantity : $item->requested_quantity }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($requisition->notes)
            <div style="background: #fffbe6; border: 1px solid #ffe58f; padding: 10px; border-radius: 4px; margin-bottom: 25px;">
                <strong>Requisition Notes / Remarks:</strong> {{ $requisition->notes }}
            </div>
        @endif

        <table class="signatures">
            <tr>
                <td class="sig-box">
                    <strong>Issued By (Store Officer)</strong><br><br>
                    Signature & Date
                </td>
                <td style="width: 5%;"></td>
                <td class="sig-box">
                    <strong>Received By (Recipient)</strong><br><br>
                    Signature & Date
                </td>
                <td style="width: 5%;"></td>
                <td class="sig-box">
                    <strong>Approved By (HOD/Auditor)</strong><br><br>
                    Signature & Date
                </td>
            </tr>
        </table>

        <div class="footer">
            Generated on {{ date('d M Y, h:i A') }} • University Central Store & Inventory Management • Confidential Document
        </div>
    </div>
</body>

</html>
