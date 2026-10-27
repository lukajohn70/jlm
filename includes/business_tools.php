<?php

if (!function_exists('jlm_business_random_token')) {
    function jlm_business_random_token($length = 32) {
        $length = max(16, (int) $length);
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }
}

if (!function_exists('jlm_base_url')) {
    function jlm_base_url() {
        $siteUrl = getenv('JLM_SITE_URL') ?: 'https://jlm.com.ng';
        if (function_exists('normalizeJLMConfigUrl')) {
            $siteUrl = normalizeJLMConfigUrl($siteUrl);
        } else {
            $siteUrl = preg_replace('/\s+#.*$/', '', trim((string) $siteUrl));
            $siteUrl = rtrim((string) preg_replace('/\s+/', '', (string) $siteUrl), '/');
        }

        return $siteUrl !== '' ? $siteUrl : 'https://jlm.com.ng';
    }
}

if (!function_exists('jlm_business_slug')) {
    function jlm_business_slug($text) {
        $text = strtolower(trim((string) $text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        $text = trim((string) $text, '-');
        return $text !== '' ? $text : 'item';
    }
}

if (!function_exists('jlm_currency_symbol')) {
    function jlm_currency_symbol($currency) {
        $currency = strtoupper(trim((string) $currency));
        $map = [
            'NGN' => 'NGN',
            'USD' => '$',
            'GBP' => 'GBP',
            'EUR' => 'EUR',
        ];

        return $map[$currency] ?? $currency;
    }
}

if (!function_exists('jlm_money')) {
    function jlm_money($amount, $currency = 'NGN') {
        return jlm_currency_symbol($currency) . ' ' . number_format((float) $amount, 2);
    }
}

if (!function_exists('jlm_get_invoice_profile')) {
    function jlm_get_invoice_profile() {
        return [
            'business_name' => getenv('JLM_INVOICE_BUSINESS_NAME') ?: 'JOHN LUKA MEDIA',
            'display_name' => getenv('JLM_INVOICE_DISPLAY_NAME') ?: 'John Luka Media',
            'contact_phone' => getenv('JLM_INVOICE_PHONE') ?: '08150917741',
            'contact_email' => getenv('JLM_INVOICE_EMAIL') ?: 'john@jlm.com.ng',
            'contact_website' => preg_replace('#^https?://#', '', jlm_base_url()),
            'bank_name' => getenv('JLM_BANK_NAME') ?: 'Moniepoint MFB',
            'bank_account_number' => getenv('JLM_BANK_ACCOUNT_NUMBER') ?: '6752058341',
            'bank_account_name' => getenv('JLM_BANK_ACCOUNT_NAME') ?: 'John Luka Media',
        ];
    }
}

if (!function_exists('jlm_get_persistent_signature_path')) {
    function jlm_get_persistent_signature_path(PDO $pdo) {
        if (!jlm_table_has_column($pdo, 'invoices', 'signature_path')) {
            return null;
        }

        $stmt = $pdo->query('SELECT signature_path FROM invoices WHERE signature_path IS NOT NULL AND signature_path <> "" ORDER BY updated_at DESC, id DESC LIMIT 1');
        $path = $stmt ? $stmt->fetchColumn() : null;

        return $path ? (string) $path : null;
    }
}

if (!function_exists('jlm_paystack_public_key')) {
    function jlm_paystack_public_key() {
        return trim((string) (getenv('JLM_PAYSTACK_PUBLIC_KEY') ?: ''));
    }
}

if (!function_exists('jlm_paystack_secret_key')) {
    function jlm_paystack_secret_key() {
        return trim((string) (getenv('JLM_PAYSTACK_SECRET_KEY') ?: ''));
    }
}

if (!function_exists('jlm_paystack_enabled')) {
    function jlm_paystack_enabled() {
        return jlm_paystack_public_key() !== '' && jlm_paystack_secret_key() !== '';
    }
}

if (!function_exists('jlm_generate_unique_reference')) {
    function jlm_generate_unique_reference(PDO $pdo, $table, $column, $prefix) {
        $prefix = strtoupper(trim((string) $prefix));

        do {
            $reference = $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
            $stmt->execute([$reference]);
        } while ((int) $stmt->fetchColumn() > 0);

        return $reference;
    }
}

if (!function_exists('jlm_table_has_column')) {
    function jlm_table_has_column(PDO $pdo, $table, $column) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ");
        $stmt->execute([(string) $table, (string) $column]);
        return (int) $stmt->fetchColumn() > 0;
    }
}

if (!function_exists('jlm_add_column_if_missing')) {
    function jlm_add_column_if_missing(PDO $pdo, $table, $column, $definition) {
        if (!jlm_table_has_column($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}

if (!function_exists('jlm_column_contains')) {
    function jlm_column_contains(PDO $pdo, $table, $column, $needle) {
        $stmt = $pdo->prepare("
            SELECT COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
            LIMIT 1
        ");
        $stmt->execute([(string) $table, (string) $column]);
        $columnType = (string) $stmt->fetchColumn();

        return stripos($columnType, (string) $needle) !== false;
    }
}

if (!function_exists('jlm_normalize_questionnaire_fields')) {
    function jlm_normalize_questionnaire_fields(array $post) {
        $labels = $post['field_label'] ?? [];
        $types = $post['field_type'] ?? [];
        $placeholders = $post['field_placeholder'] ?? [];
        $helps = $post['field_help'] ?? [];
        $options = $post['field_options'] ?? [];
        $required = $post['field_required'] ?? [];

        $fields = [];
        foreach ($labels as $index => $label) {
            $label = trim((string) $label);
            if ($label === '') {
                continue;
            }

            $type = trim((string) ($types[$index] ?? 'short_text'));
            $allowedTypes = ['short_text', 'long_text', 'email', 'phone', 'number', 'date', 'single_choice', 'multiple_choice', 'dropdown', 'file_upload'];
            if (!in_array($type, $allowedTypes, true)) {
                $type = 'short_text';
            }

            $optionLines = preg_split('/\r\n|\r|\n/', (string) ($options[$index] ?? ''));
            $cleanOptions = [];
            foreach ($optionLines as $line) {
                $line = trim((string) $line);
                if ($line !== '') {
                    $cleanOptions[] = $line;
                }
            }

            $fields[] = [
                'label' => $label,
                'type' => $type,
                'placeholder' => trim((string) ($placeholders[$index] ?? '')),
                'help_text' => trim((string) ($helps[$index] ?? '')),
                'options' => $cleanOptions,
                'is_required' => isset($required[$index]) ? 1 : 0,
            ];
        }

        return $fields;
    }
}

if (!function_exists('jlm_normalize_invoice_items')) {
    function jlm_normalize_invoice_items(array $post) {
        $descriptions = $post['item_description'] ?? [];
        $quantities = $post['item_quantity'] ?? [];
        $prices = $post['item_unit_price'] ?? [];
        $amounts = $post['item_amount'] ?? [];
        $percentages = $post['item_percentage'] ?? [];

        $items = [];
        foreach ($descriptions as $index => $description) {
            $description = trim((string) $description);
            if ($description === '') {
                continue;
            }

            $quantity = (float) ($quantities[$index] ?? 0);
            $unitPrice = (float) ($prices[$index] ?? ($amounts[$index] ?? 0));
            $lineTotal = isset($amounts[$index]) && $amounts[$index] !== '' ? (float) $amounts[$index] : 0.0;
            $percentageValue = isset($percentages[$index]) && $percentages[$index] !== '' ? (float) $percentages[$index] : null;
            if ($quantity <= 0) {
                $quantity = 1;
            }
            if ($lineTotal <= 0) {
                $lineTotal = $quantity * $unitPrice;
            }
            if ($unitPrice <= 0 && $lineTotal > 0) {
                $unitPrice = $lineTotal;
            }

            $items[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'percentage_value' => $percentageValue,
            ];
        }

        return $items;
    }
}

if (!function_exists('jlm_calculate_invoice_totals')) {
    function jlm_calculate_invoice_totals(array $items, $discountAmount, $taxAmount, $amountPaid = 0.0) {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['line_total'] ?? 0);
        }

        $discountAmount = max(0, (float) $discountAmount);
        $taxAmount = max(0, (float) $taxAmount);
        $amountPaid = max(0, (float) $amountPaid);
        $total = max(0, $subtotal - $discountAmount + $taxAmount);
        $balance = max(0, $total - $amountPaid);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'amount_paid' => min($amountPaid, $total),
            'balance_due' => $balance,
        ];
    }
}

if (!function_exists('jlm_invoice_status_from_balance')) {
    function jlm_invoice_status_from_balance($balanceDue, $amountPaid, $currentStatus = 'draft') {
        $balanceDue = (float) $balanceDue;
        $amountPaid = (float) $amountPaid;
        $currentStatus = trim((string) $currentStatus);

        if ($currentStatus === 'cancelled') {
            return 'cancelled';
        }
        if ($balanceDue <= 0.009) {
            return 'paid';
        }
        if ($amountPaid > 0) {
            return 'part_paid';
        }
        if ($currentStatus === 'sent') {
            return 'sent';
        }

        return 'draft';
    }
}

if (!function_exists('jlm_ensure_business_tools_schema')) {
    function jlm_ensure_business_tools_schema(PDO $pdo) {
        static $done = false;
        if ($done) {
            return;
        }

        $queries = [
            "CREATE TABLE IF NOT EXISTS questionnaires (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                description TEXT NULL,
                intro_message TEXT NULL,
                thank_you_message TEXT NULL,
                status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
                created_by INT NULL,
                updated_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                published_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS questionnaire_fields (
                id INT AUTO_INCREMENT PRIMARY KEY,
                questionnaire_id INT NOT NULL,
                label VARCHAR(255) NOT NULL,
                field_name VARCHAR(255) NOT NULL,
                field_type ENUM('short_text','long_text','email','phone','number','date','single_choice','multiple_choice','dropdown','file_upload') NOT NULL DEFAULT 'short_text',
                placeholder VARCHAR(255) NULL,
                help_text VARCHAR(255) NULL,
                field_options TEXT NULL,
                is_required TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_questionnaire_fields_questionnaire (questionnaire_id),
                CONSTRAINT fk_questionnaire_fields_questionnaire FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS questionnaire_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                questionnaire_id INT NOT NULL,
                client_name VARCHAR(255) NOT NULL,
                client_email VARCHAR(255) NOT NULL,
                access_token VARCHAR(80) NOT NULL UNIQUE,
                status ENUM('draft','sent','responded','archived') NOT NULL DEFAULT 'draft',
                email_subject VARCHAR(255) NULL,
                email_message TEXT NULL,
                sent_at DATETIME NULL,
                responded_at DATETIME NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_questionnaire_requests_questionnaire (questionnaire_id),
                INDEX idx_questionnaire_requests_status (status),
                CONSTRAINT fk_questionnaire_requests_questionnaire FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS questionnaire_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                questionnaire_id INT NOT NULL,
                request_id INT NULL,
                respondent_name VARCHAR(255) NULL,
                respondent_email VARCHAR(255) NULL,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                INDEX idx_questionnaire_responses_questionnaire (questionnaire_id),
                INDEX idx_questionnaire_responses_request (request_id),
                CONSTRAINT fk_questionnaire_responses_questionnaire FOREIGN KEY (questionnaire_id) REFERENCES questionnaires(id) ON DELETE CASCADE,
                CONSTRAINT fk_questionnaire_responses_request FOREIGN KEY (request_id) REFERENCES questionnaire_requests(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS questionnaire_response_answers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                response_id INT NOT NULL,
                field_id INT NOT NULL,
                answer_text LONGTEXT NULL,
                INDEX idx_questionnaire_answers_response (response_id),
                INDEX idx_questionnaire_answers_field (field_id),
                CONSTRAINT fk_questionnaire_answers_response FOREIGN KEY (response_id) REFERENCES questionnaire_responses(id) ON DELETE CASCADE,
                CONSTRAINT fk_questionnaire_answers_field FOREIGN KEY (field_id) REFERENCES questionnaire_fields(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_number VARCHAR(50) NOT NULL UNIQUE,
                public_token VARCHAR(80) NOT NULL UNIQUE,
                client_name VARCHAR(255) NOT NULL,
                client_email VARCHAR(255) NOT NULL,
                client_phone VARCHAR(100) NULL,
                client_company VARCHAR(255) NULL,
                client_address TEXT NULL,
                issue_date DATE NOT NULL,
                due_date DATE NULL,
                currency VARCHAR(10) NOT NULL DEFAULT 'NGN',
                notes TEXT NULL,
                terms TEXT NULL,
                subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                balance_due DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                status ENUM('draft','sent','part_paid','paid','cancelled') NOT NULL DEFAULT 'draft',
                created_by INT NULL,
                updated_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                sent_at DATETIME NULL,
                paid_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS invoice_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT NOT NULL,
                description VARCHAR(255) NOT NULL,
                quantity DECIMAL(12,2) NOT NULL DEFAULT 1.00,
                unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                line_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                sort_order INT NOT NULL DEFAULT 0,
                INDEX idx_invoice_items_invoice (invoice_id),
                CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS invoice_payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                invoice_id INT NOT NULL,
                receipt_number VARCHAR(50) NOT NULL UNIQUE,
                public_token VARCHAR(80) NOT NULL UNIQUE,
                payment_date DATE NOT NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                payment_method VARCHAR(100) NULL,
                payment_reference VARCHAR(255) NULL,
                notes TEXT NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_invoice_payments_invoice (invoice_id),
                CONSTRAINT fk_invoice_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($queries as $query) {
            $pdo->exec($query);
        }

        jlm_add_column_if_missing($pdo, 'invoices', 'total_contract_value', "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER currency");
        jlm_add_column_if_missing($pdo, 'invoices', 'payment_scope', "ENUM('full','part') NOT NULL DEFAULT 'full' AFTER total_contract_value");
        jlm_add_column_if_missing($pdo, 'invoices', 'signature_path', "VARCHAR(255) NULL AFTER total_contract_value");
        jlm_add_column_if_missing($pdo, 'invoice_items', 'percentage_value', "DECIMAL(8,2) NULL AFTER description");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'status', "ENUM('pending','verified','failed') NOT NULL DEFAULT 'verified' AFTER amount");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'payment_channel', "VARCHAR(50) NULL AFTER payment_method");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'gateway_reference', "VARCHAR(255) NULL AFTER payment_reference");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'gateway_status', "VARCHAR(100) NULL AFTER gateway_reference");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'verification_payload', "LONGTEXT NULL AFTER gateway_status");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'verified_at', "DATETIME NULL AFTER verification_payload");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'verified_by', "INT NULL AFTER verified_at");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'payer_name', "VARCHAR(255) NULL AFTER public_token");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'receipt_upload_path', "VARCHAR(255) NULL AFTER payment_reference");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'completion_marked_at', "DATETIME NULL AFTER verified_by");
        jlm_add_column_if_missing($pdo, 'invoice_payments', 'completion_marked_by', "INT NULL AFTER completion_marked_at");

        if (!jlm_column_contains($pdo, 'questionnaire_fields', 'field_type', 'file_upload')) {
            $pdo->exec("ALTER TABLE questionnaire_fields MODIFY COLUMN field_type ENUM('short_text','long_text','email','phone','number','date','single_choice','multiple_choice','dropdown','file_upload') NOT NULL DEFAULT 'short_text'");
        }

        $done = true;
    }
}

if (!function_exists('jlm_get_questionnaire')) {
    function jlm_get_questionnaire(PDO $pdo, $questionnaireId) {
        $stmt = $pdo->prepare('SELECT * FROM questionnaires WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $questionnaireId]);
        $questionnaire = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$questionnaire) {
            return null;
        }

        $fieldStmt = $pdo->prepare('SELECT * FROM questionnaire_fields WHERE questionnaire_id = ? ORDER BY sort_order ASC, id ASC');
        $fieldStmt->execute([(int) $questionnaireId]);
        $questionnaire['fields'] = $fieldStmt->fetchAll(PDO::FETCH_ASSOC);

        return $questionnaire;
    }
}

if (!function_exists('jlm_get_invoice_bundle')) {
    function jlm_get_invoice_bundle(PDO $pdo, $invoiceId) {
        $stmt = $pdo->prepare('SELECT * FROM invoices WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $invoiceId]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invoice) {
            return null;
        }

        $itemStmt = $pdo->prepare('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order ASC, id ASC');
        $itemStmt->execute([(int) $invoiceId]);
        $invoice['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

        $paymentStmt = $pdo->prepare('SELECT * FROM invoice_payments WHERE invoice_id = ? ORDER BY payment_date DESC, id DESC');
        $paymentStmt->execute([(int) $invoiceId]);
        $invoice['payments'] = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
        $invoice['verified_payments'] = array_values(array_filter($invoice['payments'], static function ($payment) {
            return ($payment['status'] ?? 'verified') === 'verified';
        }));
        $invoice['pending_payments'] = array_values(array_filter($invoice['payments'], static function ($payment) {
            return ($payment['status'] ?? '') === 'pending';
        }));

        return $invoice;
    }
}

if (!function_exists('jlm_upload_invoice_signature')) {
    function jlm_upload_invoice_signature(array $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedTypes[$mimeType])) {
            throw new RuntimeException('Signature must be a JPG, PNG, or WebP image.');
        }

        if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
            throw new RuntimeException('Signature image is too large. Maximum size is 3MB.');
        }

        $uploadDir = __DIR__ . '/../uploads/signatures/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Unable to create the signature upload folder.');
        }

        $filename = 'signature_' . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $allowedTypes[$mimeType];
        $targetPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Failed to save the uploaded signature.');
        }

        return 'uploads/signatures/' . $filename;
    }
}

if (!function_exists('jlm_upload_business_file')) {
    function jlm_upload_business_file(array $file, $targetDir, array $allowedTypes, $maxSizeBytes, $prefix, $errorLabel) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($file['tmp_name']);
        if (!isset($allowedTypes[$mimeType])) {
            throw new RuntimeException($errorLabel);
        }

        if (($file['size'] ?? 0) > $maxSizeBytes) {
            throw new RuntimeException('Uploaded file is too large.');
        }

        $uploadDir = __DIR__ . '/../' . trim($targetDir, '/') . '/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Unable to create the upload folder.');
        }

        $filename = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(5)) . '.' . $allowedTypes[$mimeType];
        $targetPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new RuntimeException('Failed to save the uploaded file.');
        }

        return trim($targetDir, '/') . '/' . $filename;
    }
}

if (!function_exists('jlm_upload_questionnaire_document')) {
    function jlm_upload_questionnaire_document(array $file) {
        return jlm_upload_business_file(
            $file,
            'uploads/questionnaire-documents',
            [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/vnd.ms-powerpoint' => 'ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                'application/zip' => 'zip',
                'application/x-zip-compressed' => 'zip',
            ],
            10 * 1024 * 1024,
            'questionnaire_doc',
            'Document must be a JPG, PNG, WebP, PDF, Word, PowerPoint, or ZIP file.'
        );
    }
}

if (!function_exists('jlm_upload_payment_receipt')) {
    function jlm_upload_payment_receipt(array $file) {
        return jlm_upload_business_file(
            $file,
            'uploads/payment-receipts',
            [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
            ],
            8 * 1024 * 1024,
            'payment_receipt',
            'Receipt proof must be a JPG, PNG, WebP, or PDF file.'
        );
    }
}

if (!function_exists('jlm_sync_invoice_payment_state')) {
    function jlm_sync_invoice_payment_state(PDO $pdo, $invoiceId, $updatedBy = null) {
        $invoice = jlm_get_invoice_bundle($pdo, $invoiceId);
        if (!$invoice) {
            return null;
        }

        $verifiedAmount = 0.0;
        foreach ($invoice['payments'] as $payment) {
            if (($payment['status'] ?? 'verified') === 'verified') {
                $verifiedAmount += (float) $payment['amount'];
            }
        }

        $totals = jlm_calculate_invoice_totals($invoice['items'], $invoice['discount_amount'], $invoice['tax_amount'], $verifiedAmount);
        $status = jlm_invoice_status_from_balance($totals['balance_due'], $totals['amount_paid'], $invoice['status']);

        $params = [
            $totals['subtotal'],
            $totals['amount_paid'],
            $totals['balance_due'],
            $status,
            $status,
        ];
        $sql = 'UPDATE invoices SET subtotal = ?, amount_paid = ?, balance_due = ?, status = ?, paid_at = CASE WHEN ? = "paid" THEN NOW() ELSE paid_at END';
        if ($updatedBy !== null) {
            $sql .= ', updated_by = ?';
            $params[] = $updatedBy;
        }
        $sql .= ' WHERE id = ?';
        $params[] = $invoiceId;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return jlm_get_invoice_bundle($pdo, $invoiceId);
    }
}

if (!function_exists('jlm_create_invoice_payment')) {
    function jlm_create_invoice_payment(PDO $pdo, array $data) {
        $stmt = $pdo->prepare('
            INSERT INTO invoice_payments (
                invoice_id, receipt_number, public_token, payer_name, payment_date, amount, status, payment_method,
                payment_channel, payment_reference, gateway_reference, gateway_status, verification_payload, notes,
                receipt_upload_path, verified_at, verified_by, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $data['invoice_id'],
            $data['receipt_number'],
            $data['public_token'],
            $data['payer_name'] ?? null,
            $data['payment_date'],
            $data['amount'],
            $data['status'] ?? 'verified',
            $data['payment_method'] ?? null,
            $data['payment_channel'] ?? null,
            $data['payment_reference'] ?? null,
            $data['gateway_reference'] ?? null,
            $data['gateway_status'] ?? null,
            $data['verification_payload'] ?? null,
            $data['notes'] ?? null,
            $data['receipt_upload_path'] ?? null,
            $data['verified_at'] ?? null,
            $data['verified_by'] ?? null,
            $data['created_by'] ?? null,
        ]);

        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('jlm_verify_invoice_payment')) {
    function jlm_verify_invoice_payment(PDO $pdo, $paymentId, $adminId, $verificationPayload = null, $gatewayStatus = 'verified') {
        $stmt = $pdo->prepare('SELECT * FROM invoice_payments WHERE id = ? LIMIT 1');
        $stmt->execute([(int) $paymentId]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            throw new RuntimeException('Payment record not found.');
        }

        if (($payment['status'] ?? '') !== 'verified') {
            $pdo->prepare('UPDATE invoice_payments SET status = "verified", gateway_status = ?, verification_payload = ?, verified_at = NOW(), verified_by = ? WHERE id = ?')->execute([
                $gatewayStatus,
                $verificationPayload,
                $adminId,
                $paymentId,
            ]);
        }

        return jlm_sync_invoice_payment_state($pdo, (int) $payment['invoice_id'], $adminId);
    }
}

if (!function_exists('jlm_mark_invoice_complete')) {
    function jlm_mark_invoice_complete(PDO $pdo, $invoiceId, $adminId = null) {
        $invoice = jlm_get_invoice_bundle($pdo, $invoiceId);
        if (!$invoice) {
            throw new RuntimeException('Invoice not found.');
        }

        $balance = max(0, (float) $invoice['balance_due']);
        if ($balance <= 0.009) {
            return $invoice;
        }

        $paymentId = jlm_create_invoice_payment($pdo, [
            'invoice_id' => $invoice['id'],
            'receipt_number' => jlm_generate_unique_reference($pdo, 'invoice_payments', 'receipt_number', 'RCT'),
            'public_token' => jlm_business_random_token(40),
            'payer_name' => $invoice['client_name'],
            'payment_date' => date('Y-m-d'),
            'amount' => $balance,
            'status' => 'verified',
            'payment_method' => 'Completion adjustment',
            'payment_channel' => 'manual_completion',
            'gateway_status' => 'completed',
            'notes' => 'Invoice manually marked complete.',
            'verified_at' => date('Y-m-d H:i:s'),
            'verified_by' => $adminId,
            'created_by' => $adminId,
        ]);

        $pdo->prepare('UPDATE invoice_payments SET completion_marked_at = NOW(), completion_marked_by = ? WHERE id = ?')->execute([
            $adminId,
            $paymentId,
        ]);

        return jlm_verify_invoice_payment($pdo, $paymentId, $adminId, null, 'completed');
    }
}

if (!function_exists('jlm_send_admin_questionnaire_submission_notice')) {
    function jlm_send_admin_questionnaire_submission_notice(array $questionnaire, array $request, array $answers) {
        require_once __DIR__ . '/../config/email_utils.php';

        $rows = '';
        foreach ($answers as $label => $answer) {
            $display = filter_var((string) $answer, FILTER_VALIDATE_URL)
                ? '<a href="' . htmlspecialchars((string) $answer) . '" style="color:#1b2299;text-decoration:underline;">Open uploaded file</a>'
                : nl2br(htmlspecialchars((string) $answer));
            $rows .= '<tr><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;width:35%;">' . htmlspecialchars($label) . '</td><td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#111827;">' . $display . '</td></tr>';
        }

        $content = '
        <p style="margin-top:0;">A questionnaire response was submitted for <strong>' . htmlspecialchars($questionnaire['title']) . '</strong>.</p>
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:20px 0;">
            <div><strong>Client:</strong> ' . htmlspecialchars($request['client_name']) . '</div>
            <div><strong>Email:</strong> ' . htmlspecialchars($request['client_email']) . '</div>
        </div>
        <table style="width:100%;border-collapse:collapse;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
            <tbody>' . $rows . '</tbody>
        </table>';

        return sendEmail(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@jlm.com.ng', 'Questionnaire submitted - ' . $questionnaire['title'], getCompleteEmailTemplate('New Questionnaire Response', $content));
    }
}

if (!function_exists('jlm_send_admin_payment_notice')) {
    function jlm_send_admin_payment_notice(array $invoice, array $payment, $verified = true) {
        require_once __DIR__ . '/../config/email_utils.php';

        $content = '
        <p style="margin-top:0;">' . ($verified ? 'A payment has been confirmed for an invoice.' : 'A bank transfer confirmation has been submitted and is awaiting verification.') . '</p>
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:20px 0;">
            <div><strong>Invoice:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</div>
            <div><strong>Client:</strong> ' . htmlspecialchars($invoice['client_name']) . ' (' . htmlspecialchars($invoice['client_email']) . ')</div>
            <div><strong>Amount:</strong> ' . htmlspecialchars(jlm_money($payment['amount'], $invoice['currency'])) . '</div>
            <div><strong>Method:</strong> ' . htmlspecialchars($payment['payment_method'] ?: ($payment['payment_channel'] ?? 'Payment')) . '</div>
            <div><strong>Reference:</strong> ' . htmlspecialchars($payment['gateway_reference'] ?: ($payment['payment_reference'] ?: 'N/A')) . '</div>
            ' . (!empty($payment['receipt_upload_path']) ? '<div><strong>Receipt Proof:</strong> <a href="' . htmlspecialchars(jlm_base_url() . '/' . ltrim($payment['receipt_upload_path'], '/')) . '" style="color:#1b2299;text-decoration:underline;">Open uploaded proof</a></div>' : '') . '
        </div>';

        return sendEmail(defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'support@jlm.com.ng', $verified ? 'Invoice paid - ' . $invoice['invoice_number'] : 'Transfer confirmation pending - ' . $invoice['invoice_number'], getCompleteEmailTemplate($verified ? 'Invoice Payment Confirmed' : 'Transfer Confirmation Pending', $content));
    }
}

if (!function_exists('jlm_paystack_request')) {
    function jlm_paystack_request($method, $path, array $payload = []) {
        if (!jlm_paystack_enabled()) {
            throw new RuntimeException('Paystack keys are not configured.');
        }

        $url = 'https://api.paystack.co' . $path;
        $headers = [
            'Authorization: Bearer ' . jlm_paystack_secret_key(),
            'Content-Type: application/json',
        ];

        $method = strtoupper((string) $method);
        $rawResponse = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
            $rawResponse = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            if ($rawResponse === false) {
                throw new RuntimeException('Paystack request failed: ' . $curlError);
            }
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => implode("\r\n", $headers),
                    'content' => $method !== 'GET' ? json_encode($payload) : '',
                    'ignore_errors' => true,
                ],
            ]);
            $rawResponse = @file_get_contents($url, false, $context);
            $httpCode = 200;
        }

        $decoded = json_decode((string) $rawResponse, true);
        if (!is_array($decoded) || empty($decoded['status'])) {
            $message = is_array($decoded) ? ($decoded['message'] ?? 'Unknown Paystack error') : 'Unexpected Paystack response';
            throw new RuntimeException('Paystack error: ' . $message);
        }

        return $decoded;
    }
}

if (!function_exists('jlm_initialize_paystack_transaction')) {
    function jlm_initialize_paystack_transaction(array $invoice, array $metadata = []) {
        $reference = 'PSK-' . preg_replace('/[^A-Za-z0-9\-]/', '', $invoice['invoice_number']) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $callbackUrl = jlm_base_url() . '/billing_paystack_callback.php?token=' . rawurlencode($invoice['public_token']);
        $payload = [
            'email' => $invoice['client_email'],
            'amount' => (string) round(((float) $invoice['balance_due']) * 100),
            'currency' => $invoice['currency'],
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => json_encode(array_merge($metadata, [
                'invoice_id' => (int) $invoice['id'],
                'invoice_number' => $invoice['invoice_number'],
            ])),
        ];

        $response = jlm_paystack_request('POST', '/transaction/initialize', $payload);
        return [
            'reference' => $reference,
            'authorization_url' => $response['data']['authorization_url'] ?? '',
            'access_code' => $response['data']['access_code'] ?? '',
            'payload' => $response,
        ];
    }
}

if (!function_exists('jlm_verify_paystack_transaction')) {
    function jlm_verify_paystack_transaction($reference) {
        return jlm_paystack_request('GET', '/transaction/verify/' . rawurlencode((string) $reference));
    }
}

if (!function_exists('jlm_send_questionnaire_email')) {
    function jlm_send_questionnaire_email($recipientEmail, $recipientName, $questionnaireTitle, $questionnaireUrl, $customMessage = '') {
        require_once __DIR__ . '/../config/email_utils.php';

        $content = '
        <p style="margin-top:0;">Dear ' . htmlspecialchars($recipientName) . ',</p>
        <p>Please complete the questionnaire for <strong>' . htmlspecialchars($questionnaireTitle) . '</strong>. This will help us collect the details we need for your project.</p>
        ' . ($customMessage !== '' ? '<div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:20px 0;">' . nl2br(htmlspecialchars($customMessage)) . '</div>' : '') . '
        <p style="margin:24px 0;">
            <a href="' . htmlspecialchars($questionnaireUrl) . '" style="display:inline-block;background:linear-gradient(135deg,#1b2299,#e4306d);color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700;">
                Open Questionnaire
            </a>
        </p>
        <p>If the button above does not work, use this link:</p>
        <p style="word-break:break-word;color:#1b2299;">' . htmlspecialchars($questionnaireUrl) . '</p>
        <p>Warm regards,<br><strong>The JLM Team</strong></p>';

        return sendEmail(
            $recipientEmail,
            'Questionnaire: ' . $questionnaireTitle,
            getCompleteEmailTemplate('Project Questionnaire', $content)
        );
    }
}

if (!function_exists('jlm_send_invoice_email')) {
    function jlm_send_invoice_email(array $invoice, $invoiceUrl) {
        require_once __DIR__ . '/../config/email_utils.php';

        $content = '
        <p style="margin-top:0;">Dear ' . htmlspecialchars($invoice['client_name']) . ',</p>
        <p>Your invoice <strong>' . htmlspecialchars($invoice['invoice_number']) . '</strong> is ready.</p>
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:20px 0;">
            <div><strong>Issue Date:</strong> ' . htmlspecialchars(date('F j, Y', strtotime($invoice['issue_date']))) . '</div>
            <div><strong>Due Date:</strong> ' . htmlspecialchars($invoice['due_date'] ? date('F j, Y', strtotime($invoice['due_date'])) : 'On receipt') . '</div>
            <div><strong>Total:</strong> ' . htmlspecialchars(jlm_money($invoice['total_amount'], $invoice['currency'])) . '</div>
            <div><strong>Balance Due:</strong> ' . htmlspecialchars(jlm_money($invoice['balance_due'], $invoice['currency'])) . '</div>
        </div>
        <p style="margin:24px 0;">
            <a href="' . htmlspecialchars($invoiceUrl) . '" style="display:inline-block;background:linear-gradient(135deg,#1b2299,#e4306d);color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700;">
                View Invoice
            </a>
        </p>
        <p>If the button above does not work, use this link:</p>
        <p style="word-break:break-word;color:#1b2299;">' . htmlspecialchars($invoiceUrl) . '</p>
        <p>Warm regards,<br><strong>The JLM Team</strong></p>';

        return sendEmail(
            $invoice['client_email'],
            'Invoice ' . $invoice['invoice_number'] . ' from JLM',
            getCompleteEmailTemplate('Invoice Ready', $content)
        );
    }
}

if (!function_exists('jlm_send_receipt_email')) {
    function jlm_send_receipt_email(array $invoice, array $payment, $receiptUrl) {
        require_once __DIR__ . '/../config/email_utils.php';

        $content = '
        <p style="margin-top:0;">Dear ' . htmlspecialchars($invoice['client_name']) . ',</p>
        <p>Thank you for your payment. Your receipt <strong>' . htmlspecialchars($payment['receipt_number']) . '</strong> is ready.</p>
        <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:20px 0;">
            <div><strong>Invoice:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</div>
            <div><strong>Payment Date:</strong> ' . htmlspecialchars(date('F j, Y', strtotime($payment['payment_date']))) . '</div>
            <div><strong>Amount Received:</strong> ' . htmlspecialchars(jlm_money($payment['amount'], $invoice['currency'])) . '</div>
        </div>
        <p style="margin:24px 0;">
            <a href="' . htmlspecialchars($receiptUrl) . '" style="display:inline-block;background:linear-gradient(135deg,#1b2299,#e4306d);color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700;">
                View Receipt
            </a>
        </p>
        <p>If the button above does not work, use this link:</p>
        <p style="word-break:break-word;color:#1b2299;">' . htmlspecialchars($receiptUrl) . '</p>
        <p>Warm regards,<br><strong>The JLM Team</strong></p>';

        return sendEmail(
            $invoice['client_email'],
            'Receipt ' . $payment['receipt_number'] . ' from JLM',
            getCompleteEmailTemplate('Payment Receipt', $content)
        );
    }
}
