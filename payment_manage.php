<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';
require_once __DIR__ . '/includes/functions.php';

$adminUser = require_admin_auth();
jlm_ensure_business_tools_schema($pdo);

$invoiceId = (int) ($_GET['invoice_id'] ?? 0);
$statusFilter = trim((string) ($_GET['status'] ?? ''));
$statusSql = '';
$params = [];

if ($invoiceId > 0) {
    $statusSql .= ' AND p.invoice_id = ?';
    $params[] = $invoiceId;
}
if (in_array($statusFilter, ['pending', 'verified', 'failed'], true)) {
    $statusSql .= ' AND p.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("
    SELECT
        p.*,
        i.invoice_number,
        i.public_token AS invoice_public_token,
        i.client_name,
        i.client_email,
        i.currency,
        i.status AS invoice_status,
        i.payment_scope
    FROM invoice_payments p
    INNER JOIN invoices i ON i.id = p.invoice_id
    WHERE 1=1 {$statusSql}
    ORDER BY
        CASE p.status WHEN 'pending' THEN 0 WHEN 'verified' THEN 1 ELSE 2 END,
        p.payment_date DESC,
        p.id DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Tracking | JLM</title>
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen">
    <header class="bg-white border-b shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Payment Tracking</h1>
                <p class="text-sm text-gray-500">View pending, verified, and proof-backed invoice payments in one place.</p>
            </div>
            <div class="flex gap-3">
                <a href="billing_manage.php<?php echo $invoiceId > 0 ? '?edit=' . $invoiceId : ''; ?>" class="px-4 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">Back to Billing</a>
                <a href="admin_dashboard.php?tab=billing" class="px-4 py-2 rounded-lg bg-violet-600 text-white font-semibold hover:bg-violet-700 transition">Dashboard</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        <section class="bg-white rounded-2xl shadow-sm p-6">
            <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        <option value="">All payments</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="verified" <?php echo $statusFilter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Filter</label>
                    <input type="text" readonly value="<?php echo $invoiceId > 0 ? 'Invoice #' . $invoiceId : 'All invoices'; ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-gray-50">
                    <?php if ($invoiceId > 0): ?><input type="hidden" name="invoice_id" value="<?php echo $invoiceId; ?>"><?php endif; ?>
                </div>
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" class="px-5 py-3 rounded-xl bg-violet-600 text-white font-semibold hover:bg-violet-700 transition">Apply Filter</button>
                    <a href="payment_manage.php<?php echo $invoiceId > 0 ? '?invoice_id=' . $invoiceId : ''; ?>" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">Reset</a>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Payments</h2>
                <div class="text-sm text-gray-500"><?php echo count($payments); ?> record<?php echo count($payments) === 1 ? '' : 's'; ?></div>
            </div>

            <?php if (empty($payments)): ?>
                <p class="text-sm text-gray-500">No payment records found for this filter.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($payments as $payment): ?>
                        <?php
                        $invoiceUrl = jlm_base_url() . '/billing_document.php?type=invoice&token=' . rawurlencode($payment['invoice_public_token']);
                        $receiptUrl = jlm_base_url() . '/billing_document.php?type=receipt&token=' . rawurlencode($payment['public_token']);
                        $proofUrl = !empty($payment['receipt_upload_path']) ? jlm_base_url() . '/' . ltrim($payment['receipt_upload_path'], '/') : '';
                        ?>
                        <div class="border border-gray-200 rounded-2xl p-4">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <div>
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($payment['receipt_number']); ?> <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($payment['invoice_number']); ?>)</span></div>
                                    <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($payment['client_name']); ?> • <?php echo htmlspecialchars($payment['client_email']); ?></div>
                                    <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars(date('M j, Y', strtotime($payment['payment_date']))); ?> • <?php echo htmlspecialchars(jlm_money($payment['amount'], $payment['currency'])); ?> • <?php echo htmlspecialchars($payment['payment_method'] ?: 'Payment'); ?></div>
                                    <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($payment['payment_reference'] ?: ($payment['gateway_reference'] ?: 'No reference')); ?> • <?php echo htmlspecialchars(ucfirst((string) $payment['status'])); ?> • <?php echo htmlspecialchars(($payment['payment_scope'] ?? 'full') === 'part' ? 'Part Payment Invoice' : 'Full Payment Invoice'); ?></div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-3 py-2 rounded-lg text-xs font-semibold <?php echo ($payment['status'] ?? '') === 'verified' ? 'bg-green-100 text-green-700' : (($payment['status'] ?? '') === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-200 text-gray-700'); ?>"><?php echo htmlspecialchars(ucfirst((string) $payment['status'])); ?></span>
                                    <a href="<?php echo htmlspecialchars($invoiceUrl); ?>" target="_blank" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">View Invoice</a>
                                    <a href="<?php echo htmlspecialchars($receiptUrl); ?>" target="_blank" class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 font-semibold hover:bg-emerald-100 transition <?php echo ($payment['status'] ?? '') === 'verified' ? '' : 'opacity-50 pointer-events-none'; ?>">View Receipt</a>
                                    <?php if ($proofUrl !== ''): ?>
                                        <a href="<?php echo htmlspecialchars($proofUrl); ?>" target="_blank" class="px-3 py-2 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition">Open Proof</a>
                                    <?php endif; ?>
                                    <a href="billing_manage.php?edit=<?php echo (int) $payment['invoice_id']; ?>" class="px-3 py-2 rounded-lg bg-violet-50 text-violet-700 font-semibold hover:bg-violet-100 transition">Open Record</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
