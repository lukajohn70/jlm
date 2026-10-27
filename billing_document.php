<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';
require_once __DIR__ . '/includes/functions.php';

jlm_ensure_business_tools_schema($pdo);

$type = trim((string) ($_GET['type'] ?? 'invoice'));
$token = trim((string) ($_GET['token'] ?? ''));
$toast = '';

$invoice = null;
$payment = null;

if ($type === 'receipt') {
    $stmt = $pdo->prepare("
        SELECT p.*
        FROM invoice_payments p
        WHERE p.public_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($payment) {
        $invoice = jlm_get_invoice_bundle($pdo, $payment['invoice_id']);
    }
} else {
    $stmt = $pdo->prepare('SELECT id FROM invoices WHERE public_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $invoiceId = (int) $stmt->fetchColumn();
    if ($invoiceId > 0) {
        $invoice = jlm_get_invoice_bundle($pdo, $invoiceId);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $type === 'invoice' && $invoice) {
    $action = trim((string) ($_POST['action'] ?? ''));
    try {
        if ($action === 'confirm_transfer') {
            $payerName = trim((string) ($_POST['payer_name'] ?? ''));
            $paymentReference = trim((string) ($_POST['payment_reference'] ?? ''));
            $amount = (float) ($_POST['payment_amount'] ?? $invoice['balance_due']);
            $notes = trim((string) ($_POST['payment_notes'] ?? ''));
            $receiptUploadPath = null;

            if ($payerName === '' || $paymentReference === '' || $amount <= 0) {
                throw new RuntimeException('Payer name, transfer reference, and amount are required.');
            }
            if ((float) ($invoice['balance_due'] ?? 0) <= 0) {
                throw new RuntimeException('This invoice has already been fully paid.');
            }

            $dupeStmt = $pdo->prepare('SELECT COUNT(*) FROM invoice_payments WHERE invoice_id = ? AND (payment_reference = ? OR gateway_reference = ?) AND status IN ("pending","verified")');
            $dupeStmt->execute([$invoice['id'], $paymentReference, $paymentReference]);
            if ((int) $dupeStmt->fetchColumn() > 0) {
                throw new RuntimeException('That transfer reference has already been submitted for this invoice.');
            }

            if (!empty($_FILES['receipt_file']) && is_array($_FILES['receipt_file']) && (int) ($_FILES['receipt_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $receiptUploadPath = jlm_upload_payment_receipt($_FILES['receipt_file']);
            }

            $paymentId = jlm_create_invoice_payment($pdo, [
                'invoice_id' => $invoice['id'],
                'receipt_number' => jlm_generate_unique_reference($pdo, 'invoice_payments', 'receipt_number', 'RCT'),
                'public_token' => jlm_business_random_token(40),
                'payer_name' => $payerName,
                'payment_date' => date('Y-m-d'),
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => 'Bank transfer',
                'payment_channel' => 'manual_transfer',
                'payment_reference' => $paymentReference,
                'gateway_reference' => $paymentReference,
                'gateway_status' => 'pending_verification',
                'notes' => $notes,
                'receipt_upload_path' => $receiptUploadPath,
                'created_by' => null,
            ]);

            $paymentStmt = $pdo->prepare('SELECT * FROM invoice_payments WHERE id = ? LIMIT 1');
            $paymentStmt->execute([$paymentId]);
            $pendingPayment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            jlm_send_admin_payment_notice($invoice, $pendingPayment, false);
            $toast = 'Transfer confirmation submitted. We will verify it and send your receipt once confirmed.';
            $invoice = jlm_get_invoice_bundle($pdo, $invoice['id']);
        }
    } catch (Throwable $e) {
        $toast = $e->getMessage();
    }
}

if (!$invoice) {
    http_response_code(404);
}

$profile = jlm_get_invoice_profile();
$documentTitle = $type === 'receipt' ? 'RECEIPT' : 'INVOICE';
$logoUrl = asset_url('assets/images/jlm.png');
$watermarkUrl = $logoUrl;
$contractValue = (float) ($invoice['total_contract_value'] ?? 0);
$remainingContractBalance = max($contractValue - (float) ($invoice['total_amount'] ?? 0), 0);
$verifiedPayments = $invoice['verified_payments'] ?? [];
$pendingPayments = $invoice['pending_payments'] ?? [];
$latestReceipt = !empty($verifiedPayments) ? $verifiedPayments[0] : null;
$invoiceTypeLabel = (($invoice['payment_scope'] ?? 'full') === 'part') ? 'Part Payment' : 'Full Payment';
$showPercentageColumn = (($invoice['payment_scope'] ?? 'full') === 'part');
$isPaidInFull = (float) ($invoice['balance_due'] ?? 0) <= 0.009;
$showPaymentInformation = $type !== 'receipt';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($documentTitle); ?> | JLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4;
            margin: 8mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
        }

        .sheet {
            box-sizing: border-box;
            width: 194mm;
            min-height: 279mm;
        }

        .doc-grid-2 {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        .doc-header-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 240px;
            gap: 24px;
            align-items: start;
        }

        .doc-header-right {
            justify-self: end;
            text-align: right;
            width: 240px;
        }

        .doc-grid-2-bottom {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 24px;
            align-items: end;
        }

        .print-color-exact,
        .print-color-exact * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .doc-heading {
            font-size: 18px;
            line-height: 1.2;
        }

        .doc-title {
            font-size: 22px;
            line-height: 1.05;
        }

        .doc-text {
            font-size: 16px;
            line-height: 1.35;
            word-break: normal;
        }

        .doc-small {
            font-size: 14px;
            line-height: 1.3;
        }

        @media print {
            .print-hide { display: none !important; }
            body { background: #fff !important; }
            .sheet {
                box-shadow: none !important;
                margin: 0 !important;
                width: 194mm !important;
                min-height: 279mm !important;
                border-radius: 0 !important;
            }

            .doc-header-grid {
                grid-template-columns: minmax(0, 1fr) 240px !important;
            }

            .doc-header-right {
                justify-self: end !important;
                text-align: right !important;
                width: 240px !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="print-hide max-w-6xl mx-auto px-4 pt-6">
        <div class="flex flex-wrap gap-3 justify-end">
            <button onclick="window.print()" class="px-4 py-3 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">Print / Save as PDF</button>
            <a href="billing_manage.php" class="px-4 py-3 rounded-xl bg-white text-gray-700 font-semibold hover:bg-gray-50 transition border border-gray-200">Back to Billing</a>
        </div>
    </div>

    <main class="sheet print-color-exact relative mx-auto my-6 bg-white rounded-[26px] shadow-2xl overflow-hidden">
        <div class="absolute inset-0 pointer-events-none opacity-[0.05] flex items-center justify-center">
            <img src="<?php echo htmlspecialchars($watermarkUrl); ?>" alt="" class="w-[55%] h-auto">
        </div>
        <div class="absolute top-0 left-0 h-5 w-full bg-[#2f33a8]"></div>
        <div class="absolute top-0 right-0 h-5 w-[38%] bg-[#e73072] [clip-path:polygon(12%_0,100%_0,100%_100%,0_100%)]"></div>
        <div class="absolute bottom-0 left-0 h-5 w-[38%] bg-[#e73072] [clip-path:polygon(0_0,100%_0,88%_100%,0_100%)]"></div>
        <div class="absolute bottom-0 right-0 h-5 w-full bg-[#2f33a8]"></div>

        <?php if (!$invoice): ?>
            <div class="p-10">
                <div class="rounded-2xl bg-red-50 border border-red-200 p-6">
                    <h1 class="text-2xl font-bold text-red-800">Document not found</h1>
                    <p class="text-red-700 mt-2">This billing document is invalid or unavailable.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="relative px-8 pt-10 pb-8">
                <?php if ($toast): ?>
                    <div class="print-hide mb-6 rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-4 text-emerald-800"><?php echo htmlspecialchars($toast); ?></div>
                <?php endif; ?>

                <section class="doc-header-grid">
                    <div class="flex items-start gap-4">
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="JLM Logo" class="w-20 h-20 object-contain shrink-0">
                        <div class="pt-2">
                            <div class="doc-heading text-[#2f33a8] font-extrabold uppercase"><?php echo nl2br(htmlspecialchars(str_replace(' ', "\n", $profile['business_name']))); ?></div>
                        </div>
                    </div>
                    <div class="doc-header-right text-[#2f33a8] pt-2">
                        <div class="doc-text"><?php echo htmlspecialchars($profile['contact_phone']); ?></div>
                        <div class="doc-text"><?php echo htmlspecialchars($profile['contact_email']); ?></div>
                        <div class="doc-text"><?php echo htmlspecialchars($profile['contact_website']); ?></div>
                        <div class="mt-3 doc-text"><?php echo htmlspecialchars(date('F j, Y', strtotime($type === 'receipt' ? ($payment['payment_date'] ?? $invoice['issue_date']) : $invoice['issue_date']))); ?></div>
                    </div>
                </section>

                <div class="mt-3 h-[4px] bg-[#2f33a8]"></div>

                <section class="mt-4 doc-grid-2">
                    <div class="doc-title text-[#e73072] font-extrabold uppercase"><?php echo htmlspecialchars($documentTitle); ?></div>
                    <div class="text-right doc-title text-[#e73072] font-extrabold pt-1">
                        NO: <?php echo htmlspecialchars($type === 'receipt' ? $payment['receipt_number'] : $invoice['invoice_number']); ?>
                    </div>
                </section>

                <section class="mt-6 doc-grid-2">
                    <div>
                        <div class="doc-heading text-[#2f33a8] font-extrabold">Bill To:</div>
                        <div class="mt-3 doc-text text-[#e73072]">
                            <?php echo nl2br(htmlspecialchars(trim($invoice['client_company'] !== '' ? $invoice['client_company'] : $invoice['client_name']))); ?>
                        </div>
                        <?php if ($invoice['client_company'] !== '' && $invoice['client_name'] !== ''): ?>
                            <div class="mt-1 doc-text text-[#e73072]"><?php echo htmlspecialchars($invoice['client_name']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <div class="doc-heading text-[#2f33a8] font-extrabold">From:</div>
                        <div class="mt-3 doc-text text-[#e73072] uppercase"><?php echo htmlspecialchars($profile['business_name']); ?></div>
                    </div>
                </section>

                <div class="mt-8 flex items-center justify-between gap-4">
                    <div class="doc-heading text-[#2f33a8]">Due Date: <?php echo htmlspecialchars(date('F j, Y', strtotime($invoice['due_date'] ?: $invoice['issue_date']))); ?></div>
                    <div class="doc-heading text-[#2f33a8] font-bold uppercase text-right"><?php echo htmlspecialchars($invoiceTypeLabel); ?></div>
                </div>

                <section class="mt-3">
                    <table class="w-full border-collapse text-center">
                        <thead>
                            <tr class="bg-[#ffe680] text-[#2f33a8] font-extrabold doc-heading">
                                <th class="border-[2px] border-[#e73072] px-2 py-3 w-[10%]">S/N</th>
                                <th class="border-[2px] border-[#e73072] px-2 py-3 <?php echo $showPercentageColumn ? 'w-[42%]' : 'w-[58%]'; ?>">DESCRIPTION</th>
                                <?php if ($showPercentageColumn): ?>
                                    <th class="border-[2px] border-[#e73072] px-2 py-3 w-[25%]">PERCENTAGE</th>
                                <?php endif; ?>
                                <th class="border-[2px] border-[#e73072] px-2 py-3 <?php echo $showPercentageColumn ? 'w-[23%]' : 'w-[32%]'; ?>">AMOUNT<br>₦</th>
                            </tr>
                        </thead>
                        <tbody class="text-[#2f33a8] doc-text">
                            <?php foreach ($invoice['items'] as $index => $item): ?>
                                <tr>
                                    <td class="border-[2px] border-[#e73072] px-2 py-4"><?php echo $index + 1; ?></td>
                                    <td class="border-[2px] border-[#e73072] px-2 py-4"><?php echo htmlspecialchars($item['description']); ?></td>
                                    <?php if ($showPercentageColumn): ?>
                                        <td class="border-[2px] border-[#e73072] px-2 py-4"><?php echo htmlspecialchars((string) ($item['percentage_value'] ?? '')); ?></td>
                                    <?php endif; ?>
                                    <td class="border-[2px] border-[#e73072] px-2 py-4"><?php echo number_format((float) $item['line_total'], 0); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (count($invoice['items']) < 2): ?>
                                <tr>
                                    <td class="border-[2px] border-[#e73072] px-2 py-4">&nbsp;</td>
                                    <td class="border-[2px] border-[#e73072] px-2 py-4">&nbsp;</td>
                                    <?php if ($showPercentageColumn): ?>
                                        <td class="border-[2px] border-[#e73072] px-2 py-4">&nbsp;</td>
                                    <?php endif; ?>
                                    <td class="border-[2px] border-[#e73072] px-2 py-4">&nbsp;</td>
                                </tr>
                            <?php endif; ?>
                            <tr class="bg-[#2f33a8] text-[#ffe680] font-extrabold doc-heading">
                                <td colspan="<?php echo $showPercentageColumn ? '3' : '2'; ?>" class="border-[2px] border-[#e73072] px-2 py-4">TOTAL AMOUNT DUE NOW</td>
                                <td class="border-[2px] border-[#e73072] px-2 py-4"><?php echo number_format((float) $invoice['total_amount'], 0); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </section>

                <section class="mt-6 doc-grid-2-bottom">
                    <div>
                        <div class="doc-heading text-[#2f33a8] font-extrabold">Note:</div>
                        <?php if ($contractValue > 0 && $showPercentageColumn): ?>
                            <div class="doc-text text-[#e73072]">Total Contract Value: <span class="text-[#2f33a8]">₦<?php echo number_format($contractValue, 0); ?></span></div>
                            <div class="doc-text text-[#e73072]">Total Balance Remaining: <span class="text-[#2f33a8]">₦<?php echo number_format($remainingContractBalance, 0); ?></span></div>
                        <?php endif; ?>
                        <?php if (!empty($invoice['notes'])): ?>
                            <div class="mt-2 doc-text text-[#2f33a8] whitespace-pre-line"><?php echo htmlspecialchars($invoice['notes']); ?></div>
                        <?php endif; ?>

                        <?php if ($showPaymentInformation): ?>
                            <div class="mt-6">
                                <div class="inline-block bg-blue-100 px-2 text-[#2f33a8] font-extrabold doc-heading">Payment Information:</div>
                                <div class="mt-2 space-y-1 doc-text">
                                    <div><span class="inline-block bg-blue-100 px-2 mr-3 text-[#2f33a8] font-bold">Bank:</span><span class="text-[#e73072]"><?php echo htmlspecialchars($profile['bank_name']); ?></span></div>
                                    <div><span class="inline-block bg-blue-100 px-2 mr-3 text-[#2f33a8] font-bold">No Bank:</span><span class="text-[#e73072]"><?php echo htmlspecialchars($profile['bank_account_number']); ?></span></div>
                                    <div><span class="inline-block bg-blue-100 px-2 mr-3 text-[#2f33a8] font-bold">Email:</span><span class="text-[#e73072]"><?php echo htmlspecialchars($profile['bank_account_name']); ?></span></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="text-right">
                        <div class="flex items-end justify-end gap-6">
                            <div class="text-left text-[#2f33a8] doc-heading">
                                <div>Authorised</div>
                                <div>Signature</div>
                            </div>
                            <div class="min-h-[90px] min-w-[150px] flex items-end justify-end">
                                <?php if (!empty($invoice['signature_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($invoice['signature_path']); ?>" alt="Authorized signature" class="h-20 w-auto object-contain">
                                <?php else: ?>
                                    <div class="text-[#2f33a8] text-lg font-semibold">JLM</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mt-10 text-[#2f33a8] doc-heading font-semibold">Thank You!</div>
                    </div>
                </section>

                <?php if ($type === 'invoice'): ?>
                    <section class="print-hide mt-12 rounded-[28px] border border-slate-200 bg-slate-50 p-6">
                        <h2 class="text-2xl font-bold text-slate-900">Payment Options</h2>
                        <p class="text-slate-600 mt-2">Choose online payment for instant confirmation and automatic receipt generation, or use Moniepoint transfer and submit your proof for manual verification.</p>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                            <div class="rounded-3xl bg-white border border-slate-200 p-5">
                                <h3 class="text-xl font-bold text-[#2f33a8]">Online Payment</h3>
                                <p class="text-slate-600 mt-2">Pay securely online. This will clear the full outstanding amount shown on this invoice.</p>
                                <?php if (jlm_paystack_enabled() && !$isPaidInFull): ?>
                                    <form method="get" action="billing_paystack_initialize.php" class="mt-5 space-y-4">
                                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($invoice['public_token']); ?>">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-2">Amount to Pay</label>
                                            <input type="text" readonly value="<?php echo htmlspecialchars(jlm_money($invoice['balance_due'], $invoice['currency'])); ?>" class="w-full border border-slate-300 rounded-2xl px-4 py-3 bg-slate-100">
                                        </div>
                                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-[#2f33a8] to-[#e73072] text-white font-semibold hover:opacity-95 transition">
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="2.5" y="5.5" width="19" height="13" rx="2"></rect>
                                                <path d="M2.5 10h19"></path>
                                            </svg>
                                            <span>Pay Online</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="mt-5 text-sm text-slate-500"><?php echo $isPaidInFull ? 'This invoice has already been fully paid.' : 'Online payment is not configured yet.'; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="rounded-3xl bg-white border border-slate-200 p-5">
                                <h3 class="text-xl font-bold text-[#2f33a8]">Bank Transfer</h3>
                                <div class="mt-3 space-y-1 text-slate-700">
                                    <div><strong>Bank:</strong> <?php echo htmlspecialchars($profile['bank_name']); ?></div>
                                    <div><strong>No Bank:</strong> <?php echo htmlspecialchars($profile['bank_account_number']); ?></div>
                                    <div><strong>Email:</strong> <?php echo htmlspecialchars($profile['bank_account_name']); ?></div>
                                </div>
                                <form method="post" enctype="multipart/form-data" class="mt-5 space-y-4">
                                    <input type="hidden" name="action" value="confirm_transfer">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Payer Name</label>
                                        <input type="text" name="payer_name" class="w-full border border-slate-300 rounded-2xl px-4 py-3" value="<?php echo htmlspecialchars($invoice['client_name']); ?>" <?php echo $isPaidInFull ? 'disabled' : ''; ?>>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Transfer Reference</label>
                                        <input type="text" name="payment_reference" class="w-full border border-slate-300 rounded-2xl px-4 py-3" <?php echo $isPaidInFull ? 'disabled' : ''; ?>>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Amount Transferred</label>
                                        <input type="text" readonly value="<?php echo htmlspecialchars(jlm_money($invoice['balance_due'], $invoice['currency'])); ?>" class="w-full border border-slate-300 rounded-2xl px-4 py-3 bg-slate-100">
                                        <input type="hidden" name="payment_amount" value="<?php echo htmlspecialchars((string) $invoice['balance_due']); ?>">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Upload Receipt / Proof</label>
                                        <input type="file" name="receipt_file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full border border-slate-300 rounded-2xl px-4 py-3 bg-white" <?php echo $isPaidInFull ? 'disabled' : ''; ?>>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Notes</label>
                                        <textarea name="payment_notes" rows="3" class="w-full border border-slate-300 rounded-2xl px-4 py-3" placeholder="Optional transfer note" <?php echo $isPaidInFull ? 'disabled' : ''; ?>></textarea>
                                    </div>
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-[#2f33a8] to-[#e73072] text-white font-semibold hover:opacity-95 transition <?php echo $isPaidInFull ? 'opacity-50 cursor-not-allowed' : ''; ?>" <?php echo $isPaidInFull ? 'disabled' : ''; ?>>
                                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 3v12"></path>
                                            <path d="m7 10 5 5 5-5"></path>
                                            <path d="M4 20h16"></path>
                                        </svg>
                                        <span>Submit Confirmation</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <?php if (!empty($pendingPayments)): ?>
                            <div class="mt-6 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-amber-900">
                                <strong>Pending transfer confirmation:</strong> We have received a transfer submission for this invoice and it is awaiting manual verification.
                            </div>
                        <?php endif; ?>

                        <?php if ((float) $invoice['amount_paid'] > 0 && (float) $invoice['balance_due'] > 0): ?>
                            <div class="mt-6 rounded-2xl bg-blue-50 border border-blue-200 p-4 text-blue-900">
                                <strong>Payment progress:</strong> Paid so far <?php echo htmlspecialchars(jlm_money($invoice['amount_paid'], $invoice['currency'])); ?>. Remaining balance <?php echo htmlspecialchars(jlm_money($invoice['balance_due'], $invoice['currency'])); ?>.
                            </div>
                        <?php endif; ?>

                        <?php if ($isPaidInFull): ?>
                            <div class="mt-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900">
                                <strong>Invoice settled:</strong> This invoice has been fully paid, so payment options are disabled.
                            </div>
                        <?php endif; ?>

                        <?php if ($latestReceipt): ?>
                            <div class="mt-6 rounded-2xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-900">
                                <strong>Latest receipt:</strong>
                                <a class="underline font-semibold" href="<?php echo htmlspecialchars(jlm_base_url() . '/billing_document.php?type=receipt&token=' . rawurlencode($latestReceipt['public_token'])); ?>" target="_blank">Open receipt</a>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
