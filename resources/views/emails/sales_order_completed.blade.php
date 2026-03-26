<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Order Completed</title>
</head>
<body style="margin:0; padding:0; background-color:#ADB5BD; font-family:Arial, Helvetica, sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
<tr>
<td align="center">

{{-- ====== MAIN CONTAINER ====== --}}
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);">

    {{-- ====== HEADER ====== --}}
    <tr>
        <td style="background:#343A40; padding:30px; text-align:center;">
            <img src="{{ asset('images/logo.png') }}" alt="Warehouse Logo" width="120" style="margin-bottom:10px;">
            <h1 style="color:#FFC107; margin:0; font-size:22px; letter-spacing:1px;">
                JOUNTEUX WAREHOUSE
            </h1>
        </td>
    </tr>

    {{-- ====== BODY ====== --}}
    <tr>
        <td style="padding:40px; color:#212529;">

            <h2 style="color:#FF6B35; margin-top:0;">
                Order Successfully Completed
            </h2>

            <p style="font-size:15px; line-height:1.6;">
                Hello <strong>{{ $salesOrder->customer->customer_name }}</strong>,
            </p>

            <p style="font-size:15px; line-height:1.6;">
                We are pleased to inform you that your order
                <strong style="color:#FF6B35;">{{ $salesOrder->so_number }}</strong>
                has been successfully processed and completed.
            </p>

            {{-- ====== ORDER SUMMARY ====== --}}
            <table width="100%" cellpadding="10" cellspacing="0" style="margin:24px 0; border-collapse:collapse; border:1px solid #DEE2E6;">
                <tr style="background:#F8F9FA;">
                    <td style="border-bottom:1px solid #DEE2E6; font-weight:bold; width:40%;">Order Number</td>
                    <td style="border-bottom:1px solid #DEE2E6;">{{ $salesOrder->so_number }}</td>
                </tr>
                <tr>
                    <td style="border-bottom:1px solid #DEE2E6; font-weight:bold;">Order Date</td>
                    <td style="border-bottom:1px solid #DEE2E6;">{{ $salesOrder->order_date->format('F d, Y') }}</td>
                </tr>
                @if($salesOrder->delivery_date)
                <tr style="background:#F8F9FA;">
                    <td style="border-bottom:1px solid #DEE2E6; font-weight:bold;">Delivery Date</td>
                    <td style="border-bottom:1px solid #DEE2E6;">{{ $salesOrder->delivery_date->format('F d, Y') }}</td>
                </tr>
                @endif
                <tr>
                    <td style="border-bottom:1px solid #DEE2E6; font-weight:bold;">Status</td>
                    <td style="border-bottom:1px solid #DEE2E6;">
                        <span style="background:#28A745; color:#ffffff; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:bold;">
                            COMPLETED
                        </span>
                    </td>
                </tr>
            </table>

            {{-- ====== ITEMS TABLE ====== --}}
            <h3 style="color:#343A40; margin-bottom:8px;">Order Items</h3>
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse; border:1px solid #DEE2E6; margin-bottom:16px;">
                <thead>
                    <tr style="background:#343A40; color:#ffffff;">
                        <th style="text-align:left; padding:10px; border:1px solid #495057;">Product</th>
                        <th style="text-align:center; padding:10px; border:1px solid #495057;">Qty</th>
                        <th style="text-align:right; padding:10px; border:1px solid #495057;">Unit Price</th>
                        <th style="text-align:right; padding:10px; border:1px solid #495057;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($salesOrder->items as $index => $item)
                    <tr style="background:{{ $index % 2 === 0 ? '#ffffff' : '#F8F9FA' }};">
                        <td style="padding:10px; border:1px solid #DEE2E6;">{{ $item->product->name ?? 'N/A' }}</td>
                        <td style="padding:10px; border:1px solid #DEE2E6; text-align:center;">{{ $item->quantity }}</td>
                        <td style="padding:10px; border:1px solid #DEE2E6; text-align:right;">&#8369;{{ number_format($item->unit_price, 2) }}</td>
                        <td style="padding:10px; border:1px solid #DEE2E6; text-align:right;">&#8369;{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#FFF3E0;">
                        <td colspan="3" style="padding:12px; border:1px solid #DEE2E6; text-align:right; font-weight:bold; font-size:15px;">
                            Total Amount
                        </td>
                        <td style="padding:12px; border:1px solid #DEE2E6; text-align:right; font-weight:bold; font-size:16px; color:#FF6B35;">
                            &#8369;{{ number_format($salesOrder->total_amount, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <p style="font-size:15px; line-height:1.6;">
                Thank you for trusting our warehouse solutions.
                We look forward to supporting your next project.
            </p>

            {{-- ====== CTA BUTTON ====== --}}
            <div style="text-align:center; margin-top:30px;">
                <a href="{{ config('app.url') }}"
                   style="background:#FF6B35;
                          color:#ffffff;
                          padding:14px 28px;
                          text-decoration:none;
                          border-radius:6px;
                          font-weight:bold;
                          font-size:14px;
                          display:inline-block;
                          letter-spacing:0.5px;">
                    Visit Our Warehouse Portal
                </a>
            </div>

        </td>
    </tr>

    {{-- ====== FOOTER ====== --}}
    <tr>
        <td style="background:#495057; padding:24px; text-align:center; color:#ADB5BD; font-size:12px; line-height:1.5;">
            &copy; {{ date('Y') }} Jounteux Construction Warehouse. All rights reserved.<br>
            This is an automated notification. Please do not reply to this email.
        </td>
    </tr>

</table>

</td>
</tr>
</table>

</body>
</html>
