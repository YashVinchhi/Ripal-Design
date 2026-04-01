<?php
// dashboard/invoice_pdf_template.php
// Provides invoice_pdf_html() helper to render printable HTML for PDF generation.
function invoice_pdf_html($project, $goods, $subtotal, $tax, $total, $invoice_id, $share_url) {
    ob_start();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Invoice <?php echo htmlspecialchars($invoice_id); ?></title>
        <style>
            body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #222; margin: 0; padding: 24px; }
            .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
            .brand { font-size: 18px; font-weight: 700; }
            .meta { text-align: right; font-size: 12px; }
            table { width: 100%; border-collapse: collapse; margin-top: 12px; }
            th, td { border: 1px solid #e6e6e6; padding: 8px; font-size: 12px; }
            th { background: #f9f9f9; text-align: left; }
            .totals { margin-top: 12px; width: 100%; }
            .totals td { border: none; padding: 6px; }
            .right { text-align: right; }
            .center { text-align: center; }
            .notes { margin-top: 18px; font-size: 11px; color: #555; }
        </style>
    </head>
    <body>
        <div class="header">
            <div>
                <div class="brand">Ripal Design</div>
                <div style="font-size:12px;color:#666">Architects & Interior Design</div>
            </div>
            <div class="meta">
                <div>Invoice: <?php echo htmlspecialchars($invoice_id); ?></div>
                <div>Date: <?php echo date('F j, Y'); ?></div>
                <div>Project: <?php echo htmlspecialchars($project['name'] ?? ''); ?></div>
            </div>
        </div>

        <div>
            <strong>Bill To:</strong><br>
            <?php echo htmlspecialchars($project['owner_name'] ?? 'Client'); ?><br>
            <?php echo htmlspecialchars($project['owner_contact'] ?? ''); ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:6%;">#</th>
                    <th>Item</th>
                    <th style="width:12%;" class="center">Qty</th>
                    <th style="width:18%;" class="right">Unit Price</th>
                    <th style="width:18%;" class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($goods)): ?>
                    <tr><td colspan="5" class="center">No items</td></tr>
                <?php else: $i=1; foreach($goods as $g): ?>
                    <tr>
                        <td class="center"><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($g['name'] ?? ''); ?><?php if (!empty($g['description'])) echo '<div style="color:#666;font-size:11px;margin-top:4px">'.htmlspecialchars($g['description']).'</div>'; ?></td>
                        <td class="center"><?php echo intval($g['quantity']); ?> <?php echo htmlspecialchars($g['unit'] ?? ''); ?></td>
                        <td class="right">₹ <?php echo number_format($g['unit_price'],2); ?></td>
                        <td class="right">₹ <?php echo number_format($g['total_price'],2); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td></td>
                <td style="width:260px;">
                    <table style="width:100%;border:none;">
                        <tr><td class="right">Subtotal:</td><td class="right">₹ <?php echo number_format($subtotal,2); ?></td></tr>
                        <tr><td class="right">Tax (<?php echo (floatval(18)); ?>%):</td><td class="right">₹ <?php echo number_format($tax,2); ?></td></tr>
                        <tr style="font-weight:700"><td class="right">Grand Total:</td><td class="right">₹ <?php echo number_format($total,2); ?></td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="notes">
            View online: <?php echo htmlspecialchars($share_url); ?>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
