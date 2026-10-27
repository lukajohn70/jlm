<?php
function normalizeJLMConfigUrl($url) {
    $url = trim((string) $url);
    $url = preg_replace('/\s+#.*$/', '', $url);
    $url = trim((string) $url);
    $url = preg_replace('/\s+/', '', $url);
    return rtrim((string) $url, '/');
}

function getJLMBranding() {
    $siteUrl = normalizeJLMConfigUrl(getenv('JLM_SITE_URL') ?: '');
    $basePath = getenv('JLM_BASE_PATH') ?: '';

    if ($siteUrl === '') {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host !== '') {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            if ($basePath === '' && $scriptDir !== '' && $scriptDir !== '/') {
                $basePath = $scriptDir;
            }
            $siteUrl = $scheme . '://' . $host . ($basePath ? '/' . ltrim($basePath, '/') : '');
        } else {
            $siteUrl = 'https://jlm.com.ng';
        }
    } else {
        $siteUrl = normalizeJLMConfigUrl($siteUrl);
        if ($basePath !== '') {
            $siteUrl .= '/' . ltrim($basePath, '/');
        }
    }

    $siteUrl = normalizeJLMConfigUrl($siteUrl);
    $contactEmail = getenv('JLM_FROM_EMAIL') ?: 'support@jlm.com.ng';
    return [
        'name' => 'JLM',
        'slogan' => 'Bringing Your Creative Vision to Life',
        'website' => $siteUrl,
        'website_label' => preg_replace('#^https?://#', '', $siteUrl),
        'email' => $contactEmail,
        'phone' => '+2348150917741',
        'logo' => $siteUrl . '/assets/images/jlm.png',
        'socials' => [
            'Instagram' => 'https://instagram.com/jlukamedia',
            'Facebook' => 'https://facebook.com/jlukamedia',
            'X' => 'https://x.com/jlukamedia',
            'LinkedIn' => 'https://linkedin.com/in/jlukamedia',
            'WhatsApp' => 'https://wa.me/2348150917741',
        ],
    ];
}

function isPublicEmailAssetUrl($url) {
    if (!$url) {
        return false;
    }

    $parts = parse_url((string) $url);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));

    if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
        return false;
    }

    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1') {
        return false;
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        return !preg_match('/^(10\.|127\.|192\.168\.|172\.(1[6-9]|2\d|3[0-1])\.)/', $host);
    }

    return true;
}

function getJLMLogo() {
    $brand = getJLMBranding();
    $logoUrl = $brand['logo'];

    if (isPublicEmailAssetUrl($logoUrl)) {
        return '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . htmlspecialchars($brand['name']) . ' Logo" width="88" style="width:88px;max-width:88px;height:auto;display:block;margin:0 auto 16px;border:0;outline:none;text-decoration:none;">';
    }

    return '
    <div style="display:inline-block;margin:0 auto 16px;padding:10px 18px;border-radius:999px;background:rgba(255,255,255,0.16);border:1px solid rgba(255,255,255,0.24);font-size:13px;letter-spacing:0.18em;font-weight:700;color:#ffffff;">
        ' . htmlspecialchars($brand['name']) . '
    </div>';
}

function getSocialMediaLinks() {
    $brand = getJLMBranding();
    $links = '';
    foreach ($brand['socials'] as $label => $url) {
        $links .= '<a href="' . htmlspecialchars($url) . '" style="display:inline-block;margin:4px 6px;color:#1b2299;text-decoration:none;font-weight:600;">' . htmlspecialchars($label) . '</a>';
    }

    return '<div style="text-align:center;margin-top:16px;">' . $links . '</div>';
}

function getEmailHeader($title = 'JLM') {
    $brand = getJLMBranding();
    return '
    <div style="background:linear-gradient(135deg,#1b2299 0%,#e4306d 100%);padding:36px 24px;text-align:center;color:#ffffff;">
        ' . getJLMLogo() . '
        <div style="font-size:30px;line-height:1.15;font-weight:800;margin-bottom:8px;">' . htmlspecialchars($title) . '</div>
        <div style="font-size:15px;line-height:1.6;opacity:0.95;">' . htmlspecialchars($brand['slogan']) . '</div>
    </div>';
}

function getEmailFooter() {
    $brand = getJLMBranding();
    $website = htmlspecialchars($brand['website']);
    $websiteLabel = htmlspecialchars($brand['website_label'] ?: $brand['website']);
    return '
    <div style="background:#f7f8fb;padding:28px 24px;border-top:1px solid #e5e7eb;">
        <div style="text-align:center;color:#4b5563;font-size:14px;line-height:1.8;">
            <div style="font-size:22px;font-weight:800;color:#111827;margin-bottom:4px;">' . htmlspecialchars($brand['name']) . '</div>
            <div style="margin-bottom:14px;">' . htmlspecialchars($brand['slogan']) . '</div>
            <div>Email: <a href="mailto:' . htmlspecialchars($brand['email']) . '" style="color:#1b2299;text-decoration:none;">' . htmlspecialchars($brand['email']) . '</a></div>
            <div>Phone: ' . htmlspecialchars($brand['phone']) . '</div>
            <div>Website: <a href="' . $website . '" style="color:#1b2299;text-decoration:none;">' . $websiteLabel . '</a></div>
        </div>
        ' . getSocialMediaLinks() . '
        <div style="text-align:center;color:#6b7280;font-size:12px;margin-top:16px;">
            This message was sent by JLM. Please keep your reference details for future follow-up.
        </div>
    </div>';
}

function getTicketNumberDisplay($ticketNumber) {
    return '
    <div style="background:#eef6ff;border:1px solid #bfdbfe;border-radius:12px;padding:16px;margin:0 0 24px;text-align:center;">
        <div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#1d4ed8;font-weight:700;margin-bottom:6px;">Reference Ticket</div>
        <div style="font-size:18px;font-weight:700;color:#0f172a;font-family:monospace;">' . htmlspecialchars($ticketNumber) . '</div>
    </div>';
}

function getFormDataDisplay($formData) {
    $rows = '';
    foreach ($formData as $field => $value) {
        if ($value === null || $value === '') {
            continue;
        }

        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        $label = ucwords(str_replace('_', ' ', (string) $field));
        $rows .= '
        <tr>
            <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;color:#374151;width:32%;">' . htmlspecialchars($label) . '</td>
            <td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#111827;">' . nl2br(htmlspecialchars((string) $value)) . '</td>
        </tr>';
    }

    if ($rows === '') {
        return '';
    }

    return '
    <table style="width:100%;border-collapse:collapse;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
        <tbody>' . $rows . '</tbody>
    </table>';
}

function getCompleteEmailTemplate($title, $content, $ticketNumber = null) {
    $ticketHtml = $ticketNumber ? getTicketNumberDisplay($ticketNumber) : '';

    return '
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin:0;padding:24px;background:#eef2f7;font-family:Arial,sans-serif;color:#111827;">
        <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 12px 30px rgba(15,23,42,0.08);">
            ' . getEmailHeader($title) . '
            <div style="padding:32px 24px;line-height:1.7;font-size:15px;">
                ' . $ticketHtml . '
                ' . $content . '
            </div>
            ' . getEmailFooter() . '
        </div>
    </body>
    </html>';
}
