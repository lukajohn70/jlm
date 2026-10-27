<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/business_tools.php';
require_once __DIR__ . '/includes/functions.php';

require_admin_auth();
jlm_ensure_business_tools_schema($pdo);

$questionnaireId = (int) ($_GET['id'] ?? 0);
$questionnaire = $questionnaireId > 0 ? jlm_get_questionnaire($pdo, $questionnaireId) : null;
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($questionnaire['title'] ?? 'Questionnaire Preview'); ?> | JLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="max-w-4xl mx-auto px-4 py-10">
        <div class="bg-white rounded-[32px] shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 via-indigo-800 to-pink-600 px-8 py-10 text-white">
                <img src="<?php echo asset_url('assets/images/jlm.png'); ?>" alt="JLM Logo" class="h-12 w-auto mb-6">
                <p class="text-sm uppercase tracking-[0.25em] text-white/80">Admin Preview</p>
                <h1 class="text-3xl md:text-4xl font-bold mt-3"><?php echo htmlspecialchars($questionnaire['title'] ?? 'Questionnaire'); ?></h1>
                <p class="text-white/85 mt-3 max-w-2xl"><?php echo htmlspecialchars($questionnaire['description'] ?? ''); ?></p>
            </div>
            <div class="px-6 md:px-8 py-8">
                <?php if (!$questionnaire): ?>
                    <div class="rounded-2xl bg-red-50 border border-red-200 p-6">Questionnaire not found.</div>
                <?php else: ?>
                    <div class="mb-6 rounded-2xl bg-slate-50 border border-slate-200 p-5 text-slate-700">
                        <?php echo nl2br(htmlspecialchars($questionnaire['intro_message'] ?: 'Please complete this questionnaire with as much detail as possible.')); ?>
                    </div>
                    <div class="space-y-6">
                        <?php foreach ($questionnaire['fields'] as $field): ?>
                            <?php $fieldOptions = json_decode((string) ($field['field_options'] ?? '[]'), true); ?>
                            <div>
                                <label class="block text-sm font-semibold text-slate-800 mb-2"><?php echo htmlspecialchars($field['label']); ?><?php if (!empty($field['is_required'])): ?><span class="text-pink-600">*</span><?php endif; ?></label>
                                <?php if (!empty($field['help_text'])): ?><p class="text-sm text-slate-500 mb-2"><?php echo htmlspecialchars($field['help_text']); ?></p><?php endif; ?>
                                <?php if (($field['field_type'] ?? '') === 'long_text'): ?>
                                    <textarea rows="4" disabled class="w-full border border-slate-300 rounded-2xl px-4 py-3 bg-slate-50"></textarea>
                                <?php elseif (in_array(($field['field_type'] ?? ''), ['single_choice', 'multiple_choice'], true)): ?>
                                    <div class="space-y-3">
                                        <?php foreach ((array) $fieldOptions as $option): ?>
                                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 px-4 py-3 bg-slate-50"><input type="checkbox" disabled><span><?php echo htmlspecialchars((string) $option); ?></span></label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (($field['field_type'] ?? '') === 'dropdown'): ?>
                                    <select disabled class="w-full border border-slate-300 rounded-2xl px-4 py-3 bg-slate-50"><option>Select one</option></select>
                                <?php else: ?>
                                    <input type="text" disabled class="w-full border border-slate-300 rounded-2xl px-4 py-3 bg-slate-50" placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
