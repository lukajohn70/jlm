<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';
require_once __DIR__ . '/includes/functions.php';

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
    exit('Online payment is not configured yet.');
}

if ((float) $invoice['balance_due'] <= 0) {
    header('Location: ' . jlm_base_url() . '/billing_document.php?type=invoice&token=' . rawurlencode($invoice['public_token']));
    exit;
}

$callbackUrl = jlm_base_url() . '/billing_paystack_callback.php?token=' . rawurlencode($invoice['public_token']);
$returnUrl = jlm_base_url() . '/billing_document.php?type=invoice&token=' . rawurlencode($invoice['public_token']);
$logoUrl = asset_url('assets/images/jlm.png');
$initialized = null;

try {
    $initialized = jlm_initialize_paystack_transaction($invoice, [
        'source' => 'billing_pay_online',
    ]);

    jlm_create_invoice_payment($pdo, [
        'invoice_id' => $invoice['id'],
        'receipt_number' => jlm_generate_unique_reference($pdo, 'invoice_payments', 'receipt_number', 'RCT'),
        'public_token' => jlm_business_random_token(40),
        'payer_name' => $invoice['client_name'],
        'payment_date' => date('Y-m-d'),
        'amount' => (float) $invoice['balance_due'],
        'status' => 'pending',
        'payment_method' => 'Online payment',
        'payment_channel' => 'paystack',
        'payment_reference' => $initialized['reference'],
        'gateway_reference' => $initialized['reference'],
        'gateway_status' => 'initialized',
        'verification_payload' => json_encode($initialized['payload']),
        'notes' => 'Initialized from branded online payment page',
        'created_by' => null,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Unable to start online payment: ' . htmlspecialchars($e->getMessage()));
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment | JLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[linear-gradient(180deg,#eef3ff_0%,#f9fbff_55%,#ffffff_100%)] text-slate-900">
    <main class="max-w-5xl mx-auto px-4 py-8">
        <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-[32px] overflow-hidden shadow-2xl bg-white border border-slate-200">
                <div class="bg-gradient-to-r from-[#2f33a8] via-[#5630a7] to-[#e73072] px-8 py-8 text-white">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo htmlspecialchars($logoUrl); ?>" alt="JLM Logo" class="h-14 w-14 rounded-2xl bg-white/10 p-2 object-contain">
                        <div>
                            <div class="text-sm uppercase tracking-[0.24em] text-white/75">Secure Checkout</div>
                            <h1 class="text-3xl font-bold mt-2">Complete Your Payment</h1>
                        </div>
                    </div>
                    <p class="mt-5 max-w-2xl text-white/85">You are paying the full outstanding amount for this invoice through our secure online payment channel.</p>
                </div>

                <div class="px-8 py-8 space-y-6">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Invoice</div>
                                <div class="mt-2 text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Client</div>
                                <div class="mt-2 text-lg font-semibold text-slate-900"><?php echo htmlspecialchars($invoice['client_name']); ?></div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Amount</div>
                                <div class="mt-2 text-lg font-semibold text-slate-900"><?php echo htmlspecialchars(jlm_money($invoice['balance_due'], $invoice['currency'])); ?></div>
                            </div>
                            <div>
                                <div class="text-xs uppercase tracking-[0.18em] text-slate-500">Reference</div>
                                <div class="mt-2 text-sm font-semibold text-slate-700 break-all"><?php echo htmlspecialchars($initialized['reference']); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <a href="<?php echo htmlspecialchars($initialized['authorization_url']); ?>" class="inline-flex items-center gap-3 px-6 py-4 rounded-2xl bg-gradient-to-r from-[#2f33a8] to-[#e73072] text-white font-semibold shadow-lg hover:opacity-95 transition">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2.5" y="5.5" width="19" height="13" rx="2"></rect>
                                <path d="M2.5 10h19"></path>
                            </svg>
                            <span>Continue to Payment</span>
                        </a>
                        <a href="<?php echo htmlspecialchars($returnUrl); ?>" class="inline-flex items-center gap-3 px-6 py-4 rounded-2xl bg-white border border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                            <span>Back to Invoice</span>
                        </a>
                    </div>
                </div>
            </section>

            <aside class="rounded-[32px] shadow-xl bg-white border border-slate-200 p-8">
                <h2 class="text-xl font-bold text-[#2f33a8]">What Happens Next</h2>
                <div class="mt-5 space-y-5 text-slate-600">
                    <div class="flex gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-2xl bg-[#eef2ff] text-[#2f33a8] flex items-center justify-center font-bold">1</div>
                        <p>Click <strong>Continue to Payment</strong> to open the secure payment popup.</p>
                    </div>
                    <div class="flex gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-2xl bg-[#fff0f6] text-[#e73072] flex items-center justify-center font-bold">2</div>
                        <p>Once payment succeeds, we verify it and update your invoice automatically.</p>
                    </div>
                    <div class="flex gap-4">
                        <div class="h-10 w-10 shrink-0 rounded-2xl bg-[#eefbf4] text-emerald-600 flex items-center justify-center font-bold">3</div>
                        <p>Your receipt opens immediately after successful verification.</p>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                    If the button does not open automatically in your browser, use this secure link:
                    <a href="<?php echo htmlspecialchars($initialized['authorization_url']); ?>" class="font-semibold text-[#2f33a8] underline break-all">Open payment</a>
                </div>
            </aside>
        </div>
    </main>
</body>
</html>
