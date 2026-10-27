<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';
require_once __DIR__ . '/includes/functions.php';

jlm_ensure_business_tools_schema($pdo);

$token = trim((string) ($_GET['token'] ?? ''));
$request = null;
$questionnaire = null;
$errors = [];
$submitted = false;

if ($token !== '') {
    $stmt = $pdo->prepare("
        SELECT qr.*, q.title, q.description, q.intro_message, q.thank_you_message, q.status AS questionnaire_status
        FROM questionnaire_requests qr
        INNER JOIN questionnaires q ON q.id = qr.questionnaire_id
        WHERE qr.access_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($request) {
        $questionnaire = jlm_get_questionnaire($pdo, $request['questionnaire_id']);
        $existingResponseStmt = $pdo->prepare('SELECT id FROM questionnaire_responses WHERE request_id = ? LIMIT 1');
        $existingResponseStmt->execute([(int) $request['id']]);
        $submitted = (bool) $existingResponseStmt->fetchColumn();
    }
}

if (!$request || !$questionnaire || $questionnaire['status'] === 'archived') {
    http_response_code(404);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request && $questionnaire) {
    $existingResponseStmt = $pdo->prepare('SELECT id FROM questionnaire_responses WHERE request_id = ? LIMIT 1');
    $existingResponseStmt->execute([(int) $request['id']]);
    $existingResponse = $existingResponseStmt->fetchColumn();

    if ($existingResponse) {
        $submitted = true;
    } else {
        $respondentName = trim((string) ($_POST['respondent_name'] ?? $request['client_name']));
        $respondentEmail = trim((string) ($_POST['respondent_email'] ?? $request['client_email']));
        $answers = [];
        $answerSummary = [];

        foreach ($questionnaire['fields'] as $field) {
            $fieldKey = 'field_' . (int) $field['id'];
            if (($field['field_type'] ?? '') === 'file_upload') {
                $uploadedFile = $_FILES[$fieldKey] ?? null;
                $value = '';
                if (is_array($uploadedFile) && (int) ($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $storedPath = jlm_upload_questionnaire_document($uploadedFile);
                    $value = jlm_base_url() . '/' . ltrim($storedPath, '/');
                }
            } else {
                $value = $_POST[$fieldKey] ?? '';
                if (is_array($value)) {
                    $value = implode(', ', array_map('trim', $value));
                } else {
                    $value = trim((string) $value);
                }
            }

            if (!empty($field['is_required']) && $value === '') {
                $errors[$fieldKey] = $field['label'] . ' is required.';
            }

            $answers[$field['id']] = $value;
            $answerSummary[$field['label']] = $value;
        }

        if ($respondentName === '') {
            $errors['respondent_name'] = 'Your name is required.';
        }
        if (!filter_var($respondentEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['respondent_email'] = 'A valid email address is required.';
        }

        if (empty($errors)) {
            $responseStmt = $pdo->prepare('INSERT INTO questionnaire_responses (questionnaire_id, request_id, respondent_name, respondent_email, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)');
            $responseStmt->execute([
                $questionnaire['id'],
                $request['id'],
                $respondentName,
                $respondentEmail,
                $_SERVER['REMOTE_ADDR'] ?? '',
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
            $responseId = (int) $pdo->lastInsertId();

            $answerStmt = $pdo->prepare('INSERT INTO questionnaire_response_answers (response_id, field_id, answer_text) VALUES (?, ?, ?)');
            foreach ($answers as $fieldId => $answerText) {
                $answerStmt->execute([$responseId, $fieldId, $answerText]);
            }

            $pdo->prepare('UPDATE questionnaire_requests SET status = "responded", responded_at = NOW() WHERE id = ?')->execute([(int) $request['id']]);
            jlm_send_admin_questionnaire_submission_notice($questionnaire, $request, $answerSummary);
            $submitted = true;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($questionnaire['title'] ?? 'Questionnaire'); ?> | JLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="<?php echo asset_url('assets/images/jlm.png'); ?>" />
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white rounded-[32px] shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 via-indigo-800 to-pink-600 px-8 py-10 text-white">
                <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM Logo" class="h-12 w-auto mb-6">
                <p class="text-sm uppercase tracking-[0.25em] text-white/80">Client Questionnaire</p>
                <h1 class="text-3xl md:text-4xl font-bold mt-3"><?php echo htmlspecialchars($questionnaire['title'] ?? 'Questionnaire'); ?></h1>
                <p class="text-white/85 mt-3 max-w-2xl"><?php echo htmlspecialchars($questionnaire['description'] ?? ''); ?></p>
            </div>

            <div class="px-6 md:px-8 py-8">
                <?php if (!$request || !$questionnaire): ?>
                    <div class="rounded-2xl bg-red-50 border border-red-200 p-6">
                        <h2 class="text-lg font-semibold text-red-800">Questionnaire not found</h2>
                        <p class="text-red-700 mt-2">This questionnaire link is invalid or no longer available.</p>
                    </div>
                <?php elseif ($submitted): ?>
                    <div class="rounded-3xl bg-green-50 border border-green-200 p-8 text-center">
                        <div class="w-14 h-14 rounded-full bg-green-100 text-green-700 flex items-center justify-center mx-auto mb-4 text-2xl">✓</div>
                        <h2 class="text-2xl font-bold text-green-900">Response received</h2>
                        <p class="text-green-800 mt-3 max-w-2xl mx-auto"><?php echo htmlspecialchars($questionnaire['thank_you_message'] ?: 'Thank you. Your response has been submitted successfully.'); ?></p>
                        <p class="text-sm text-green-700 mt-4">This questionnaire link has now been completed and can’t be submitted again.</p>
                    </div>
                <?php else: ?>
                    <div class="mb-6 rounded-2xl bg-slate-50 border border-slate-200 p-5 text-slate-700">
                        <?php echo nl2br(htmlspecialchars($questionnaire['intro_message'] ?: 'Please complete this questionnaire with as much detail as possible.')); ?>
                    </div>

                    <form method="post" enctype="multipart/form-data" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Your Name</label>
                                <input type="text" name="respondent_name" value="<?php echo htmlspecialchars($_POST['respondent_name'] ?? $request['client_name']); ?>" class="w-full border <?php echo isset($errors['respondent_name']) ? 'border-red-300' : 'border-slate-300'; ?> rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php if (isset($errors['respondent_name'])): ?><p class="text-sm text-red-600 mt-2"><?php echo htmlspecialchars($errors['respondent_name']); ?></p><?php endif; ?>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Your Email</label>
                                <input type="email" name="respondent_email" value="<?php echo htmlspecialchars($_POST['respondent_email'] ?? $request['client_email']); ?>" class="w-full border <?php echo isset($errors['respondent_email']) ? 'border-red-300' : 'border-slate-300'; ?> rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php if (isset($errors['respondent_email'])): ?><p class="text-sm text-red-600 mt-2"><?php echo htmlspecialchars($errors['respondent_email']); ?></p><?php endif; ?>
                            </div>
                        </div>

                        <?php foreach ($questionnaire['fields'] as $field): ?>
                            <?php
                            $fieldKey = 'field_' . (int) $field['id'];
                            $fieldType = $field['field_type'];
                            $fieldOptions = json_decode((string) ($field['field_options'] ?? '[]'), true);
                            $submittedValue = $_POST[$fieldKey] ?? '';
                            ?>
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-2">
                                    <?php echo htmlspecialchars($field['label']); ?>
                                    <?php if (!empty($field['is_required'])): ?><span class="text-pink-600">*</span><?php endif; ?>
                                </label>
                                <?php if (!empty($field['help_text'])): ?>
                                    <p class="text-sm text-slate-500 mb-2"><?php echo htmlspecialchars($field['help_text']); ?></p>
                                <?php endif; ?>

                                <?php if ($fieldType === 'long_text'): ?>
                                    <textarea name="<?php echo $fieldKey; ?>" rows="5" class="w-full border <?php echo isset($errors[$fieldKey]) ? 'border-red-300' : 'border-slate-300'; ?> rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"><?php echo htmlspecialchars((string) $submittedValue); ?></textarea>
                                <?php elseif (in_array($fieldType, ['single_choice', 'multiple_choice'], true)): ?>
                                    <div class="space-y-3">
                                        <?php foreach ((array) $fieldOptions as $option): ?>
                                            <?php
                                            $option = (string) $option;
                                            $isChecked = is_array($submittedValue) ? in_array($option, $submittedValue, true) : (string) $submittedValue === $option;
                                            ?>
                                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 bg-slate-50">
                                                <input type="<?php echo $fieldType === 'multiple_choice' ? 'checkbox' : 'radio'; ?>" name="<?php echo $fieldType === 'multiple_choice' ? $fieldKey . '[]' : $fieldKey; ?>" value="<?php echo htmlspecialchars($option); ?>" <?php echo $isChecked ? 'checked' : ''; ?> class="text-blue-600">
                                                <span><?php echo htmlspecialchars($option); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($fieldType === 'dropdown'): ?>
                                    <select name="<?php echo $fieldKey; ?>" class="w-full border <?php echo isset($errors[$fieldKey]) ? 'border-red-300' : 'border-slate-300'; ?> rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">Select one</option>
                                        <?php foreach ((array) $fieldOptions as $option): ?>
                                            <option value="<?php echo htmlspecialchars((string) $option); ?>" <?php echo (string) $submittedValue === (string) $option ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $option); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($fieldType === 'file_upload'): ?>
                                    <input type="file" name="<?php echo $fieldKey; ?>" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.ppt,.pptx,.zip" class="w-full border <?php echo isset($errors[$fieldKey]) ? 'border-red-300' : 'border-slate-300'; ?> rounded-2xl px-4 py-3 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-xs text-slate-500 mt-2">Accepted: JPG, PNG, WebP, PDF, Word, PowerPoint, or ZIP. Max 10MB.</p>
                                <?php else: ?>
                                    <?php
                                    $inputTypeMap = [
                                        'short_text' => 'text',
                                        'email' => 'email',
                                        'phone' => 'tel',
                                        'number' => 'number',
                                        'date' => 'date',
                                    ];
                                    $inputType = $inputTypeMap[$fieldType] ?? 'text';
                                    ?>
                                    <input type="<?php echo $inputType; ?>" name="<?php echo $fieldKey; ?>" value="<?php echo htmlspecialchars((string) $submittedValue); ?>" placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>" class="w-full border <?php echo isset($errors[$fieldKey]) ? 'border-red-300' : 'border-slate-300'; ?> rounded-2xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php endif; ?>

                                <?php if (isset($errors[$fieldKey])): ?><p class="text-sm text-red-600 mt-2"><?php echo htmlspecialchars($errors[$fieldKey]); ?></p><?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="pt-2">
                            <button type="submit" class="inline-flex items-center justify-center px-6 py-4 rounded-2xl bg-gradient-to-r from-blue-900 to-pink-600 text-white font-semibold shadow-lg hover:opacity-95 transition">
                                Submit Questionnaire
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
