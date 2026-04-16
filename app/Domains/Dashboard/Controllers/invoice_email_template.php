<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
// dashboard/invoice_email_template.php
// Renders the HTML invoice used as the email body.
function invoice_email_html($project, $goods, $subtotal, $tax, $total, $invoice_id, $share_url, $payment_link = null, $qr_data_uri = null) {
    $payment_link = $payment_link ?: $share_url;
    ob_start();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goods Invoice - Ripal Design</title>
    <style>
        /* Base reset for email clients */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        @media screen and (max-width: 600px) {
            .email-container { width: 100% !important; margin: auto !important; }
            .content-padding { padding: 20px !important; }
            .mobile-stack { display: block !important; width: 100% !important; margin-bottom: 20px !important; text-align: left !important; box-sizing: border-box !important; }
            .hide-mobile { display: none !important; }
            .spacer-mobile { height: 20px !important; display: block !important; }
            .invoice-table th, .invoice-table td { padding: 12px 5px !important; font-size: 13px !important; }
            .total-section { width: 100% !important; }
            .note-section { width: 100% !important; margin-bottom: 30px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f5; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="650" class="email-container" style="margin-bottom: 10px;">
                    <tr>
                        <td style="padding: 0 20px 10px 20px;">
                            <p style="margin: 0; font-size: 11px; font-weight: 600; color: #9ca3af; letter-spacing: 1px; text-transform: uppercase;">
                                Dashboard / <span style="color: #b91c1c;">Invoice</span>
                            </p>
                        </td>
                    </tr>
                </table>

                <table border="0" cellpadding="0" cellspacing="0" width="650" class="email-container" style="background-color: #ffffff; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border-top: 3px solid #b91c1c;">
                    <tr>
                        <td class="content-padding" style="padding: 40px 40px 20px 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td class="mobile-stack" width="60%" valign="top">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td valign="middle" style="padding-right: 15px;">
                                                    <img src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/').'/assets/Content/Logo.png'); ?>" alt="RD Logo" width="45" style="display: block; border: 0;" onerror="this.src='https://placehold.co/90x90/b91c1c/ffffff?text=RD'">
                                                </td>
                                                <td valign="middle">
                                                    <h1 style="margin: 0 0 2px 0; color: #111827; font-size: 18px; font-weight: 700;">Ripal Design</h1>
                                                    <p style="margin: 0 0 2px 0; font-size: 10px; color: #6b7280; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">
                                                        Architects & Interior Design
                                                    </p>
                                                    <p style="margin: 0; font-size: 11px; color: #9ca3af;">
                                                        projects@ripaldesign.in | +91 98765 43210
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="mobile-stack" width="40%" valign="top" align="right">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fef2f2; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 15px 20px;">
                                                    <p style="margin: 0 0 5px 0; font-size: 10px; color: #d1d5db; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">Invoice Meta</p>
                                                    <h2 style="margin: 0 0 5px 0; color: #b91c1c; font-size: 16px; font-weight: 700;"><?php echo htmlspecialchars($invoice_id); ?></h2>
                                                    <p style="margin: 0 0 2px 0; font-size: 13px; color: #111827; font-weight: 500;"><?php echo date('F j, Y'); ?></p>
                                                    <p style="margin: 0; font-size: 12px; color: #9ca3af;">Project ID: <?php echo htmlspecialchars($project['id'] ?? ''); ?></p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="content-padding" style="padding: 20px 40px 30px 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td class="mobile-stack" width="48%" valign="top">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border: 1px solid #f3f4f6; border-radius: 4px; height: 160px;">
                                            <tr>
                                                <td style="padding: 20px;" valign="top">
                                                    <p style="margin: 0 0 15px 0; font-size: 10px; color: #9ca3af; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
                                                        <span style="color: #b91c1c; margin-right: 5px;">ðŸ‘¤</span> Bill To
                                                    </p>
                                                    <p style="margin: 0 0 4px 0; font-size: 15px; color: #111827; font-weight: 600;">
                                                        <?php echo htmlspecialchars($project['owner_name'] ?? 'Client'); ?>
                                                    </p>
                                                    <p style="margin: 0 0 10px 0; font-size: 13px; color: #6b7280;">
                                                        <?php echo htmlspecialchars($project['owner_contact'] ?? ''); ?>
                                                    </p>
                                                    <p style="margin: 0; font-size: 12px; color: #9ca3af; line-height: 1.5; padding-left: 15px; border-left: 2px solid #e5e7eb;">
                                                        <?php echo nl2br(htmlspecialchars($project['location'] ?? '')); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="hide-mobile" width="4%"></td>
                                    <td class="mobile-stack" width="48%" valign="top">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border: 1px solid #fef2f2; background-color: #fffafb; border-radius: 4px; height: 160px;">
                                            <tr>
                                                <td style="padding: 20px;" valign="top">
                                                    <p style="margin: 0 0 15px 0; font-size: 10px; color: #9ca3af; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
                                                        <span style="color: #b91c1c; margin-right: 5px;">ðŸ“‹</span> Project Summary
                                                    </p>
                                                    <p style="margin: 0 0 4px 0; font-size: 15px; color: #111827; font-weight: 600;">
                                                        <?php echo htmlspecialchars($project['name'] ?? ''); ?>
                                                    </p>
                                                    <p style="margin: 0 0 20px 0; font-size: 12px; color: #9ca3af;">
                                                        ID: <?php echo htmlspecialchars($project['id'] ?? ''); ?>
                                                    </p>
                                                    <p style="margin: 0 0 4px 0; font-size: 10px; color: #9ca3af; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
                                                        Estimated Invoice Total
                                                    </p>
                                                    <p style="margin: 0; font-size: 22px; color: #b91c1c; font-weight: 700;">
                                                        â‚¹ <?php echo number_format($total, 2); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="content-padding" style="padding: 10px 40px 20px 40px;">
                            <table class="invoice-table" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                                <tr>
                                    <th align="left" style="padding: 10px 5px; border-top: 2px solid #111827; border-bottom: 1px solid #111827; font-size: 10px; color: #9ca3af; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; width: 5%;">#</th>
                                    <th align="left" style="padding: 10px 5px; border-top: 2px solid #111827; border-bottom: 1px solid #111827; font-size: 10px; color: #9ca3af; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Item Details</th>
                                    <th align="center" style="padding: 10px 5px; border-top: 2px solid #111827; border-bottom: 1px solid #111827; font-size: 10px; color: #9ca3af; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; width: 15%;">Qty</th>
                                    <th align="right" style="padding: 10px 5px; border-top: 2px solid #111827; border-bottom: 1px solid #111827; font-size: 10px; color: #9ca3af; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; width: 20%;">Unit Price</th>
                                    <th align="right" style="padding: 10px 5px; border-top: 2px solid #111827; border-bottom: 1px solid #111827; font-size: 10px; color: #9ca3af; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; width: 20%;">Total</th>
                                </tr>

                                <?php if (empty($goods)): ?>
                                <tr>
                                    <td colspan="5" align="center" style="padding: 20px; font-size: 12px; color: #9ca3af;">No items</td>
                                </tr>
                                <?php else: ?>
                                    <?php $i = 1; foreach ($goods as $g): ?>
                                        <tr>
                                            <td align="left" valign="top" style="padding: 20px 5px; border-bottom: 1px solid #f3f4f6; font-size: 11px; color: #d1d5db; font-weight: 600;"><?php echo str_pad($i++,2,'0',STR_PAD_LEFT); ?></td>
                                            <td align="left" valign="top" style="padding: 20px 5px; border-bottom: 1px solid #f3f4f6;">
                                                <p style="margin: 0 0 2px 0; font-size: 14px; color: #111827; font-weight: 600;"><?php echo htmlspecialchars($g['name'] ?? ''); ?></p>
                                                <p style="margin: 0 0 6px 0; font-size: 9px; color: #b91c1c; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;"><?php echo htmlspecialchars($g['sku'] ?? ''); ?></p>
                                                <p style="margin: 0; font-size: 12px; color: #9ca3af;"><?php echo htmlspecialchars($g['description'] ?? ''); ?></p>
                                            </td>
                                            <td align="center" valign="middle" style="padding: 20px 5px; border-bottom: 1px solid #f3f4f6;">
                                                <p style="margin: 0; font-size: 13px; color: #111827; font-weight: 600;"><?php echo number_format($g['quantity'] ?? 0); ?></p>
                                                <p style="margin: 0; font-size: 10px; color: #9ca3af; text-transform: uppercase;"><?php echo htmlspecialchars($g['unit'] ?? 'PCS'); ?></p>
                                            </td>
                                            <td align="right" valign="middle" style="padding: 20px 5px; border-bottom: 1px solid #f3f4f6; font-size: 13px; color: #4b5563; font-weight: 500;">â‚¹ <?php echo number_format($g['unit_price'] ?? 0,2); ?></td>
                                            <td align="right" valign="middle" style="padding: 20px 5px; border-bottom: 1px solid #f3f4f6; font-size: 14px; color: #111827; font-weight: 700;">â‚¹ <?php echo number_format($g['total_price'] ?? 0,2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="content-padding" style="padding: 20px 40px 40px 40px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td class="mobile-stack note-section" width="50%" valign="bottom" style="padding-right: 20px;">
                                        <p style="margin: 0 0 5px 0; font-size: 10px; color: #111827; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">
                                            Procurement Note:
                                        </p>
                                        <p style="margin: 0; font-size: 11px; color: #9ca3af; font-style: italic; line-height: 1.5;">
                                            These goods are listed for procurement by the site supervisor/worker. 
                                            Prices indicated are estimates based on latest market rates and are 
                                            subject to change at the time of final vendor billing.
                                        </p>
                                    </td>
                                    <td class="mobile-stack total-section" width="50%" valign="bottom">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td align="left" style="padding: 8px 10px; font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Subtotal</td>
                                                <td align="right" style="padding: 8px 10px; font-size: 13px; color: #111827; font-weight: 700;">â‚¹ <?php echo number_format($subtotal,2); ?></td>
                                            </tr>
                                            <tr>
                                                <td align="left" style="padding: 8px 10px 20px 10px; font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Tax (18% GST)</td>
                                                <td align="right" style="padding: 8px 10px 20px 10px; font-size: 13px; color: #111827; font-weight: 700;">â‚¹ <?php echo number_format($tax,2); ?></td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="padding: 0;">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fef2f2; border-top: 2px solid #b91c1c;">
                                                        <tr>
                                                            <td align="left" style="padding: 15px 10px; font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Grand Total</td>
                                                            <td align="right" style="padding: 15px 10px; font-size: 18px; color: #b91c1c; font-weight: 700;">â‚¹ <?php echo number_format($total,2); ?></td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="content-padding" align="center" style="padding: 0 40px 40px 40px; border-top: 1px solid #f3f4f6;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 30px;">
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                            <tr>
                                                <td align="center" style="border-radius: 4px;" bgcolor="#b91c1c">
                                                    <a href="<?php echo htmlspecialchars($payment_link); ?>" target="_blank" style="display: inline-block; padding: 12px 24px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #ffffff; text-decoration: none; border-radius: 4px; font-weight: 600; letter-spacing: 0.5px;">View Online / Make Payment</a>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="margin-top:8px;font-size:13px;color:#6b7280;">
                                            If the button doesn't open on your device, view the invoice online: <a href="<?php echo htmlspecialchars($share_url); ?>" style="color:#b91c1c;text-decoration:underline;">Open invoice</a>
                                        </div>
                                        <?php if (!empty($qr_data_uri)): ?>
                                            <div style="margin-top:12px;text-align:center;">
                                                <img src="<?php echo htmlspecialchars($qr_data_uri); ?>" alt="UPI QR" width="160" height="160" style="display:block;margin:0 auto;border:0;" />
                                                <div style="font-size:13px;color:#6b7280;margin-top:8px;">Scan to pay â‚¹ <?php echo number_format($total,2); ?> using your UPI app</div>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>

                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #262626; margin-top: 40px;">
                    <tr>
                        <td align="center" style="padding: 40px 20px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="600" class="email-container">
                                <tr>
                                    <td class="mobile-stack" width="50%" valign="top" style="padding-bottom: 20px;">
                                        <h3 style="margin: 0 0 10px 0; color: #ffffff; font-size: 18px; font-weight: 600;">Ready to build something Iconic?</h3>
                                        <p style="margin: 0; font-size: 12px; color: #a3a3a3; line-height: 1.6; padding-right: 20px;">
                                            Whether it's a private residence or a large-scale government infrastructure project, Ripal Design brings the expertise to make it happen.
                                        </p>
                                    </td>
                                    <td class="mobile-stack" width="50%" valign="top">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border: 1px solid #404040; padding: 20px;">
                                            <tr>
                                                <td>
                                                    <p style="margin: 0 0 15px 0; font-size: 14px; color: #ffffff; font-weight: 500;">Contact To Us</p>
                                                    <p style="margin: 0 0 10px 0; font-size: 12px; color: #a3a3a3; line-height: 1.5;">
                                                        <span style="color: #b91c1c; font-size: 14px; margin-right: 5px;">ðŸ“</span> 
                                                        Ripal Design Rajkot<br>
                                                        <span style="padding-left: 20px; display: block;">538 Jasal Complex, Nanavati Chowk,<br>150 ft Ring Road, Rajkot, Gujarat</span>
                                                    </p>
                                                    <p style="margin: 0; font-size: 12px; color: #a3a3a3;">
                                                        <span style="color: #b91c1c; font-size: 14px; margin-right: 5px;">âœ‰ï¸</span> 
                                                        projects@ripaldesign.in
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="padding-top: 40px; border-top: 1px solid #404040; margin-top: 40px; text-align: left;">
                                        <p style="margin: 0; font-size: 10px; color: #737373; text-transform: uppercase; letter-spacing: 1px;">
                                            Â© <?php echo date('Y'); ?> RIPAL DESIGN. ALL RIGHTS RESERVED.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>
</html>
    <?php
    return ob_get_clean();
}
