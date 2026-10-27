<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';

jlm_ensure_business_tools_schema($pdo);

$token = trim((string) ($_GET['token'] ?? ''));
$reference = trim((string) ($_GET['reference'] ?? ''));

if ($token === '' || $reference === '') {
    http_response_code(400);
    exit('Missing Paystack callback details.');
}

$stmt = $pdo->prepare('SELECT id FROM invoices WHERE public_token = ? LIMIT 1');
$stmt->execute([$token]);
$invoiceId = (int) $stmt->fetchColumn();
$invoice = $invoiceId > 0 ? jlm_get_invoice_bundle($pdo, $invoiceId) : null;

if (!$invoice) {
    http_response_code(404);
    exit('Invoice not found.');
}

try {
    $verification = jlm_verify_paystack_transaction($reference);
    $data = $verification['data'] ?? [];
    $status = (string) ($data['status'] ?? '');

    if ($status !== 'success') {
        header('Location: ' . jlm_base_url() . '/billing_document.php?type=invoice&token=' . rawurlencode($invoice['public_token']));
        exit;
    }

    $paymentStmt = $pdo->prepare('SELECT * FROM invoice_payments WHERE gateway_reference = ? LIMIT 1');
    $paymentStmt->execute([$reference]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        if (($payment['status'] ?? '') !== 'verified') {
            $pdo->prepare('UPDATE invoice_payments SET status = "verified", gateway_status = ?, verification_payload = ?, verified_at = NOW(), payment_method = "Paystack", payment_channel = "paystack", amount = ?, payment_date = CURDATE() WHERE id = ?')->execute([
                $status,
                json_encode($verification),
                ((float) ($data['amount'] ?? 0)) / 100,
                $payment['id'],
            ]);
        }
        $paymentId = (int) $payment['id'];
    } else {
        $paymentId = jlm_create_invoice_payment($pdo, [
            'invoice_id' => $invoice['id'],
            'receipt_number' => jlm_generate_unique_reference($pdo, 'invoice_payments', 'receipt_number', 'RCT'),
            'public_token' => jlm_business_random_token(40),
            'payer_name' => $invoice['client_name'],
            'payment_date' => date('Y-m-d'),
            'amount' => ((float) ($data['amount'] ?? 0)) / 100,
            'status' => 'verified',
            'payment_method' => 'Paystack',
            'payment_channel' => 'paystack',
            'payment_reference' => $reference,
            'gateway_reference' => $reference,
            'gateway_status' => $status,
            'verification_payload' => json_encode($verification),
            'notes' => 'Verified from Paystack callback',
            'verified_at' => date('Y-m-d H:i:s'),
            'created_by' => null,
        ]);
    }

    $invoice = jlm_verify_invoice_payment($pdo, $paymentId, null, json_encode($verification), $status);
    $paymentStmt = $pdo->prepare('SELECT * FROM invoice_payments WHERE id = ? LIMIT 1');
    $paymentStmt->execute([$paymentId]);
    $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

    $receiptUrl = jlm_base_url() . '/billing_document.php?type=receipt&token=' . rawurlencode($payment['public_token']);
    jlm_send_receipt_email($invoice, $payment, $receiptUrl);
    jlm_send_admin_payment_notice($invoice, $payment, true);

    header('Location: ' . $receiptUrl);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Unable to verify Paystack payment: ' . htmlspecialchars($e->getMessage());
}
