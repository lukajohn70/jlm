<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';

jlm_ensure_business_tools_schema($pdo);

$token = trim((string) ($_GET['token'] ?? ''));
if ($token === '') {
    http_response_code(400);
    exit('Missing invoice token.');
}

$stmt = $pdo->prepare('SELECT id FROM invoices WHERE public_token = ? LIMIT 1');
$stmt->execute([$token]);
$invoiceId = (int) $stmt->fetchColumn();
$invoice = $invoiceId > 0 ? jlm_get_invoice_bundle($pdo, $invoiceId) : null;

if (!$invoice) {
    http_response_code(404);
    exit('Invoice not found.');
}

if (!jlm_paystack_enabled()) {
    http_response_code(500);
    exit('Paystack is not configured yet.');
}

if ((float) $invoice['balance_due'] <= 0) {
    header('Location: ' . jlm_base_url() . '/billing_document.php?type=invoice&token=' . rawurlencode($invoice['public_token']));
    exit;
}

$payAmount = (float) $invoice['balance_due'];
if ($payAmount <= 0) {
    http_response_code(400);
    exit('Invoice has no outstanding balance.');
}

header('Location: ' . jlm_base_url() . '/billing_pay_online.php?token=' . rawurlencode($invoice['public_token']));
exit;
