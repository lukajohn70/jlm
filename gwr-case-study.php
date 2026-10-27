<?php
session_start();

// Error reporting for development only
if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || ($_SERVER['HTTP_HOST'] ?? '') === '127.0.0.1') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$page_title = 'Guinness World Record Technical Case Study';
$page_description = 'How JLM engineered 431 hours of continuous multi-camera capture, master timecoded audio, and 100% uptime for the Guinness World Record longest reading marathon by a team.';
$current_page = 'portfolio';

require_once __DIR__ . '/includes/header.php';
?>

<style>
  .jlm-prose { color: #111827; }
  .jlm-prose p { margin: 0 0 1.25rem; color: #374151; line-height: 1.85; font-size: 1.1rem; }
  .jlm-prose h2 { font-size: 1.95rem; font-weight: 800; color: #111827; margin: 2.5rem 0 1rem; line-height: 1.2; letter-spacing: -0.02em; }
  .jlm-prose h3 { font-size: 1.4rem; font-weight: 700; color: #1f2937; margin: 1.75rem 0 0.75rem; line-height: 1.3; }
  .jlm-prose ul { margin: 0 0 1.5rem 0; padding-left: 1.5rem; list-style: disc; color: #374151; }
  .jlm-prose li { margin: 0.5rem 0; line-height: 1.75; font-size: 1.05rem; }
  .jlm-prose li::marker { color: #e4306d; font-weight: 700; }
  .jlm-prose blockquote {
    position: relative;
    margin: 2rem 0;
    padding: 1.25rem 1.5rem;
    border-left: 5px solid #e4306d;
    background: linear-gradient(135deg, rgba(27,34,153,0.04) 0%, rgba(228,48,109,0.05) 100%);
    border-radius: 16px;
    color: #374151;
    box-shadow: 0 10px 30px rgba(15,23,42,0.04);
  }
  .jlm-animate-up { opacity: 0; transform: translateY(24px); animation: jlmUp .75s cubic-bezier(.2,.8,.2,1) forwards; }
  .jlm-animate-up-delay { animation-delay: .12s; }
  .jlm-animate-up-delay-2 { animation-delay: .22s; }
  @keyframes jlmUp { to { opacity: 1; transform: translateY(0); } }
</style>

<!-- Hero Section (Consistent with JLM subpage architecture) -->
<section class="relative overflow-hidden bg-gradient-to-br from-white via-[#f8fafc] to-[#eef2ff] border-b border-gray-100">
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(27,34,153,0.12),_transparent_35%),radial-gradient(circle_at_top_right,_rgba(228,48,109,0.12),_transparent_32%)] pointer-events-none"></div>
  
  <div class="relative container mx-auto px-4 lg:px-8 py-14 md:py-20">
    <div class="max-w-4xl mx-auto">
      <!-- Eyebrow Badge -->
      <div class="jlm-animate-up inline-flex items-center gap-2 rounded-full bg-white/90 backdrop-blur-sm px-4 py-1.5 text-xs sm:text-sm font-semibold text-gray-800 shadow-sm border border-gray-200">
        <span class="w-2 h-2 rounded-full bg-gradient-to-r from-[#1b2299] to-[#e4306d]"></span>
        <span>Guinness World Records™ Title Documentation</span>
      </div>

      <!-- Breadcrumbs -->
      <nav class="jlm-animate-up mt-6 text-sm text-gray-500" aria-label="Breadcrumb">
        <a href="index.php#home" class="hover:text-[#1b2299] transition-colors">Home</a>
        <span class="mx-2 text-gray-400">/</span>
        <a href="index.php#portfolio" class="hover:text-[#1b2299] transition-colors">Portfolio</a>
        <span class="mx-2 text-gray-400">/</span>
        <span class="text-gray-800 font-medium">Guinness World Record Case Study</span>
      </nav>

      <!-- Headline -->
      <h1 class="jlm-animate-up jlm-animate-up-delay mt-6 text-3xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.1] text-gray-900">
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#111827] via-[#1b2299] to-[#e4306d]">
          Engineering 431 Hours of 100% Uptime: Documenting World History
        </span>
      </h1>

      <!-- Lead Excerpt -->
      <p class="jlm-animate-up jlm-animate-up-delay mt-6 text-lg sm:text-xl text-gray-600 leading-relaxed max-w-3xl">
        How JLM engineered continuous multi-camera capture, master timecoded audio, and unbroken global streaming for the Longest Marathon Reading Aloud by a Team—without a single dropped frame across 18 consecutive days.
      </p>

      <!-- Metadata Pills Bar -->
      <div class="jlm-animate-up jlm-animate-up-delay-2 mt-8 flex flex-wrap items-center gap-3 text-xs sm:text-sm text-gray-700">
        <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm border border-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#1b2299]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          The Library, Lagos
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm border border-gray-200 font-bold text-[#1b2299]">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#e4306d]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path d="M12 6v6l4 2" stroke-width="2"/></svg>
          431h 31m 25s
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-sm border border-gray-200">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#1b2299]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
          79 Works Read
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 text-emerald-800 px-4 py-2 shadow-sm border border-emerald-200 font-semibold">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          GWR Ratified
        </span>
      </div>
    </div>
  </div>
</section>

<!-- Content Section -->
<section class="bg-white py-16">
  <div class="container mx-auto px-4 lg:px-8">
    <div class="max-w-4xl mx-auto">

      <!-- Featured Image Banner -->
      <div class="jlm-animate-up rounded-3xl overflow-hidden shadow-xl border border-gray-100 bg-slate-950 p-8 sm:p-12 mb-14 text-center relative">
        <div class="absolute inset-0 bg-[radial-gradient(#1b2299_1px,transparent_1px)] [background-size:20px_20px] opacity-25"></div>
        <img src="<?php echo asset_url('assets/images/clients/naijareadfest.png'); ?>" alt="Naija ReadFest Marathon Reading Guinness World Record" class="max-h-36 max-w-full mx-auto relative z-10 drop-shadow-lg object-contain">
        <div class="relative z-10 mt-6 inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-semibold text-gray-200">
          Official Technical Team &bull; Nigeria Reads Initiative
        </div>
      </div>

      <!-- Key Numbers Grid (Consistent with JLM count-up style) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
        <div class="p-6 rounded-2xl bg-gray-50/80 border border-gray-200/80 text-center transition-all hover:shadow-lg hover:-translate-y-1">
          <div class="text-3xl sm:text-4xl font-extrabold text-[#1b2299] mb-1">431h 31m</div>
          <div class="text-xs font-bold uppercase tracking-wider text-gray-600">Official Duration</div>
          <div class="text-[11px] text-gray-400 mt-1">18 Days Non-Stop</div>
        </div>
        <div class="p-6 rounded-2xl bg-gray-50/80 border border-gray-200/80 text-center transition-all hover:shadow-lg hover:-translate-y-1">
          <div class="text-3xl sm:text-4xl font-extrabold text-emerald-600 mb-1">0 Sec</div>
          <div class="text-xs font-bold uppercase tracking-wider text-gray-600">Downtime</div>
          <div class="text-[11px] text-gray-400 mt-1">Zero frames dropped</div>
        </div>
        <div class="p-6 rounded-2xl bg-gray-50/80 border border-gray-200/80 text-center transition-all hover:shadow-lg hover:-translate-y-1">
          <div class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-1">79 Books</div>
          <div class="text-xs font-bold uppercase tracking-wider text-gray-600">Authors Read</div>
          <div class="text-[11px] text-gray-400 mt-1">Nigerian Literature</div>
        </div>
        <div class="p-6 rounded-2xl bg-gray-50/80 border border-gray-200/80 text-center transition-all hover:shadow-lg hover:-translate-y-1">
          <div class="text-3xl sm:text-4xl font-extrabold text-[#e4306d] mb-1">100%</div>
          <div class="text-xs font-bold uppercase tracking-wider text-gray-600">Verification</div>
          <div class="text-[11px] text-gray-400 mt-1">GWR Adjudicated</div>
        </div>
      </div>

      <!-- Editorial Body Copy -->
      <div class="jlm-prose">
        <h2>1. The Stakes: Zero Margin for Error</h2>
        <p>
          Guinness World Records enforces some of the most exacting evidence verification guidelines in international record-keeping. For multi-day endurance achievements like the <strong>Longest Marathon Reading Aloud by a Team</strong>, technical integrity is not merely a preference—it is the deciding factor between a certified world record and immediate disqualification.
        </p>

        <blockquote>
          <p class="font-medium text-gray-800">
            “If the camera stopped rolling for even five minutes, or if audio clipped during a midnight reader handover, over two weeks of non-stop human endurance would have been voided on technical grounds.”
          </p>
        </blockquote>

        <p>
          To meet Guinness World Records standards, our technical deployment had to satisfy four uncompromising criteria simultaneously:
        </p>

        <ul>
          <li><strong>Unbroken Multi-Angle Video Coverage:</strong> Independent primary and backup video feeds operating 24 hours a day for over two weeks without a single power drop or storage hiccup.</li>
          <li><strong>Facial &amp; Textual Verification:</strong> Continuous, clear framing of the active reader’s face and mouth movement (to prove continuous vocalization) alongside synchronized overhead shots of the printed book text.</li>
          <li><strong>Acoustic Audibility of Every Word:</strong> Every passage across 79 complete Nigerian literary works had to be captured with pristine clarity to allow remote adjudicators to audit continuity.</li>
          <li><strong>Atomic Timecode Synchronization:</strong> Burn-in timecodes synchronized with physical witness logbooks, stewarding logs, and independent adjudicator records.</li>
        </ul>

        <h2>2. Technical Architecture &amp; Engineering</h2>
        <p>
          JLM approached the project as a mission-critical infrastructure challenge. Drawing on our experience in live multi-camera broadcasting and commercial video engineering, we deployed a three-tier failover architecture:
        </p>

        <h3>Visual Evidence Matrix</h3>
        <p>
          Three broadcast camera positions were locked into dedicated isolated (ISO) recording channels. Angle A maintained a continuous tight shot on the active reader, Angle B captured a macro perspective of the book pages as they were read, and Angle C maintained a wide overview of the witness podium and official timekeepers. All three angles were recorded synchronously with master timecodes.
        </p>

        <h3>Continuous Audio Engineering &amp; Relay Transitions</h3>
        <p>
          In a relay reading marathon, the highest risk of dropouts occurs during the transition between readers. We implemented dual broadcast-grade wireless lapels paired with calibrated room boundary microphones. This setup allowed the incoming reader to take over the text seamlessly without introducing clipping, level jumps, or acoustic dropouts.
        </p>

        <h3>Power Infrastructure &amp; Thermal Management</h3>
        <p>
          Power interruptions are fatal to marathon attempts. We isolated the entire technical control desk behind online double-conversion UPS battery banks paired with automated dual-generator switchgear. Operating continuously for 431 hours also presented severe thermal challenges; equipment was actively cooled and monitored through thermal sensors to eliminate hardware throttling.
        </p>

        <h3>Data Ingestion &amp; Checksum Verification</h3>
        <p>
          Capturing 431 hours of continuous footage generated terabytes of high-bitrate master files. JLM established a rolling media swap schedule: cards were cloned to high-speed NVMe RAID arrays, verified against SHA-256 hash checksums, and duplicated to offsite cold storage. Over 18 days of operation, zero corrupt files or dropped frames occurred.
        </p>

        <h2>3. The Record-Breaking Relay Team</h2>
        <p>
          Organized under the <strong>Nigeria Reads Initiative</strong>, five marathon readers completed the historic challenge, representing diverse geopolitical zones and tribes across Nigeria in a shared mission to champion literacy and celebrate homegrown literature:
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 my-8 not-prose">
          <div class="p-5 rounded-2xl bg-gray-50 border border-gray-200">
            <div class="font-bold text-gray-900 text-base">John J. Obot</div>
            <div class="text-xs text-[#1b2299] font-semibold mt-0.5">Akwa Ibom State</div>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">Experienced reading marathon champion and dedicated advocate for literacy education in Nigeria.</p>
          </div>
          <div class="p-5 rounded-2xl bg-gray-50 border border-gray-200">
            <div class="font-bold text-gray-900 text-base">Keturah Heman</div>
            <div class="text-xs text-[#1b2299] font-semibold mt-0.5">Gombe State</div>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">Representing the North-East geopolitical zone with outstanding endurance and vocal clarity.</p>
          </div>
          <div class="p-5 rounded-2xl bg-gray-50 border border-gray-200">
            <div class="font-bold text-gray-900 text-base">Stephen A. Oyelami</div>
            <div class="text-xs text-[#1b2299] font-semibold mt-0.5">Osun State</div>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">South-West representative who powered through high-intensity overnight relay reading sessions.</p>
          </div>
          <div class="p-5 rounded-2xl bg-gray-50 border border-gray-200">
            <div class="font-bold text-gray-900 text-base">Preciouslight I. Ukachi</div>
            <div class="text-xs text-[#1b2299] font-semibold mt-0.5">Imo State</div>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">South-East representative delivering consistent, measured reading pacing across complex prose.</p>
          </div>
          <div class="p-5 rounded-2xl bg-gray-50 border border-gray-200 sm:col-span-2">
            <div class="font-bold text-gray-900 text-base">Temitope Ogunremi</div>
            <div class="text-xs text-[#1b2299] font-semibold mt-0.5">Kogi State</div>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">North-Central representative whose endurance carried the final marathon push beyond the 400-hour barrier.</p>
          </div>
        </div>

        <h2>4. The Outcome: Official Ratification</h2>
        <p>
          Following the conclusion of <strong>431 hours, 31 minutes, and 25 seconds</strong> of continuous read-aloud time at The Library in Lagos, JLM assembled and delivered the complete, verified evidence package directly to Guinness World Records adjudicators.
        </p>
        <p>
          Guinness World Records officially approved all submissions with <strong>100% evidence acceptance</strong>, formally ratifying the team as the new world record holders and surpassing the previous 365-hour benchmark.
        </p>

        <!-- Enterprise Value Card (Matches JLM conversion blocks) -->
        <div class="my-12 p-8 rounded-3xl bg-gradient-to-br from-white via-gray-50 to-[#1b2299]/5 border border-gray-200 shadow-lg not-prose">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#1b2299]/10 text-[#1b2299] text-xs font-bold uppercase mb-3">
            Enterprise Reliability
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-3">What This Means For Your Next Production</h3>
          <p class="text-gray-600 text-base leading-relaxed mb-6">
            If our technical team can sustain 18 consecutive days of unbroken, multi-camera broadcasting under the world's most unforgiving compliance standards, your high-stakes corporate conference, annual general meeting, or live broadcast is in bulletproof hands.
          </p>
          <div class="flex flex-col sm:flex-row gap-4">
            <a href="booking.php" class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white font-bold text-sm shadow-lg hover:scale-105 transition-all">
              Book JLM for Your Event
            </a>
            <a href="index.php#portfolio" class="inline-flex items-center justify-center px-6 py-3.5 rounded-xl border border-gray-300 hover:border-gray-400 bg-white text-gray-800 font-semibold text-sm transition">
              Explore More Work
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
