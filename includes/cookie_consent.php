<?php
/**
 * JLM Creative Media - Cookie Consent Component
 * Fully compliant with GDPR, ePrivacy, NDPA 2023, and CCPA/CPRA.
 */

// Prevent multiple renderings on the same page
if (defined('JLM_COOKIE_CONSENT_RENDERED')) {
    return;
}
define('JLM_COOKIE_CONSENT_RENDERED', true);

// Compute correct asset URL for the script
$scriptPath = 'assets/js/cookie-consent.js';
if (function_exists('asset_url')) {
    $scriptUrl = asset_url($scriptPath);
} else {
    $scriptUrl = $scriptPath;
}
?>

<!-- ========================================== -->
<!-- JLM COOKIE CONSENT BANNER & MODAL (GDPR/NDPA) -->
<!-- ========================================== -->
<div id="jlm-cookie-consent-wrapper" class="jlm-cookie-system" style="font-family: inherit;">

    <!-- Main Consent Banner (Initially hidden until JS checks state) -->
    <div id="jlm-cookie-banner" 
         class="fixed bottom-0 inset-x-0 z-[99999] p-3 sm:p-5 pointer-events-none hidden" 
         role="region" 
         aria-label="Cookie consent banner"
         aria-hidden="true">
        <div class="pointer-events-auto max-w-5xl mx-auto bg-slate-900/95 backdrop-blur-xl border border-slate-700/80 rounded-2xl shadow-2xl p-5 sm:p-6 text-white transition-all duration-300">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5">
                <div class="flex items-start gap-4 flex-1">
                    <div class="p-2.5 rounded-xl bg-gradient-to-br from-[#1b2299]/30 to-[#e4306d]/30 border border-white/10 text-2xl flex-shrink-0">
                        🍪
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-white mb-1.5 flex items-center gap-2">
                            <span>Your Privacy & Cookie Choices</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider bg-emerald-500/20 text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-500/30">Compliant</span>
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-3xl">
                            We use cookies to maintain website security, operate essential portal features, remember your preferences, and analyze site traffic. Under GDPR and NDPA 2023, you have full control over non-essential cookies. You can accept all, reject non-essential cookies, or tailor your preferences.
                            <a href="cookie-policy.php" class="text-[#f7de7a] hover:underline font-medium inline-flex items-center gap-1 ml-1">Cookie Policy</a> and 
                            <a href="privacy-policy.php" class="text-[#f7de7a] hover:underline font-medium inline-flex items-center gap-1">Privacy Policy</a>.
                        </p>
                    </div>
                </div>

                <!-- Balanced Action Buttons (No Dark Patterns) -->
                <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto justify-end flex-shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-800">
                    <button type="button" 
                            onclick="window.JLM_COOKIE_CONSENT.rejectAll()" 
                            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-slate-600 hover:border-slate-400 bg-slate-800/90 hover:bg-slate-700 text-slate-200 hover:text-white text-xs sm:text-sm font-semibold transition-all duration-200 text-center active:scale-95 focus:outline-none focus:ring-2 focus:ring-slate-400">
                        Decline Non-Essential
                    </button>
                    <button type="button" 
                            onclick="window.JLM_COOKIE_CONSENT.openPreferences()" 
                            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-slate-700 hover:border-slate-500 bg-slate-900/80 hover:bg-slate-800 text-slate-300 hover:text-white text-xs sm:text-sm font-semibold transition-all duration-200 text-center active:scale-95 focus:outline-none focus:ring-2 focus:ring-slate-500">
                        Customize
                    </button>
                    <button type="button" 
                            onclick="window.JLM_COOKIE_CONSENT.acceptAll()" 
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] hover:from-[#252ebf] hover:to-[#f03b78] text-white text-xs sm:text-sm font-bold shadow-lg shadow-[#1b2299]/30 transition-all duration-200 hover:scale-[1.02] text-center active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#e4306d]">
                        Accept All
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preferences Modal -->
    <div id="jlm-cookie-modal" 
         class="fixed inset-0 z-[100000] hidden flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md overflow-y-auto" 
         role="dialog" 
         aria-modal="true" 
         aria-labelledby="jlm-cookie-modal-title"
         aria-hidden="true">
        
        <div class="w-full max-w-2xl bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl p-6 sm:p-8 text-white my-8 max-h-[90vh] flex flex-col transition-all duration-300">
            <!-- Modal Header -->
            <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-800">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xl">🛡️</span>
                        <h2 id="jlm-cookie-modal-title" class="text-xl sm:text-2xl font-bold text-white">Cookie & Privacy Preferences</h2>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-400">
                        Configure which cookie categories you permit. You can update or revoke these choices at any time from any page.
                    </p>
                </div>
                <button type="button" 
                        id="jlm-cookie-modal-close"
                        onclick="window.JLM_COOKIE_CONSENT.closePreferences()" 
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none focus:ring-2 focus:ring-[#e4306d]" 
                        aria-label="Close preferences modal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Categories Scrollable Area -->
            <div class="overflow-y-auto py-4 space-y-4 pr-1 my-2 flex-1">

                <!-- 1. Strictly Necessary (Always Active) -->
                <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/70">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm sm:text-base font-semibold text-white">Strictly Necessary Cookies</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                            Always Active
                        </span>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed mb-2">
                        Essential for site operation, secure login sessions, CSRF protection, and preserving your cookie consent choices. Cannot be turned off.
                    </p>
                    <div class="text-[11px] text-slate-400 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                        <span class="font-semibold text-slate-300">Examples:</span> <code class="text-emerald-400">PHPSESSID</code> (session), <code class="text-emerald-400">jlm_cookie_consent</code> (consent memory), <code class="text-emerald-400">csrf_token</code> (security).
                    </div>
                </div>

                <!-- 2. Functional & Preferences -->
                <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/70">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <div>
                            <span class="text-sm sm:text-base font-semibold text-white">Functional & Preferences</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="jlm-cookie-toggle-functional" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#1b2299] peer-checked:to-[#e4306d]"></div>
                        </label>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed mb-2">
                        Enables advanced user convenience, such as remembering your name and email for subsequent blog comments and maintaining your blog article likes across visits.
                    </p>
                    <div class="text-[11px] text-slate-400 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                        <span class="font-semibold text-slate-300">Examples:</span> <code class="text-blue-300">jlm_blog_liker</code>, <code class="text-blue-300">jlm_blog_commenter</code>.
                    </div>
                </div>

                <!-- 3. Analytics & Performance -->
                <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/70">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <div>
                            <span class="text-sm sm:text-base font-semibold text-white">Analytics & Performance</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="jlm-cookie-toggle-analytics" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#1b2299] peer-checked:to-[#e4306d]"></div>
                        </label>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed mb-2">
                        Gathers aggregated, non-identifying traffic and usage data so we can measure page loading performance, identify popular tutorials, and optimize server capacity.
                    </p>
                    <div class="text-[11px] text-slate-400 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                        <span class="font-semibold text-slate-300">Examples:</span> <code class="text-purple-300">_ga</code>, <code class="text-purple-300">_gid</code> (Google Analytics metrics).
                    </div>
                </div>

                <!-- 4. Marketing & Social -->
                <div class="p-4 rounded-2xl bg-slate-800/60 border border-slate-700/70">
                    <div class="flex items-center justify-between gap-4 mb-2">
                        <div>
                            <span class="text-sm sm:text-base font-semibold text-white">Marketing & Media Integration</span>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="jlm-cookie-toggle-marketing" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-[#1b2299] peer-checked:to-[#e4306d]"></div>
                        </label>
                    </div>
                    <p class="text-xs text-slate-300 leading-relaxed mb-2">
                        Allows social media sharing widgets and campaign attribution tracking for promotional initiatives.
                    </p>
                    <div class="text-[11px] text-slate-400 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                        <span class="font-semibold text-slate-300">Examples:</span> Social platform share tracking, marketing campaign cookies.
                    </div>
                </div>

            </div>

            <!-- Modal Footer Buttons -->
            <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" 
                            onclick="window.JLM_COOKIE_CONSENT.rejectAll()" 
                            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-slate-700 hover:border-slate-500 bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white text-xs sm:text-sm font-semibold transition-all">
                        Decline All
                    </button>
                    <button type="button" 
                            onclick="window.JLM_COOKIE_CONSENT.acceptAll()" 
                            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl border border-slate-700 hover:border-slate-500 bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white text-xs sm:text-sm font-semibold transition-all">
                        Accept All
                    </button>
                </div>
                <button type="button" 
                        onclick="saveCustomPreferences()" 
                        class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] hover:from-[#252ebf] hover:to-[#f03b78] text-white text-xs sm:text-sm font-bold shadow-lg shadow-[#1b2299]/30 transition-all hover:scale-[1.02] active:scale-95">
                    Save My Preferences
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function saveCustomPreferences() {
    var func = document.getElementById('jlm-cookie-toggle-functional') ? document.getElementById('jlm-cookie-toggle-functional').checked : false;
    var anal = document.getElementById('jlm-cookie-toggle-analytics') ? document.getElementById('jlm-cookie-toggle-analytics').checked : false;
    var mktg = document.getElementById('jlm-cookie-toggle-marketing') ? document.getElementById('jlm-cookie-toggle-marketing').checked : false;
    window.JLM_COOKIE_CONSENT.saveCustom(func, anal, mktg);
}
</script>
<script src="<?php echo htmlspecialchars($scriptUrl); ?>"></script>
