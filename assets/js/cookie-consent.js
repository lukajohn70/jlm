/**
 * JLM Creative Media - Cookie Consent & Privacy Manager
 * Fully compliant with GDPR (EU/UK), ePrivacy Directive, NDPA 2023 (Nigeria), and CCPA/CPRA.
 */
(function() {
    'use strict';

    var COOKIE_NAME = 'jlm_cookie_consent';
    var COOKIE_LIFETIME_DAYS = 365;
    var SCHEMA_VERSION = '1.0';

    var DEFAULT_CONSENT = {
        version: SCHEMA_VERSION,
        necessary: true,
        functional: false,
        analytics: false,
        marketing: false,
        timestamp: null
    };

    /**
     * Reads a cookie by name
     */
    function getCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) {
                var val = c.substring(nameEQ.length, c.length);
                try {
                    return decodeURIComponent(val);
                } catch (e) {
                    return val;
                }
            }
        }
        return null;
    }

    /**
     * Writes a cookie with proper security attributes
     */
    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        var isSecure = window.location.protocol === 'https:';
        var secureFlag = isSecure ? '; Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax' + secureFlag;
    }

    /**
     * Deletes a cookie across all possible path and domain scopes
     */
    function eraseCookie(name) {
        var hostname = window.location.hostname;
        var hostParts = hostname.split('.');
        var paths = ['/', window.location.pathname];

        // Clear without domain
        paths.forEach(function(p) {
            document.cookie = name + '=; Path=' + p + '; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax';
            document.cookie = name + '=; Path=' + p + '; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax; Secure';
        });

        // Clear with subdomains/parent domain
        while (hostParts.length > 1) {
            var domain = '.' + hostParts.join('.');
            paths.forEach(function(p) {
                document.cookie = name + '=; Domain=' + domain + '; Path=' + p + '; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax';
                document.cookie = name + '=; Domain=' + domain + '; Path=' + p + '; Expires=Thu, 01 Jan 1970 00:00:01 GMT; SameSite=Lax; Secure';
            });
            hostParts.shift();
        }
    }

    /**
     * Retrieve stored consent from cookie or localStorage
     */
    function getStoredConsent() {
        var raw = getCookie(COOKIE_NAME);
        if (!raw) {
            try {
                raw = localStorage.getItem(COOKIE_NAME);
            } catch (e) {
                raw = null;
            }
        }
        if (!raw) return null;

        try {
            var parsed = JSON.parse(raw);
            if (parsed && typeof parsed === 'object') {
                return {
                    version: parsed.version || SCHEMA_VERSION,
                    necessary: true,
                    functional: Boolean(parsed.functional),
                    analytics: Boolean(parsed.analytics),
                    marketing: Boolean(parsed.marketing),
                    timestamp: parsed.timestamp || null
                };
            }
        } catch (e) {
            // invalid JSON
        }
        return null;
    }

    /**
     * Persist consent object to both cookie and localStorage
     */
    function saveConsent(consent) {
        consent.necessary = true;
        consent.version = SCHEMA_VERSION;
        consent.timestamp = new Date().toISOString();

        var json = JSON.stringify(consent);
        setCookie(COOKIE_NAME, json, COOKIE_LIFETIME_DAYS);
        try {
            localStorage.setItem(COOKIE_NAME, json);
        } catch (e) {
            // storage failed (e.g. private mode)
        }

        enforceConsentRules(consent);
        dispatchConsentEvent(consent);
    }

    /**
     * Purges unconsented cookies and sets script disable flags
     */
    function enforceConsentRules(consent) {
        // Handle Analytics cookies
        if (!consent.analytics) {
            // Disable Google Analytics tracking if gtag exists
            if (typeof window['ga-disable-GA_MEASUREMENT_ID'] !== 'undefined') {
                window['ga-disable-GA_MEASUREMENT_ID'] = true;
            }
            // List of known analytics cookies to delete
            var analyticsCookies = ['_ga', '_gid', '_gat', '_gat_gtag', '_gac', '_gat_UA'];
            var allCookies = document.cookie.split(';');
            for (var i = 0; i < allCookies.length; i++) {
                var cName = allCookies[i].split('=')[0].trim();
                for (var j = 0; j < analyticsCookies.length; j++) {
                    if (cName.indexOf(analyticsCookies[j]) === 0) {
                        eraseCookie(cName);
                    }
                }
            }
        }

        // Handle Functional cookies
        if (!consent.functional) {
            eraseCookie('jlm_blog_liker');
            eraseCookie('jlm_blog_commenter');
        }

        // Handle Marketing cookies
        if (!consent.marketing) {
            var marketingCookies = ['_fbp', '_fbc', 'fr', 'tr', 'lidc', 'bcookie'];
            marketingCookies.forEach(function(cName) {
                eraseCookie(cName);
            });
        }
    }

    /**
     * Dispatch consent change event for any listeners or third-party tags
     */
    function dispatchConsentEvent(consent) {
        try {
            var evt = new CustomEvent('jlm:cookie-consent-updated', { detail: consent });
            window.dispatchEvent(evt);
            document.dispatchEvent(evt);
        } catch (e) {
            // legacy browser support
        }
    }

    /**
     * Main Controller Object
     */
    var JLM_COOKIE_CONSENT = {
        getConsent: function() {
            return getStoredConsent() || Object.assign({}, DEFAULT_CONSENT);
        },

        hasConsent: function(category) {
            if (!category || category === 'necessary') return true;
            var c = this.getConsent();
            return Boolean(c[category]);
        },

        isConfigured: function() {
            return getStoredConsent() !== null;
        },

        acceptAll: function() {
            var consent = {
                version: SCHEMA_VERSION,
                necessary: true,
                functional: true,
                analytics: true,
                marketing: true
            };
            saveConsent(consent);
            this.hideBanner();
            this.closePreferences();
        },

        rejectAll: function() {
            var consent = {
                version: SCHEMA_VERSION,
                necessary: true,
                functional: false,
                analytics: false,
                marketing: false
            };
            saveConsent(consent);
            this.hideBanner();
            this.closePreferences();
        },

        saveCustom: function(functional, analytics, marketing) {
            var consent = {
                version: SCHEMA_VERSION,
                necessary: true,
                functional: Boolean(functional),
                analytics: Boolean(analytics),
                marketing: Boolean(marketing)
            };
            saveConsent(consent);
            this.hideBanner();
            this.closePreferences();
        },

        showBanner: function() {
            var banner = document.getElementById('jlm-cookie-banner');
            if (banner) {
                banner.classList.remove('hidden');
                banner.setAttribute('aria-hidden', 'false');
            }
        },

        hideBanner: function() {
            var banner = document.getElementById('jlm-cookie-banner');
            if (banner) {
                banner.classList.add('hidden');
                banner.setAttribute('aria-hidden', 'true');
            }
        },

        openPreferences: function() {
            var modal = document.getElementById('jlm-cookie-modal');
            if (!modal) return;

            var current = this.getConsent();
            var funcToggle = document.getElementById('jlm-cookie-toggle-functional');
            var analToggle = document.getElementById('jlm-cookie-toggle-analytics');
            var mktgToggle = document.getElementById('jlm-cookie-toggle-marketing');

            if (funcToggle) funcToggle.checked = Boolean(current.functional);
            if (analToggle) analToggle.checked = Boolean(current.analytics);
            if (mktgToggle) mktgToggle.checked = Boolean(current.marketing);

            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('jlm-cookie-modal-open');

            // Focus on close or first button
            var closeBtn = document.getElementById('jlm-cookie-modal-close');
            if (closeBtn) closeBtn.focus();
        },

        closePreferences: function() {
            var modal = document.getElementById('jlm-cookie-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
            }
            document.body.classList.remove('jlm-cookie-modal-open');
        },

        init: function() {
            var self = this;
            var stored = getStoredConsent();

            if (stored) {
                enforceConsentRules(stored);
                self.hideBanner();
            } else {
                // Show banner after brief delay for smooth appearance
                setTimeout(function() {
                    self.showBanner();
                }, 400);
            }

            // Keyboard listeners (ESC to close modal)
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    var modal = document.getElementById('jlm-cookie-modal');
                    if (modal && !modal.classList.contains('hidden')) {
                        self.closePreferences();
                    }
                }
            });
        }
    };

    // Expose globals
    window.JLM_COOKIE_CONSENT = JLM_COOKIE_CONSENT;
    window.openCookieSettings = function() {
        JLM_COOKIE_CONSENT.openPreferences();
    };

    // Initialize once DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            JLM_COOKIE_CONSENT.init();
        });
    } else {
        JLM_COOKIE_CONSENT.init();
    }
})();
