<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';
require_once __DIR__ . '/includes/functions.php';

$adminUser = require_admin_auth();
$adminId = (int) $adminUser['id'];
jlm_ensure_business_tools_schema($pdo);

$toast = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));

    try {
        if ($action === 'save_invoice') {
            $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
            $clientName = trim((string) ($_POST['client_name'] ?? ''));
            $clientEmail = trim((string) ($_POST['client_email'] ?? ''));
            $clientPhone = trim((string) ($_POST['client_phone'] ?? ''));
            $clientCompany = trim((string) ($_POST['client_company'] ?? ''));
            $clientAddress = trim((string) ($_POST['client_address'] ?? ''));
            $issueDate = trim((string) ($_POST['issue_date'] ?? date('Y-m-d')));
            $dueDate = trim((string) ($_POST['due_date'] ?? ''));
            $currency = strtoupper(trim((string) ($_POST['currency'] ?? 'NGN')));
            $totalContractValue = (float) ($_POST['total_contract_value'] ?? 0);
            $paymentScope = trim((string) ($_POST['payment_scope'] ?? 'full'));
            $paymentScope = in_array($paymentScope, ['full', 'part'], true) ? $paymentScope : 'full';
            $notes = trim((string) ($_POST['notes'] ?? ''));
            $terms = trim((string) ($_POST['terms'] ?? ''));
            $discountAmount = (float) ($_POST['discount_amount'] ?? 0);
            $taxAmount = (float) ($_POST['tax_amount'] ?? 0);
            $status = trim((string) ($_POST['status'] ?? 'draft'));
            $status = in_array($status, ['draft', 'sent', 'part_paid', 'paid', 'cancelled'], true) ? $status : 'draft';
            $items = jlm_normalize_invoice_items($_POST);

            if ($clientName === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Client name and a valid client email are required.');
            }
            if (empty($items)) {
                throw new RuntimeException('Add at least one invoice item.');
            }

            $currentAmountPaid = 0.0;
            $invoiceNumber = '';
            $publicToken = '';
            $signaturePath = null;
            if ($invoiceId > 0) {
                $existing = $pdo->prepare('SELECT * FROM invoices WHERE id = ?');
                $existing->execute([$invoiceId]);
                $invoice = $existing->fetch(PDO::FETCH_ASSOC);
                if (!$invoice) {
                    throw new RuntimeException('Invoice not found.');
                }
                $currentAmountPaid = (float) $invoice['amount_paid'];
                $invoiceNumber = $invoice['invoice_number'];
                $publicToken = $invoice['public_token'];
                $signaturePath = $invoice['signature_path'] ?? null;
            } else {
                $invoiceNumber = jlm_generate_unique_reference($pdo, 'invoices', 'invoice_number', 'INV');
                $publicToken = jlm_business_random_token(40);
                $signaturePath = jlm_get_persistent_signature_path($pdo);
            }

            if (!empty($_FILES['signature_file']) && is_array($_FILES['signature_file']) && (int) ($_FILES['signature_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $signaturePath = jlm_upload_invoice_signature($_FILES['signature_file']);
            }

            $totals = jlm_calculate_invoice_totals($items, $discountAmount, $taxAmount, $currentAmountPaid);
            $resolvedStatus = jlm_invoice_status_from_balance($totals['balance_due'], $totals['amount_paid'], $status);

            if ($invoiceId > 0) {
                $stmt = $pdo->prepare('UPDATE invoices SET client_name = ?, client_email = ?, client_phone = ?, client_company = ?, client_address = ?, issue_date = ?, due_date = ?, currency = ?, total_contract_value = ?, payment_scope = ?, signature_path = ?, notes = ?, terms = ?, subtotal = ?, discount_amount = ?, tax_amount = ?, total_amount = ?, amount_paid = ?, balance_due = ?, status = ?, updated_by = ?, sent_at = CASE WHEN ? = "sent" AND sent_at IS NULL THEN NOW() ELSE sent_at END, paid_at = CASE WHEN ? = "paid" THEN NOW() ELSE paid_at END WHERE id = ?');
                $stmt->execute([
                    $clientName,
                    $clientEmail,
                    $clientPhone,
                    $clientCompany,
                    $clientAddress,
                    $issueDate,
                    $dueDate !== '' ? $dueDate : null,
                    $currency,
                    $totalContractValue,
                    $paymentScope,
                    $signaturePath,
                    $notes,
                    $terms,
                    $totals['subtotal'],
                    $totals['discount_amount'],
                    $totals['tax_amount'],
                    $totals['total_amount'],
                    $totals['amount_paid'],
                    $totals['balance_due'],
                    $resolvedStatus,
                    $adminId,
                    $resolvedStatus,
                    $resolvedStatus,
                    $invoiceId,
                ]);
                $pdo->prepare('DELETE FROM invoice_items WHERE invoice_id = ?')->execute([$invoiceId]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO invoices (invoice_number, public_token, client_name, client_email, client_phone, client_company, client_address, issue_date, due_date, currency, total_contract_value, payment_scope, signature_path, notes, terms, subtotal, discount_amount, tax_amount, total_amount, amount_paid, balance_due, status, created_by, updated_by, sent_at, paid_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $invoiceNumber,
                    $publicToken,
                    $clientName,
                    $clientEmail,
                    $clientPhone,
                    $clientCompany,
                    $clientAddress,
                    $issueDate,
                    $dueDate !== '' ? $dueDate : null,
                    $currency,
                    $totalContractValue,
                    $paymentScope,
                    $signaturePath,
                    $notes,
                    $terms,
                    $totals['subtotal'],
                    $totals['discount_amount'],
                    $totals['tax_amount'],
                    $totals['total_amount'],
                    $totals['amount_paid'],
                    $totals['balance_due'],
                    $resolvedStatus,
                    $adminId,
                    $adminId,
                    $resolvedStatus === 'sent' ? date('Y-m-d H:i:s') : null,
                    $resolvedStatus === 'paid' ? date('Y-m-d H:i:s') : null,
                ]);
                $invoiceId = (int) $pdo->lastInsertId();
            }

            $itemStmt = $pdo->prepare('INSERT INTO invoice_items (invoice_id, description, percentage_value, quantity, unit_price, line_total, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($items as $index => $item) {
                $itemStmt->execute([
                    $invoiceId,
                    $item['description'],
                    $item['percentage_value'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['line_total'],
                    $index + 1,
                ]);
            }

            $_SESSION['toast'] = 'Invoice saved successfully.';
            header('Location: billing_manage.php?edit=' . $invoiceId);
            exit;
        }

        if ($action === 'email_invoice') {
            $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
            $invoice = jlm_get_invoice_bundle($pdo, $invoiceId);
            if (!$invoice) {
                throw new RuntimeException('Invoice not found.');
            }

            $baseUrl = jlm_base_url();
            $invoiceUrl = $baseUrl . '/billing_document.php?type=invoice&token=' . rawurlencode($invoice['public_token']);
            if (!jlm_send_invoice_email($invoice, $invoiceUrl)) {
                throw new RuntimeException('Invoice email could not be sent.');
            }

            $pdo->prepare('UPDATE invoices SET status = CASE WHEN status = "draft" THEN "sent" ELSE status END, sent_at = NOW(), updated_by = ? WHERE id = ?')->execute([$adminId, $invoiceId]);
            $_SESSION['toast'] = 'Invoice email sent successfully.';
            header('Location: billing_manage.php?edit=' . $invoiceId);
            exit;
        }

        if ($action === 'add_payment') {
            $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
            $paymentDate = trim((string) ($_POST['payment_date'] ?? date('Y-m-d')));
            $amount = (float) ($_POST['payment_amount'] ?? 0);
            $paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));
            $paymentReference = trim((string) ($_POST['payment_reference'] ?? ''));
            $notes = trim((string) ($_POST['payment_notes'] ?? ''));
            $receiptUploadPath = null;

            if ($amount <= 0) {
                throw new RuntimeException('Payment amount must be greater than zero.');
            }

            $invoice = jlm_get_invoice_bundle($pdo, $invoiceId);
            if (!$invoice) {
                throw new RuntimeException('Invoice not found.');
            }

            if (!empty($_FILES['payment_receipt_file']) && is_array($_FILES['payment_receipt_file']) && (int) ($_FILES['payment_receipt_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $receiptUploadPath = jlm_upload_payment_receipt($_FILES['payment_receipt_file']);
            }

            $paymentId = jlm_create_invoice_payment($pdo, [
                'invoice_id' => $invoiceId,
                'receipt_number' => jlm_generate_unique_reference($pdo, 'invoice_payments', 'receipt_number', 'RCT'),
                'public_token' => jlm_business_random_token(40),
                'payer_name' => $invoice['client_name'],
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'status' => 'verified',
                'payment_method' => $paymentMethod,
                'payment_channel' => 'manual_admin',
                'payment_reference' => $paymentReference,
                'gateway_status' => 'verified',
                'notes' => $notes,
                'receipt_upload_path' => $receiptUploadPath,
                'verified_at' => date('Y-m-d H:i:s'),
                'verified_by' => $adminId,
                'created_by' => $adminId,
            ]);

            $invoice = jlm_verify_invoice_payment($pdo, $paymentId, $adminId, null, 'verified');
            $paymentLookup = $pdo->prepare('SELECT * FROM invoice_payments WHERE id = ? LIMIT 1');
            $paymentLookup->execute([$paymentId]);
            $payment = $paymentLookup->fetch(PDO::FETCH_ASSOC);
            $baseUrl = jlm_base_url();
            $receiptUrl = $baseUrl . '/billing_document.php?type=receipt&token=' . rawurlencode($payment['public_token']);
            jlm_send_receipt_email($invoice, $payment, $receiptUrl);
            jlm_send_admin_payment_notice($invoice, $payment, true);

            $_SESSION['toast'] = 'Payment logged and receipt generated.';
            header('Location: billing_manage.php?edit=' . $invoiceId);
            exit;
        }

        if ($action === 'verify_transfer') {
            $paymentId = (int) ($_POST['payment_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM invoice_payments WHERE id = ? LIMIT 1');
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$payment) {
                throw new RuntimeException('Payment record not found.');
            }

            $invoice = jlm_verify_invoice_payment($pdo, $paymentId, $adminId, null, 'manual_transfer_verified');
            $paymentStmt = $pdo->prepare('SELECT * FROM invoice_payments WHERE id = ? LIMIT 1');
            $paymentStmt->execute([$paymentId]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);
            $receiptUrl = jlm_base_url() . '/billing_document.php?type=receipt&token=' . rawurlencode($payment['public_token']);
            jlm_send_receipt_email($invoice, $payment, $receiptUrl);
            jlm_send_admin_payment_notice($invoice, $payment, true);

            $_SESSION['toast'] = 'Transfer verified and receipt sent.';
            header('Location: billing_manage.php?edit=' . (int) $invoice['id']);
            exit;
        }

        if ($action === 'mark_complete') {
            $invoiceId = (int) ($_POST['invoice_id'] ?? 0);
            $invoice = jlm_mark_invoice_complete($pdo, $invoiceId, $adminId);
            $_SESSION['toast'] = 'Invoice marked complete and payment records updated.';
            header('Location: billing_manage.php?edit=' . (int) $invoice['id']);
            exit;
        }
    } catch (Throwable $e) {
        $toast = $e->getMessage();
    }
}

$toast = $_SESSION['toast'] ?? $toast;
unset($_SESSION['toast']);

$editId = (int) ($_GET['edit'] ?? 0);
$editingInvoice = $editId > 0 ? jlm_get_invoice_bundle($pdo, $editId) : null;
$clientPaymentHistory = [];
if ($editingInvoice && !empty($editingInvoice['client_email'])) {
    $historyStmt = $pdo->prepare("
        SELECT i.invoice_number, i.public_token AS invoice_public_token, i.payment_scope, i.total_amount, i.balance_due, i.currency,
               p.id AS payment_id, p.receipt_number, p.payment_date, p.amount, p.status AS payment_status,
               p.payment_method, p.payment_reference, p.gateway_reference, p.public_token
        FROM invoices i
        LEFT JOIN invoice_payments p ON p.invoice_id = i.id
        WHERE i.client_email = ?
        ORDER BY i.created_at DESC, p.payment_date DESC, p.id DESC
    ");
    $historyStmt->execute([$editingInvoice['client_email']]);
    $clientPaymentHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
}

$invoices = $pdo->query("
    SELECT i.*,
           (SELECT COUNT(*) FROM invoice_payments p WHERE p.invoice_id = i.id) AS payment_count
    FROM invoices i
    ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => (int) $pdo->query('SELECT COUNT(*) FROM invoices')->fetchColumn(),
    'draft' => (int) $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'draft'")->fetchColumn(),
    'outstanding' => (int) $pdo->query("SELECT COUNT(*) FROM invoices WHERE balance_due > 0")->fetchColumn(),
    'paid' => (int) $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'paid'")->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(amount_paid), 0) FROM invoices WHERE status IN ('paid','part_paid')")->fetchColumn(),
];
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Invoices | JLM</title>
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen">
    <header class="bg-white shadow-sm border-b sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Billing & Invoices</h1>
                    <p class="text-sm text-gray-500">Create invoices, log payments, and issue receipts from the website.</p>
                </div>
            </div>
            <a href="admin_dashboard.php?tab=billing" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <?php if ($toast): ?>
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-xl"><?php echo htmlspecialchars($toast); ?></div>
        <?php endif; ?>

        <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="text-2xl font-bold text-blue-700"><?php echo $stats['total']; ?></div>
                <div class="text-sm text-gray-500">Invoices</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="text-2xl font-bold text-amber-700"><?php echo $stats['draft']; ?></div>
                <div class="text-sm text-gray-500">Draft</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="text-2xl font-bold text-rose-700"><?php echo $stats['outstanding']; ?></div>
                <div class="text-sm text-gray-500">Outstanding</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <div class="text-2xl font-bold text-green-700"><?php echo $stats['paid']; ?></div>
                <div class="text-sm text-gray-500">Paid</div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-5 col-span-2 lg:col-span-4">
                <div class="text-2xl font-bold text-violet-700"><?php echo htmlspecialchars(jlm_money($stats['revenue'], 'NGN')); ?></div>
                <div class="text-sm text-gray-500">Total Revenue Collected</div>
            </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900"><?php echo $editingInvoice ? 'Edit Invoice' : 'New Invoice'; ?></h2>
                        <p class="text-sm text-gray-500">Use print-friendly documents that can be emailed or saved as PDF.</p>
                    </div>
                </div>

                <form method="post" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="save_invoice">
                    <input type="hidden" name="invoice_id" value="<?php echo (int) ($editingInvoice['id'] ?? 0); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client Name</label>
                            <input type="text" name="client_name" required value="<?php echo htmlspecialchars($editingInvoice['client_name'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client Email</label>
                            <input type="email" name="client_email" required value="<?php echo htmlspecialchars($editingInvoice['client_email'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client Phone</label>
                            <input type="text" name="client_phone" value="<?php echo htmlspecialchars($editingInvoice['client_phone'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client Company</label>
                            <input type="text" name="client_company" value="<?php echo htmlspecialchars($editingInvoice['client_company'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Client Address</label>
                            <textarea name="client_address" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3"><?php echo htmlspecialchars($editingInvoice['client_address'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Issue Date</label>
                            <input type="date" name="issue_date" value="<?php echo htmlspecialchars($editingInvoice['issue_date'] ?? date('Y-m-d')); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Due Date</label>
                            <input type="date" name="due_date" value="<?php echo htmlspecialchars($editingInvoice['due_date'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Currency</label>
                            <?php $selectedCurrency = $editingInvoice['currency'] ?? 'NGN'; ?>
                            <select name="currency" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                <?php foreach (['NGN', 'USD', 'GBP', 'EUR'] as $currency): ?>
                                    <option value="<?php echo $currency; ?>" <?php echo $selectedCurrency === $currency ? 'selected' : ''; ?>><?php echo $currency; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Total Contract Value</label>
                            <input type="number" step="0.01" min="0" name="total_contract_value" value="<?php echo htmlspecialchars((string) ($editingInvoice['total_contract_value'] ?? 0)); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Type</label>
                            <?php $paymentScopeValue = $editingInvoice['payment_scope'] ?? 'full'; ?>
                            <select name="payment_scope" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                <option value="full" <?php echo $paymentScopeValue === 'full' ? 'selected' : ''; ?>>Full Payment</option>
                                <option value="part" <?php echo $paymentScopeValue === 'part' ? 'selected' : ''; ?>>Part Payment</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <?php $currentStatus = $editingInvoice['status'] ?? 'draft'; ?>
                            <select name="status" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                <?php foreach (['draft', 'sent', 'part_paid', 'paid', 'cancelled'] as $status): ?>
                                    <option value="<?php echo $status; ?>" <?php echo $currentStatus === $status ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $status)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">Invoice Items</h3>
                            <p class="text-sm text-gray-500">This layout matches your sample better. The percentage column appears on the printout only for part-payment invoices.</p>
                        </div>
                        <div id="itemBuilder" class="space-y-3">
                            <?php
                            $items = $editingInvoice['items'] ?? [];
                            if (empty($items)) {
                                $items = [[
                                    'description' => 'Creative service package',
                                    'percentage_value' => 0,
                                    'line_total' => 0,
                                ]];
                            }
                            foreach ($items as $item):
                            ?>
                                <div class="invoice-item grid grid-cols-1 md:grid-cols-12 gap-3 items-end border border-gray-200 rounded-2xl p-4 bg-gray-50">
                                    <div class="md:col-span-7">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                        <input type="text" name="item_description[]" value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Percentage</label>
                                        <input type="number" step="0.01" min="0" name="item_percentage[]" value="<?php echo htmlspecialchars((string) ($item['percentage_value'] ?? '')); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Due Now</label>
                                        <input type="number" step="0.01" min="0" name="item_amount[]" value="<?php echo htmlspecialchars((string) ($item['line_total'] ?? $item['unit_price'] ?? 0)); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                        <input type="hidden" name="item_quantity[]" value="1">
                                        <input type="hidden" name="item_unit_price[]" value="<?php echo htmlspecialchars((string) ($item['line_total'] ?? $item['unit_price'] ?? 0)); ?>">
                                    </div>
                                    <div class="md:col-span-1">
                                        <button type="button" class="remove-item w-full px-3 py-3 rounded-xl bg-red-50 text-red-600 font-semibold hover:bg-red-100 transition">X</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-5">
                            <button type="button" id="addItemBtn" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
                                <i class="fas fa-plus mr-2"></i>Add Item
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Discount Amount</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" value="<?php echo htmlspecialchars((string) ($editingInvoice['discount_amount'] ?? 0)); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tax Amount</label>
                            <input type="number" step="0.01" min="0" name="tax_amount" value="<?php echo htmlspecialchars((string) ($editingInvoice['tax_amount'] ?? 0)); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3"><?php echo htmlspecialchars($editingInvoice['notes'] ?? 'Thank you for choosing JLM.'); ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Terms</label>
                            <textarea name="terms" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3"><?php echo htmlspecialchars($editingInvoice['terms'] ?? 'Payment is due by the stated due date unless otherwise agreed in writing.'); ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Authorized Signature</label>
                            <input type="file" name="signature_file" accept=".jpg,.jpeg,.png,.webp" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-white">
                            <p class="text-sm text-gray-500 mt-2">Upload your signature here and it will appear automatically on the invoice and receipt printout.</p>
                            <?php if (!empty($editingInvoice['signature_path'])): ?>
                                <div class="mt-3">
                                    <img src="<?php echo htmlspecialchars($editingInvoice['signature_path']); ?>" alt="Current signature" class="h-16 w-auto">
                                </div>
                            <?php elseif ($persistentSignature = jlm_get_persistent_signature_path($pdo)): ?>
                                <div class="mt-3">
                                    <div class="text-sm text-gray-500 mb-2">Current default signature</div>
                                    <img src="<?php echo htmlspecialchars($persistentSignature); ?>" alt="Default signature" class="h-16 w-auto">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="px-5 py-3 rounded-xl bg-gradient-to-r from-blue-800 to-pink-600 text-white font-semibold hover:opacity-95 transition">Save Invoice</button>
                        <?php if ($editingInvoice): ?>
                            <a href="billing_manage.php" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">Create New</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <?php if ($editingInvoice): ?>
                    <?php $invoiceUrl = jlm_base_url() . '/billing_document.php?type=invoice&token=' . rawurlencode($editingInvoice['public_token']); ?>
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Invoice Actions</h2>
                        <div class="space-y-3">
                            <a href="<?php echo htmlspecialchars($invoiceUrl); ?>" target="_blank" class="block text-center px-4 py-3 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">Open Printable Invoice</a>
                            <a href="<?php echo htmlspecialchars(jlm_base_url() . '/billing_paystack_initialize.php?token=' . rawurlencode($editingInvoice['public_token'])); ?>" target="_blank" class="block text-center px-4 py-3 rounded-xl bg-emerald-600 text-white font-semibold hover:bg-emerald-700 transition <?php echo jlm_paystack_enabled() ? '' : 'opacity-50 pointer-events-none'; ?>">Open Online Payment Link</a>
                            <a href="payment_manage.php?invoice_id=<?php echo (int) $editingInvoice['id']; ?>" class="block text-center px-4 py-3 rounded-xl bg-violet-50 text-violet-700 font-semibold hover:bg-violet-100 transition">View Payment Tracking</a>
                            <form method="post">
                                <input type="hidden" name="action" value="email_invoice">
                                <input type="hidden" name="invoice_id" value="<?php echo (int) $editingInvoice['id']; ?>">
                                <button type="submit" class="w-full px-4 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Email Invoice to Client</button>
                            </form>
                            <?php if (($editingInvoice['payment_scope'] ?? 'full') === 'part' && (float) ($editingInvoice['balance_due'] ?? 0) > 0): ?>
                                <form method="post">
                                    <input type="hidden" name="action" value="mark_complete">
                                    <input type="hidden" name="invoice_id" value="<?php echo (int) $editingInvoice['id']; ?>">
                                    <button type="submit" class="w-full px-4 py-3 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition">Mark Invoice Complete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <div class="mt-4 text-sm text-gray-500 break-all"><?php echo htmlspecialchars($invoiceUrl); ?></div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Record Payment</h2>
                        <form method="post" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="add_payment">
                            <input type="hidden" name="invoice_id" value="<?php echo (int) $editingInvoice['id']; ?>">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                                <input type="date" name="payment_date" value="<?php echo htmlspecialchars(date('Y-m-d')); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                                <input type="number" step="0.01" min="0" name="payment_amount" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Method</label>
                                <input type="text" name="payment_method" placeholder="Bank transfer, card, cash..." class="w-full border border-gray-300 rounded-xl px-4 py-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Reference</label>
                                <input type="text" name="payment_reference" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                <textarea name="payment_notes" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Receipt / Proof Upload</label>
                                <input type="file" name="payment_receipt_file" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-white">
                            </div>
                            <button type="submit" class="w-full px-4 py-3 rounded-xl bg-green-600 text-white font-semibold hover:bg-green-700 transition">Save Payment & Send Receipt</button>
                        </form>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Account Details</h2>
                        <?php $profile = jlm_get_invoice_profile(); ?>
                        <div class="space-y-2 text-sm text-gray-700">
                            <div><strong>Bank:</strong> <?php echo htmlspecialchars($profile['bank_name']); ?></div>
                            <div><strong>No Bank:</strong> <?php echo htmlspecialchars($profile['bank_account_number']); ?></div>
                            <div><strong>Email:</strong> <?php echo htmlspecialchars($profile['bank_account_name']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoices</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-3 pr-4">Invoice</th>
                            <th class="py-3 pr-4">Client</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Total</th>
                            <th class="py-3 pr-4">Balance</th>
                            <th class="py-3 pr-4">Payments</th>
                            <th class="py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <?php $shareUrl = jlm_base_url() . '/billing_document.php?type=invoice&token=' . rawurlencode($invoice['public_token']); ?>
                            <tr class="border-b last:border-0">
                                <td class="py-4 pr-4">
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars(date('M j, Y', strtotime($invoice['issue_date']))); ?></div>
                                </td>
                                <td class="py-4 pr-4">
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($invoice['client_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($invoice['client_email']); ?></div>
                                </td>
                                <td class="py-4 pr-4"><span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $invoice['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($invoice['status'] === 'draft' ? 'bg-yellow-100 text-yellow-700' : ($invoice['status'] === 'cancelled' ? 'bg-gray-200 text-gray-700' : 'bg-blue-100 text-blue-700')); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $invoice['status']))); ?></span></td>
                                <td class="py-4 pr-4"><?php echo htmlspecialchars(jlm_money($invoice['total_amount'], $invoice['currency'])); ?></td>
                                <td class="py-4 pr-4"><?php echo htmlspecialchars(jlm_money($invoice['balance_due'], $invoice['currency'])); ?></td>
                                <td class="py-4 pr-4"><?php echo (int) $invoice['payment_count']; ?></td>
                                <td class="py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="billing_manage.php?edit=<?php echo (int) $invoice['id']; ?>" class="px-3 py-2 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition">Edit</a>
                                        <a href="<?php echo htmlspecialchars($shareUrl); ?>" target="_blank" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">View</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($editingInvoice): ?>
            <section class="bg-white rounded-2xl shadow-sm p-6">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Payments & Receipts</h2>
                        <a href="payment_manage.php?invoice_id=<?php echo (int) $editingInvoice['id']; ?>" class="px-4 py-2 rounded-xl bg-violet-50 text-violet-700 font-semibold hover:bg-violet-100 transition">Open Full Tracking</a>
                    </div>
                    <div class="space-y-4">
                    <?php if (empty($editingInvoice['payments'])): ?>
                        <p class="text-sm text-gray-500">No payments logged yet.</p>
                    <?php else: ?>
                        <?php foreach ($editingInvoice['payments'] as $payment): ?>
                            <?php $receiptUrl = jlm_base_url() . '/billing_document.php?type=receipt&token=' . rawurlencode($payment['public_token']); ?>
                            <div class="border border-gray-200 rounded-2xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($payment['receipt_number']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(date('M j, Y', strtotime($payment['payment_date']))); ?> • <?php echo htmlspecialchars(jlm_money($payment['amount'], $editingInvoice['currency'])); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($payment['payment_method'] ?: 'Payment'); ?><?php echo $payment['payment_reference'] ? ' • ' . htmlspecialchars($payment['payment_reference']) : ''; ?><?php echo !empty($payment['gateway_reference']) ? ' • ' . htmlspecialchars($payment['gateway_reference']) : ''; ?></div>
                                    <?php if (!empty($payment['receipt_upload_path'])): ?>
                                        <div class="text-xs text-blue-700 mt-2 break-all"><?php echo htmlspecialchars(jlm_base_url() . '/' . ltrim($payment['receipt_upload_path'], '/')); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <?php if (($payment['status'] ?? 'verified') === 'pending'): ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="verify_transfer">
                                            <input type="hidden" name="payment_id" value="<?php echo (int) $payment['id']; ?>">
                                            <button type="submit" class="px-4 py-3 rounded-xl bg-amber-500 text-white font-semibold hover:bg-amber-600 transition">Verify Transfer</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!empty($payment['receipt_upload_path'])): ?>
                                        <a href="<?php echo htmlspecialchars(jlm_base_url() . '/' . ltrim($payment['receipt_upload_path'], '/')); ?>" target="_blank" class="px-4 py-3 rounded-xl bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition">Open Proof</a>
                                    <?php endif; ?>
                                    <a href="<?php echo htmlspecialchars($receiptUrl); ?>" target="_blank" class="px-4 py-3 rounded-xl bg-green-50 text-green-700 font-semibold hover:bg-green-100 transition <?php echo ($payment['status'] ?? 'verified') === 'verified' ? '' : 'opacity-50 pointer-events-none'; ?>">Open Receipt</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Client Payment History</h2>
                <div class="space-y-3">
                    <?php if (empty($clientPaymentHistory)): ?>
                        <p class="text-sm text-gray-500">No payment records found for this client yet.</p>
                    <?php else: ?>
                        <?php foreach ($clientPaymentHistory as $historyRow): ?>
                            <div class="border border-gray-200 rounded-2xl p-4">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div>
                                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($historyRow['invoice_number']); ?> <span class="text-xs font-medium text-gray-500">(<?php echo htmlspecialchars($historyRow['payment_scope'] === 'part' ? 'Part Payment' : 'Full Payment'); ?>)</span></div>
                                        <div class="text-sm text-gray-500">Invoice Total: <?php echo htmlspecialchars(jlm_money($historyRow['total_amount'], $historyRow['currency'])); ?> • Balance: <?php echo htmlspecialchars(jlm_money($historyRow['balance_due'], $historyRow['currency'])); ?></div>
                                        <?php if (!empty($historyRow['receipt_number'])): ?>
                                            <div class="text-sm text-gray-500 mt-1">Receipt: <?php echo htmlspecialchars($historyRow['receipt_number']); ?> • <?php echo htmlspecialchars(date('M j, Y', strtotime($historyRow['payment_date']))); ?> • <?php echo htmlspecialchars(jlm_money($historyRow['amount'], $historyRow['currency'])); ?></div>
                                        <?php else: ?>
                                            <div class="text-sm text-gray-400 mt-1">No payment recorded yet for this invoice.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <a href="billing_document.php?type=invoice&token=<?php echo rawurlencode($historyRow['invoice_public_token']); ?>" target="_blank" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">View Invoice</a>
                                        <?php if (!empty($historyRow['public_token']) && !empty($historyRow['receipt_number'])): ?>
                                            <a href="billing_document.php?type=receipt&token=<?php echo rawurlencode($historyRow['public_token']); ?>" target="_blank" class="px-3 py-2 rounded-lg bg-green-50 text-green-700 font-semibold hover:bg-green-100 transition <?php echo ($historyRow['payment_status'] ?? 'verified') === 'verified' ? '' : 'opacity-50 pointer-events-none'; ?>">View Receipt</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <template id="invoiceItemTemplate">
        <div class="invoice-item grid grid-cols-1 md:grid-cols-12 gap-3 items-end border border-gray-200 rounded-2xl p-4 bg-gray-50">
            <div class="md:col-span-7">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <input type="text" name="item_description[]" class="w-full border border-gray-300 rounded-xl px-4 py-3">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Percentage</label>
                <input type="number" step="0.01" min="0" name="item_percentage[]" class="w-full border border-gray-300 rounded-xl px-4 py-3">
            </div>
            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-gray-700 mb-2">Amount Due Now</label>
                <input type="number" step="0.01" min="0" name="item_amount[]" value="0" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                <input type="hidden" name="item_quantity[]" value="1">
                <input type="hidden" name="item_unit_price[]" value="0">
            </div>
            <div class="md:col-span-1">
                <button type="button" class="remove-item w-full px-3 py-3 rounded-xl bg-red-50 text-red-600 font-semibold hover:bg-red-100 transition">X</button>
            </div>
        </div>
    </template>

    <script>
        (function () {
            const addItemBtn = document.getElementById('addItemBtn');
            const itemBuilder = document.getElementById('itemBuilder');
            const invoiceItemTemplate = document.getElementById('invoiceItemTemplate');

            function bindRemoveButtons() {
                document.querySelectorAll('.remove-item').forEach(function (button) {
                    button.onclick = function () {
                        const items = document.querySelectorAll('.invoice-item');
                        if (items.length === 1) {
                            return;
                        }
                        button.closest('.invoice-item').remove();
                    };
                });
            }

            if (addItemBtn && itemBuilder && invoiceItemTemplate) {
                addItemBtn.addEventListener('click', function () {
                    itemBuilder.insertAdjacentHTML('beforeend', invoiceItemTemplate.innerHTML);
                    bindRemoveButtons();
                });
                bindRemoveButtons();
            }
        })();
    </script>
</body>
</html>
