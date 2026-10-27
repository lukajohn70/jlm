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
        if ($action === 'save_questionnaire') {
            $questionnaireId = (int) ($_POST['questionnaire_id'] ?? 0);
            $title = trim((string) ($_POST['title'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $introMessage = trim((string) ($_POST['intro_message'] ?? ''));
            $thankYouMessage = trim((string) ($_POST['thank_you_message'] ?? ''));
            $status = trim((string) ($_POST['status'] ?? 'draft'));
            $status = in_array($status, ['draft', 'published', 'archived'], true) ? $status : 'draft';
            $fields = jlm_normalize_questionnaire_fields($_POST);

            if ($title === '') {
                throw new RuntimeException('Questionnaire title is required.');
            }
            if (empty($fields)) {
                throw new RuntimeException('Add at least one field to the questionnaire.');
            }

            if ($questionnaireId > 0) {
                $existing = $pdo->prepare('SELECT * FROM questionnaires WHERE id = ?');
                $existing->execute([$questionnaireId]);
                $questionnaire = $existing->fetch(PDO::FETCH_ASSOC);
                if (!$questionnaire) {
                    throw new RuntimeException('Questionnaire not found.');
                }

                $slug = $questionnaire['slug'];
                $stmt = $pdo->prepare('UPDATE questionnaires SET title = ?, description = ?, intro_message = ?, thank_you_message = ?, status = ?, updated_by = ?, published_at = CASE WHEN ? = "published" AND published_at IS NULL THEN NOW() ELSE published_at END WHERE id = ?');
                $stmt->execute([$title, $description, $introMessage, $thankYouMessage, $status, $adminId, $status, $questionnaireId]);

                $pdo->prepare('DELETE FROM questionnaire_fields WHERE questionnaire_id = ?')->execute([$questionnaireId]);
            } else {
                $slugBase = jlm_business_slug($title);
                $slug = $slugBase;
                $counter = 2;
                do {
                    $check = $pdo->prepare('SELECT COUNT(*) FROM questionnaires WHERE slug = ?');
                    $check->execute([$slug]);
                    $exists = (int) $check->fetchColumn() > 0;
                    if ($exists) {
                        $slug = $slugBase . '-' . $counter;
                        $counter++;
                    }
                } while ($exists);

                $stmt = $pdo->prepare('INSERT INTO questionnaires (title, slug, description, intro_message, thank_you_message, status, created_by, updated_by, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    $title,
                    $slug,
                    $description,
                    $introMessage,
                    $thankYouMessage,
                    $status,
                    $adminId,
                    $adminId,
                    $status === 'published' ? date('Y-m-d H:i:s') : null,
                ]);
                $questionnaireId = (int) $pdo->lastInsertId();
            }

            $fieldStmt = $pdo->prepare('INSERT INTO questionnaire_fields (questionnaire_id, label, field_name, field_type, placeholder, help_text, field_options, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($fields as $index => $field) {
                $fieldStmt->execute([
                    $questionnaireId,
                    $field['label'],
                    'field_' . ($index + 1),
                    $field['type'],
                    $field['placeholder'],
                    $field['help_text'],
                    json_encode($field['options']),
                    $field['is_required'],
                    $index + 1,
                ]);
            }

            $toast = 'Questionnaire saved successfully.';
            $_SESSION['toast'] = $toast;
            header('Location: questionnaire_manage.php?edit=' . $questionnaireId);
            exit;
        }

        if ($action === 'send_request') {
            $questionnaireId = (int) ($_POST['questionnaire_id'] ?? 0);
            $clientName = trim((string) ($_POST['client_name'] ?? ''));
            $clientEmail = trim((string) ($_POST['client_email'] ?? ''));
            $customMessage = trim((string) ($_POST['custom_message'] ?? ''));

            if ($questionnaireId <= 0 || $clientName === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Questionnaire, client name, and a valid email are required.');
            }

            $questionnaire = jlm_get_questionnaire($pdo, $questionnaireId);
            if (!$questionnaire) {
                throw new RuntimeException('Questionnaire not found.');
            }

            $token = jlm_business_random_token(40);
            $subject = 'Questionnaire: ' . $questionnaire['title'];
            $stmt = $pdo->prepare('INSERT INTO questionnaire_requests (questionnaire_id, client_name, client_email, access_token, status, email_subject, email_message, sent_at, created_by) VALUES (?, ?, ?, ?, "sent", ?, ?, NOW(), ?)');
            $stmt->execute([$questionnaireId, $clientName, $clientEmail, $token, $subject, $customMessage, $adminId]);

            $baseUrl = jlm_base_url();
            $questionnaireUrl = $baseUrl . '/questionnaire.php?token=' . rawurlencode($token);
            $sent = jlm_send_questionnaire_email($clientEmail, $clientName, $questionnaire['title'], $questionnaireUrl, $customMessage);

            if (!$sent) {
                throw new RuntimeException('The questionnaire was saved, but the email could not be sent.');
            }

            $_SESSION['toast'] = 'Questionnaire link sent successfully.';
            header('Location: questionnaire_manage.php?edit=' . $questionnaireId);
            exit;
        }

        if ($action === 'archive_questionnaire') {
            $questionnaireId = (int) ($_POST['questionnaire_id'] ?? 0);
            $pdo->prepare('UPDATE questionnaires SET status = "archived", updated_by = ? WHERE id = ?')->execute([$adminId, $questionnaireId]);
            $_SESSION['toast'] = 'Questionnaire archived.';
            header('Location: questionnaire_manage.php');
            exit;
        }
    } catch (Throwable $e) {
        $toast = $e->getMessage();
    }
}

$toast = $_SESSION['toast'] ?? $toast;
unset($_SESSION['toast']);

$editId = (int) ($_GET['edit'] ?? 0);
$editing = $editId > 0 ? jlm_get_questionnaire($pdo, $editId) : null;
$questionnaires = $pdo->query("
    SELECT q.*,
           (SELECT COUNT(*) FROM questionnaire_requests qr WHERE qr.questionnaire_id = q.id) AS request_count,
           (SELECT COUNT(*) FROM questionnaire_responses rs WHERE rs.questionnaire_id = q.id) AS response_count
    FROM questionnaires q
    ORDER BY q.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$requests = [];
$responses = [];
if ($editing) {
    $requestStmt = $pdo->prepare('SELECT * FROM questionnaire_requests WHERE questionnaire_id = ? ORDER BY created_at DESC');
    $requestStmt->execute([$editing['id']]);
    $requests = $requestStmt->fetchAll(PDO::FETCH_ASSOC);

    $responseStmt = $pdo->prepare("
        SELECT rs.*, qr.client_name, qr.client_email
        FROM questionnaire_responses rs
        LEFT JOIN questionnaire_requests qr ON qr.id = rs.request_id
        WHERE rs.questionnaire_id = ?
        ORDER BY rs.submitted_at DESC
    ");
    $responseStmt->execute([$editing['id']]);
    $responses = $responseStmt->fetchAll(PDO::FETCH_ASSOC);
}

function questionnaireFieldOptionsText($field) {
    $options = json_decode((string) ($field['field_options'] ?? '[]'), true);
    return is_array($options) ? implode("\n", $options) : '';
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questionnaire Builder | JLM</title>
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
                    <h1 class="text-xl font-bold text-gray-900">Questionnaire Builder</h1>
                        <p class="text-sm text-gray-500">Create customized client questionnaires, collect responses, and accept supporting documents from your website.</p>
                </div>
            </div>
            <a href="admin_dashboard.php?tab=questionnaires" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <?php if ($toast): ?>
            <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-xl"><?php echo htmlspecialchars($toast); ?></div>
        <?php endif; ?>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900"><?php echo $editing ? 'Edit Questionnaire' : 'New Questionnaire'; ?></h2>
                        <p class="text-sm text-gray-500">Build forms with text, choice, date, and document upload fields.</p>
                    </div>
                </div>

                <form method="post" class="space-y-6">
                    <input type="hidden" name="action" value="save_questionnaire">
                    <input type="hidden" name="questionnaire_id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" required value="<?php echo htmlspecialchars($editing['title'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                                <?php $currentStatus = $editing['status'] ?? 'draft'; ?>
                                <?php foreach (['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'] as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo $currentStatus === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Public Slug</label>
                            <div class="w-full border border-dashed border-gray-300 rounded-xl px-4 py-3 text-sm text-gray-500">
                                <?php echo htmlspecialchars($editing['slug'] ?? 'Generated after first save'); ?>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Intro Message</label>
                            <textarea name="intro_message" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"><?php echo htmlspecialchars($editing['intro_message'] ?? 'Please complete this questionnaire with as much detail as possible.'); ?></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Thank-you Message</label>
                            <textarea name="thank_you_message" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:outline-none"><?php echo htmlspecialchars($editing['thank_you_message'] ?? 'Thank you. Your response has been received successfully.'); ?></textarea>
                        </div>
                    </div>

                    <div>
                        <div class="mb-4">
                            <h3 class="text-base font-semibold text-gray-900">Fields</h3>
                            <p class="text-sm text-gray-500">Use one block per question. Choice fields read one option per line. Document upload lets clients attach logos, brochures, and related files.</p>
                        </div>
                        <div id="fieldBuilder" class="space-y-4">
                            <?php
                            $fieldRows = $editing['fields'] ?? [];
                            if (empty($fieldRows)) {
                                $fieldRows = [[
                                    'label' => 'Project Goals',
                                    'field_type' => 'long_text',
                                    'placeholder' => 'Tell us what this project needs to achieve.',
                                    'help_text' => '',
                                    'field_options' => '[]',
                                    'is_required' => 1,
                                ]];
                            }
                            foreach ($fieldRows as $index => $field):
                            ?>
                                <div class="field-row border border-gray-200 rounded-2xl p-4 bg-gray-50">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="font-semibold text-gray-900">Question <?php echo $index + 1; ?></h4>
                                        <button type="button" class="remove-field text-red-500 hover:text-red-700 text-sm font-semibold">Remove</button>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Label</label>
                                            <input type="text" name="field_label[]" value="<?php echo htmlspecialchars($field['label'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                            <select name="field_type[]" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                                <?php
                                                $fieldType = $field['field_type'] ?? 'short_text';
                                                $typeOptions = [
                                                    'short_text' => 'Short text',
                                                    'long_text' => 'Long text',
                                                    'email' => 'Email',
                                                    'phone' => 'Phone',
                                                    'number' => 'Number',
                                                    'date' => 'Date',
                                                    'single_choice' => 'Single choice',
                                                    'multiple_choice' => 'Multiple choice',
                                                    'dropdown' => 'Dropdown',
                                                    'file_upload' => 'Document upload',
                                                ];
                                                foreach ($typeOptions as $value => $label):
                                                ?>
                                                    <option value="<?php echo $value; ?>" <?php echo $fieldType === $value ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder</label>
                                            <input type="text" name="field_placeholder[]" value="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Help Text</label>
                                            <input type="text" name="field_help[]" value="<?php echo htmlspecialchars($field['help_text'] ?? ''); ?>" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Options</label>
                                            <textarea name="field_options[]" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="One option per line"><?php echo htmlspecialchars(questionnaireFieldOptionsText($field)); ?></textarea>
                                        </div>
                                        <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                                            <input type="checkbox" data-required-checkbox <?php echo !empty($field['is_required']) ? 'checked' : ''; ?> class="rounded border-gray-300 text-blue-600">
                                            Required field
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-5 flex justify-start">
                            <button type="button" id="addFieldBtn" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">
                                <i class="fas fa-plus mr-2"></i>Add Field
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="px-5 py-3 rounded-xl bg-gradient-to-r from-blue-800 to-pink-600 text-white font-semibold hover:opacity-95 transition">
                            Save Questionnaire
                        </button>
                        <?php if ($editing): ?>
                            <a href="questionnaire_manage.php" class="px-5 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">Create New</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-2xl bg-blue-50 p-4">
                            <div class="text-2xl font-bold text-blue-700"><?php echo count($questionnaires); ?></div>
                            <div class="text-sm text-blue-900">Questionnaires</div>
                        </div>
                        <div class="rounded-2xl bg-green-50 p-4">
                            <div class="text-2xl font-bold text-green-700"><?php echo array_sum(array_map(static fn($row) => (int) $row['response_count'], $questionnaires)); ?></div>
                            <div class="text-sm text-green-900">Responses</div>
                        </div>
                    </div>
                </div>

                <?php if ($editing): ?>
                    <div class="bg-white rounded-2xl shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Send to Client</h2>
                        <p class="text-sm text-gray-500 mb-4">Email a private questionnaire link directly from the website.</p>
                        <form method="post" class="space-y-4">
                            <input type="hidden" name="action" value="send_request">
                            <input type="hidden" name="questionnaire_id" value="<?php echo (int) $editing['id']; ?>">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client Name</label>
                                <input type="text" name="client_name" required class="w-full border border-gray-300 rounded-xl px-4 py-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Client Email</label>
                                <input type="email" name="client_email" required class="w-full border border-gray-300 rounded-xl px-4 py-3">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Custom Message</label>
                                <textarea name="custom_message" rows="4" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="Optional note to the client"></textarea>
                            </div>
                            <button type="submit" class="w-full px-4 py-3 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition">Send Questionnaire Link</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Saved Questionnaires</h2>
                    <p class="text-sm text-gray-500">Manage custom forms and open their response history.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            <th class="py-3 pr-4">Title</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Requests</th>
                            <th class="py-3 pr-4">Responses</th>
                            <th class="py-3 pr-4">Created</th>
                            <th class="py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questionnaires as $row): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-4 pr-4">
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['slug']); ?></div>
                                </td>
                                <td class="py-4 pr-4"><span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $row['status'] === 'published' ? 'bg-green-100 text-green-700' : ($row['status'] === 'archived' ? 'bg-gray-200 text-gray-700' : 'bg-yellow-100 text-yellow-700'); ?>"><?php echo htmlspecialchars(ucfirst($row['status'])); ?></span></td>
                                <td class="py-4 pr-4"><?php echo (int) $row['request_count']; ?></td>
                                <td class="py-4 pr-4"><?php echo (int) $row['response_count']; ?></td>
                                <td class="py-4 pr-4"><?php echo htmlspecialchars(date('M j, Y', strtotime($row['created_at']))); ?></td>
                                <td class="py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="questionnaire_preview.php?id=<?php echo (int) $row['id']; ?>" target="_blank" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">View</a>
                                        <a href="questionnaire_manage.php?edit=<?php echo (int) $row['id']; ?>" class="px-3 py-2 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition">Edit</a>
                                        <?php if ($row['status'] !== 'archived'): ?>
                                            <form method="post" onsubmit="return confirm('Archive this questionnaire?');">
                                                <input type="hidden" name="action" value="archive_questionnaire">
                                                <input type="hidden" name="questionnaire_id" value="<?php echo (int) $row['id']; ?>">
                                                <button type="submit" class="px-3 py-2 rounded-lg bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition">Archive</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($editing): ?>
            <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Sent Requests</h2>
                    <div class="space-y-4">
                        <?php if (empty($requests)): ?>
                            <p class="text-sm text-gray-500">No questionnaire links have been sent yet.</p>
                        <?php else: ?>
                            <?php foreach ($requests as $request): ?>
                                <?php $shareUrl = jlm_base_url() . '/questionnaire.php?token=' . rawurlencode($request['access_token']); ?>
                                <div class="border border-gray-200 rounded-2xl p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($request['client_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['client_email']); ?></div>
                                            <div class="text-xs text-gray-500 mt-2">Sent <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($request['created_at']))); ?></div>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $request['status'] === 'responded' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span>
                                    </div>
                                    <div class="mt-3 text-sm break-all text-blue-700"><?php echo htmlspecialchars($shareUrl); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Responses</h2>
                    <div class="space-y-4">
                        <?php if (empty($responses)): ?>
                            <p class="text-sm text-gray-500">Responses will appear here once clients submit the questionnaire.</p>
                        <?php else: ?>
                            <?php foreach ($responses as $response): ?>
                                <div class="border border-gray-200 rounded-2xl p-4">
                                    <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($response['respondent_name'] ?: ($response['client_name'] ?? 'Client')); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($response['respondent_email'] ?: ($response['client_email'] ?? '')); ?></div>
                                    <div class="text-xs text-gray-500 mt-2">Submitted <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($response['submitted_at']))); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <template id="fieldTemplate">
        <div class="field-row border border-gray-200 rounded-2xl p-4 bg-gray-50">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-semibold text-gray-900">New Question</h4>
                <button type="button" class="remove-field text-red-500 hover:text-red-700 text-sm font-semibold">Remove</button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Label</label>
                    <input type="text" name="field_label[]" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select name="field_type[]" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                        <option value="short_text">Short text</option>
                        <option value="long_text">Long text</option>
                        <option value="email">Email</option>
                        <option value="phone">Phone</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="single_choice">Single choice</option>
                        <option value="multiple_choice">Multiple choice</option>
                        <option value="dropdown">Dropdown</option>
                        <option value="file_upload">Document upload</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Placeholder</label>
                    <input type="text" name="field_placeholder[]" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Help Text</label>
                    <input type="text" name="field_help[]" class="w-full border border-gray-300 rounded-xl px-4 py-3">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Options</label>
                    <textarea name="field_options[]" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="One option per line"></textarea>
                </div>
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" data-required-checkbox class="rounded border-gray-300 text-blue-600">
                    Required field
                </label>
            </div>
        </div>
    </template>

    <script>
        (function () {
            const addFieldBtn = document.getElementById('addFieldBtn');
            const fieldBuilder = document.getElementById('fieldBuilder');
            const fieldTemplate = document.getElementById('fieldTemplate');

            function bindRemoveButtons() {
                document.querySelectorAll('.remove-field').forEach(function (button) {
                    button.onclick = function () {
                        const rows = document.querySelectorAll('.field-row');
                        if (rows.length === 1) {
                            return;
                        }
                        button.closest('.field-row').remove();
                        reindexFields();
                    };
                });
            }

            function reindexFields() {
                document.querySelectorAll('.field-row').forEach(function (row, index) {
                    const heading = row.querySelector('h4');
                    const checkbox = row.querySelector('[data-required-checkbox]');
                    if (heading) {
                        heading.textContent = 'Question ' + (index + 1);
                    }
                    if (checkbox) {
                        checkbox.name = 'field_required[' + index + ']';
                    }
                });
            }

            if (addFieldBtn && fieldBuilder && fieldTemplate) {
                addFieldBtn.addEventListener('click', function () {
                    fieldBuilder.insertAdjacentHTML('beforeend', fieldTemplate.innerHTML);
                    bindRemoveButtons();
                    reindexFields();
                });
                bindRemoveButtons();
                reindexFields();
            }
        })();
    </script>
</body>
</html>
