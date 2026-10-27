<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email_templates.php';
require_once __DIR__ . '/../includes/functions.php';

define('ADMIN_EMAIL', getenv('JLM_ADMIN_EMAIL') ?: 'support@jlm.com.ng');
define('FROM_EMAIL', getenv('JLM_FROM_EMAIL') ?: 'support@jlm.com.ng');
define('FROM_NAME', getenv('JLM_FROM_NAME') ?: 'JLM Creative Media');
define('REPLY_TO_EMAIL', getenv('JLM_REPLY_TO_EMAIL') ?: FROM_EMAIL);

if (DEVELOPMENT_MODE) {
    define('SMTP_HOST', getenv('JLM_SMTP_HOST') ?: '');
    define('SMTP_PORT', (int) (getenv('JLM_SMTP_PORT') ?: 25));
    define('SMTP_USERNAME', getenv('JLM_SMTP_USERNAME') ?: '');
    define('SMTP_PASSWORD', getenv('JLM_SMTP_PASSWORD') ?: '');
    define('SMTP_SECURE', getenv('JLM_SMTP_SECURE') ?: '');
} else {
    define('SMTP_HOST', getenv('JLM_SMTP_HOST') ?: 'mail.jlm.com.ng');
    define('SMTP_PORT', (int) (getenv('JLM_SMTP_PORT') ?: 465));
    define('SMTP_USERNAME', getenv('JLM_SMTP_USERNAME') ?: FROM_EMAIL);
    define('SMTP_PASSWORD', getenv('JLM_SMTP_PASSWORD') ?: '');
    define('SMTP_SECURE', getenv('JLM_SMTP_SECURE') ?: 'ssl');
}

function buildPlainTextMessage($html) {
    $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
    $text = preg_replace('/<\/p>/i', "\n\n", $text);
    return trim(html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function sendEmail($to, $subject, $message, $isHTML = true) {
    if (getenv('JLM_LOG_EMAIL_ONLY')) {
        error_log("JLM EMAIL LOG-ONLY TO: {$to}");
        error_log("JLM EMAIL LOG-ONLY SUBJECT: {$subject}");
        error_log("JLM EMAIL LOG-ONLY BODY: " . substr(buildPlainTextMessage($message), 0, 500));
        return false;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->Timeout = 15;
        $mail->CharSet = 'UTF-8';
        $useSmtp = SMTP_HOST !== ''
            && (
                SMTP_HOST !== 'localhost'
                || SMTP_PORT !== 25
                || SMTP_USERNAME !== ''
                || SMTP_PASSWORD !== ''
                || SMTP_SECURE !== ''
            );

        if ($useSmtp) {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = SMTP_USERNAME !== '' || SMTP_PASSWORD !== '';
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;

            if (SMTP_SECURE === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif (SMTP_SECURE === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            $mail->isMail();
        }

        $mail->setFrom(FROM_EMAIL, FROM_NAME, false);
        $mail->Sender = FROM_EMAIL;
        $mail->addReplyTo(REPLY_TO_EMAIL, FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = $isHTML ? buildPlainTextMessage($message) : $message;
        $mail->send();
        error_log('Email sent successfully to ' . $to . ' using ' . ($useSmtp ? 'SMTP' : 'mail()'));
        return true;
    } catch (Throwable $e) {
        error_log('Email sending failed: ' . $e->getMessage());
        return false;
    }
}

function buildReferenceNotice($message, $ticketNumber) {
    if (!$ticketNumber) {
        return $message;
    }

    return $message . '<p style="margin-top:20px;color:#374151;">Reference: <strong>' . htmlspecialchars($ticketNumber) . '</strong></p>';
}

function sendAdminNotification($formType, $formData, $ticketNumber = null) {
    $content = '
    <p style="margin-top:0;">A new <strong>' . htmlspecialchars($formType) . '</strong> was submitted on ' . date('F j, Y \a\t g:i A') . '.</p>
    ' . getFormDataDisplay($formData) . '
    <p style="margin:20px 0 0;color:#374151;">Please review and follow up from the JLM admin dashboard.</p>';

    return sendEmail(
        ADMIN_EMAIL,
        'New ' . $formType . ' Submission - JLM',
        getCompleteEmailTemplate('New ' . $formType . ' Submission', $content, $ticketNumber)
    );
}

function sendConfirmationEmail($userEmail, $userName, $formType, $ticketNumber = null) {
    $content = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($userName) . ',</p>
    <p>Thank you for your ' . htmlspecialchars($formType) . '. We have received it and a member of the JLM team will follow up as soon as possible.</p>
    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:16px;margin:20px 0;">
        <strong>What happens next?</strong>
        <ul style="margin:10px 0 0 18px;padding:0;">
            <li>We review your submission.</li>
            <li>We send a response to your email address.</li>
            <li>We use your reference ticket for future follow-up.</li>
        </ul>
    </div>
    <p>' . buildReferenceNotice('We appreciate the opportunity to work with you.', $ticketNumber) . '</p>
    <p>Warm regards,<br><strong>The JLM Team</strong></p>';

    return sendEmail(
        $userEmail,
        'Thank you for contacting JLM',
        getCompleteEmailTemplate('Thank You for Reaching Out', $content, $ticketNumber)
    );
}

function sendWelcomeEmail($userEmail, $userName, $role) {
    $content = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($userName) . ',</p>
    <p>Your JLM account has been created successfully with the role <strong>' . htmlspecialchars(ucfirst($role)) . '</strong>.</p>
    <p>You can now sign in and continue with your JLM workflow.</p>
    <p>Warm regards,<br><strong>The JLM Team</strong></p>';

    return sendEmail(
        $userEmail,
        'Welcome to JLM',
        getCompleteEmailTemplate('Welcome to JLM', $content)
    );
}

function handleContactFormEmail($name, $email, $phone, $service, $message, $ticketNumber = null) {
    $formData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'service' => $service,
        'message' => $message,
    ];

    return [
        'admin' => sendAdminNotification('Contact Form', $formData, $ticketNumber),
        'user' => sendConfirmationEmail($email, $name, 'contact request', $ticketNumber),
    ];
}

function handleBookingFormEmail($firstName, $lastName, $email, $phone, $services, $projectDescription, $consultationDateTime, $consultationMethod, $budgetRange, $timeline, $ticketNumber = null) {
    $fullName = trim($firstName . ' ' . $lastName);
    $formData = [
        'name' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'services' => is_array($services) ? implode(', ', $services) : $services,
        'project_description' => $projectDescription,
        'consultation_datetime' => $consultationDateTime,
        'consultation_method' => $consultationMethod,
        'budget_range' => $budgetRange,
        'timeline' => $timeline,
    ];

    return [
        'admin' => sendAdminNotification('Booking Form', $formData, $ticketNumber),
        'user' => sendConfirmationEmail($email, $fullName, 'booking request', $ticketNumber),
    ];
}

function handleRegistrationEmail($name, $email, $role) {
    return [
        'admin' => sendAdminNotification('User Registration', [
            'name' => $name,
            'email' => $email,
            'role' => $role,
        ]),
        'user' => sendWelcomeEmail($email, $name, $role),
    ];
}

function sendBookingReply($customerEmail, $customerName, $subject, $message, $ticketNumber = '') {
    $content = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($customerName) . ',</p>
    <p>Thank you for your booking request. Here is our response:</p>
    <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:20px 0;">
        <div style="font-weight:700;margin-bottom:10px;">' . htmlspecialchars($subject) . '</div>
        <div>' . nl2br(htmlspecialchars($message)) . '</div>
    </div>
    <p>Warm regards,<br><strong>The JLM Team</strong></p>';

    return sendEmail(
        $customerEmail,
        'Re: Your Booking Request - JLM',
        getCompleteEmailTemplate('Booking Response', $content, $ticketNumber)
    );
}

function sendContactReply($userEmail, $userName, $subject, $message, $ticketNumber = null) {
    $content = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($userName) . ',</p>
    <p>Here is a response from the JLM team regarding your enquiry.</p>
    <div style="background:#f8fafc;border-left:4px solid #1b2299;padding:16px;margin:20px 0;">' . nl2br(htmlspecialchars($message)) . '</div>
    <p>Warm regards,<br><strong>The JLM Team</strong></p>';

    return sendEmail(
        $userEmail,
        'Re: ' . $subject,
        getCompleteEmailTemplate('Response from JLM', $content, $ticketNumber)
    );
}

function sendNewsletterEmail($subscriberEmail, $firstName, $subject, $content) {
    $safeName = $firstName ?: 'there';
    $baseUrl = function_exists('jlm_base_url') ? jlm_base_url() : rtrim((string) (getenv('JLM_SITE_URL') ?: 'https://jlm.com.ng'), '/');
    $unsubscribeUrl = rtrim($baseUrl, '/') . '/unsubscribe.php?email=' . rawurlencode($subscriberEmail) . '&token=' . rawurlencode(md5($subscriberEmail));
    $emailContent = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($safeName) . ',</p>
    <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:14px;padding:20px 22px;color:#1f2937;">
        ' . $content . '
    </div>
    <p style="margin:24px 0 0;color:#6b7280;font-size:13px;line-height:1.7;">
        You are receiving this because you subscribed to JLM updates.
        <a href="' . htmlspecialchars($unsubscribeUrl) . '" style="color:#1b2299;">Unsubscribe</a>
    </p>';

    return sendEmail($subscriberEmail, $subject, getCompleteEmailTemplate($subject, $emailContent));
}

function sendSubscriptionConfirmation($email, $firstName) {
    $safeName = $firstName ?: 'there';
    $content = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($safeName) . ',</p>
    <p>Thank you for subscribing to JLM updates. You will now receive creative news, offers, and selected insights from our team.</p>
    <p>Warm regards,<br><strong>The JLM Team</strong></p>';

    return sendEmail($email, 'Welcome to JLM Newsletter', getCompleteEmailTemplate('Welcome to JLM Updates', $content));
}

function sendTwoFactorCodeEmail($email, $name, $code) {
    $content = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($name) . ',</p>
    <p>Your JLM admin verification code is:</p>
    <div style="font-size:32px;letter-spacing:0.35em;font-weight:700;text-align:center;margin:24px 0;color:#1b2299;">' . htmlspecialchars($code) . '</div>
    <p>This code expires in 10 minutes. If you did not attempt to sign in, please reset your password and review your admin access immediately.</p>';

    return sendEmail($email, 'Your JLM Verification Code', getCompleteEmailTemplate('Two-Factor Verification', $content));
}

function sendPasswordResetEmail($email, $name, $resetUrl) {
    $content = '
    <p style="margin-top:0;">Dear ' . htmlspecialchars($name ?: 'Admin') . ',</p>
    <p>We received a request to reset your JLM admin password.</p>
    <p style="margin:24px 0;">
        <a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background:linear-gradient(135deg,#1b2299,#e4306d);color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:700;">
            Reset Password
        </a>
    </p>
    <p>If the button does not work, use this link:</p>
    <p style="word-break:break-word;color:#1b2299;">' . htmlspecialchars($resetUrl) . '</p>
    <p>This link expires in 30 minutes. If you did not request it, you can ignore this email.</p>';

    return sendEmail($email, 'Reset Your JLM Admin Password', getCompleteEmailTemplate('Password Reset Request', $content));
}

function sendBlogOtpEmail(string $email, string $name, string $code): bool {
    $safeName = htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $content = '
    <p style="margin-top:0;">Hi ' . $safeName . ',</p>
    <p>To complete your JLM blog account setup, please enter the verification code below. It expires in <strong>10 minutes</strong>.</p>
    <div style="margin:28px auto;text-align:center;">
        <div style="display:inline-block;background:linear-gradient(135deg,#1b2299,#e4306d);border-radius:16px;padding:22px 36px;">
            <span style="font-size:36px;letter-spacing:0.4em;font-weight:800;color:#ffffff;font-family:monospace;">' . $safeCode . '</span>
        </div>
    </div>
    <p style="color:#6b7280;font-size:13px;">If you did not create an account on JLM, you can safely ignore this email.</p>';

    // Also log the code locally (useful when SMTP is unavailable in dev)
    error_log('[JLM Blog OTP] Email: ' . $email . ' | Code: ' . $code);

    return sendEmail($email, 'Your JLM Blog Verification Code', getCompleteEmailTemplate('Verify Your Email', $content));
}
