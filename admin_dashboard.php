<?php
session_start();
require_once __DIR__ . '/config/config.php';
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: login.php');
    exit;
}
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';
$admin_email = $_SESSION['admin_email'] ?? 'admin@jlm.com.ng';

// Fetch stats
$totalContacts = $pdo->query('SELECT COUNT(*) FROM contact_submissions')->fetchColumn();
$totalBookings = $pdo->query('SELECT COUNT(*) FROM booking_submissions')->fetchColumn();
$pendingTestimonials = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status='pending'")->fetchColumn();
$totalTestimonials = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status='approved'")->fetchColumn();
$totalSubscribers = $pdo->query("SELECT COUNT(*) FROM email_subscribers WHERE status='active'")->fetchColumn();
$totalCampaigns = $pdo->query("SELECT COUNT(*) FROM email_campaigns")->fetchColumn();
$totalBlogPosts = $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
$publishedBlogPosts = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status='published'")->fetchColumn();

// Fetch recent contacts
$contacts = $pdo->query('SELECT * FROM contact_submissions ORDER BY submitted_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
// Fetch recent bookings
$bookings = $pdo->query('SELECT * FROM booking_submissions ORDER BY submitted_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
// Fetch testimonials
$testimonials = $pdo->query('SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);

$tabs = [
    'overview' => ['Overview', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"/></svg>'],
    'tickets' => ['Tickets', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>'],
    'contacts' => ['Contacts', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg>'],
    'bookings' => ['Bookings', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>'],
    'testimonials' => ['Testimonials', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 9 8.5 12 2"/></svg>'],
    'blog' => ['Blog', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'],
    'subscriptions' => ['Subscriptions', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>'],
    'jogigs' => ['JoGigs', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"/></svg>'],
    'settings' => ['Settings', '<svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>'],
];

// Handle actions (approve/reject testimonial, confirm/delete booking, mark contact as read)
$toast = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_testimonial'])) {
        $id = (int)$_POST['approve_testimonial'];
        $pdo->prepare('UPDATE testimonials SET status="approved" WHERE id=?')->execute([$id]);
        $toast = 'Testimonial approved!';
    } elseif (isset($_POST['reject_testimonial'])) {
        $id = (int)$_POST['reject_testimonial'];
        $pdo->prepare('UPDATE testimonials SET status="rejected" WHERE id=?')->execute([$id]);
        $toast = 'Testimonial rejected.';
    } elseif (isset($_POST['confirm_booking'])) {
        $id = (int)$_POST['confirm_booking'];
        $pdo->prepare('UPDATE booking_submissions SET status="confirmed" WHERE id=?')->execute([$id]);
        $toast = 'Booking confirmed!';
    } elseif (isset($_POST['delete_booking'])) {
        $id = (int)$_POST['delete_booking'];
        $pdo->prepare('DELETE FROM booking_submissions WHERE id=?')->execute([$id]);
        $toast = 'Booking deleted.';
    } elseif (isset($_POST['mark_contact_read'])) {
        $id = (int)$_POST['mark_contact_read'];
        $pdo->prepare('UPDATE contact_submissions SET status="read" WHERE id=?')->execute([$id]);
        $toast = 'Contact marked as read.';
    }
    $_SESSION['toast'] = $toast;
    header('Location: '.$_SERVER['REQUEST_URI']); exit;
}
$toast = $_SESSION['toast'] ?? '';
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | JLM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="w-full">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Admin Dashboard</span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600 hidden sm:inline">Welcome, <?php echo htmlspecialchars($admin_email); ?></span>
                <a href="?logout=1" class="px-4 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition">Sign Out</a>
            </div>
        </div>
    </header>
    <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8 py-8 flex flex-col md:flex-row gap-6">
        <!-- Sidebar -->
        <aside class="w-full md:w-60 flex-shrink-0 mb-4 md:mb-0">
            <nav class="bg-white rounded-2xl shadow flex md:flex-col flex-row overflow-x-auto md:overflow-visible">
                <?php foreach ($tabs as $id => [$name, $icon]): ?>
                    <a href="?tab=<?php echo $id; ?>" 
                       class="flex items-center px-4 py-3 md:py-3 md:px-6 text-base font-medium transition-colors <?php echo $tab === $id ? 'bg-gradient-to-r from-blue-800 to-pink-600 text-white shadow' : 'text-gray-700 hover:bg-gray-100'; ?> md:rounded-xl md:mb-2 whitespace-nowrap"
                       title="<?php echo $name; ?>">
                        <?php echo $icon . $name; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <!-- Main Content -->
        <main class="flex-1 w-full">
            <?php if ($toast): ?>
            <div id="toast" class="fixed top-6 right-6 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 animate-fade-in">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                <span><?php echo htmlspecialchars($toast); ?></span>
                <button onclick="document.getElementById('toast').remove()" class="ml-4 text-white hover:text-gray-200">&times;</button>
            </div>
            <script>
                setTimeout(function(){
                    var t = document.getElementById('toast');
                    if (t) t.remove();
                }, 3500);
            </script>
            <?php endif; ?>
            <?php if ($tab === 'overview'): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                    <div class="rounded-xl shadow p-6 flex flex-col items-start bg-gradient-to-br from-blue-600 to-blue-400 text-white">
                        <div class="mb-2"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg></div>
                        <span class="text-sm mb-1">Total Contacts</span>
                        <span class="text-2xl font-bold "><?php echo $totalContacts; ?></span>
                    </div>
                    <div class="rounded-xl shadow p-6 flex flex-col items-start bg-gradient-to-br from-pink-500 to-pink-300 text-white">
                        <div class="mb-2"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg></div>
                        <span class="text-sm mb-1">Total Bookings</span>
                        <span class="text-2xl font-bold "><?php echo $totalBookings; ?></span>
                    </div>
                    <div class="rounded-xl shadow p-6 flex flex-col items-start bg-gradient-to-br from-yellow-400 to-yellow-200 text-yellow-900">
                        <div class="mb-2"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 8-8"/></svg></div>
                        <span class="text-sm mb-1">Pending Reviews</span>
                        <span class="text-2xl font-bold "><?php echo $pendingTestimonials; ?></span>
                    </div>
                    <div class="rounded-xl shadow p-6 flex flex-col items-start bg-gradient-to-br from-green-400 to-green-200 text-green-900">
                        <div class="mb-2"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 9 8.5 12 2"/></svg></div>
                        <span class="text-sm mb-1">Published Reviews</span>
                        <span class="text-2xl font-bold "><?php echo $totalTestimonials; ?></span>
                    </div>
                    <div class="rounded-xl shadow p-6 flex flex-col items-start bg-gradient-to-br from-purple-500 to-purple-300 text-white">
                        <div class="mb-2"><svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg></div>
                        <span class="text-sm mb-1">Blog Posts</span>
                        <span class="text-2xl font-bold "><?php echo $totalBlogPosts; ?></span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl shadow p-6">
                        <h2 class="text-lg font-semibold mb-2">Recent Contacts</h2>
                        <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-gray-600 font-semibold"><th class="py-2 px-2">Name</th><th class="py-2 px-2">Email</th><th class="py-2 px-2">Subject</th><th class="py-2 px-2">Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($contacts as $c): ?>
                                <tr class="border-b last:border-0">
                                    <td class="py-2 px-2"><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td class="py-2 px-2"><?php echo htmlspecialchars($c['email']); ?></td>
                                    <td class="py-2 px-2"><?php echo htmlspecialchars($c['subject'] ?? ''); ?></td>
                                    <td class="py-2 px-2"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($c['submitted_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow p-6">
                        <h2 class="text-lg font-semibold mb-2">Recent Bookings</h2>
                        <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-gray-600 font-semibold"><th class="py-2 px-2">Name</th><th class="py-2 px-2">Email</th><th class="py-2 px-2">Service</th><th class="py-2 px-2">Date</th></tr></thead>
                            <tbody>
                            <?php foreach ($bookings as $b): ?>
                                <tr class="border-b last:border-0">
                                    <td class="py-2 px-2"><?php echo htmlspecialchars($b['name']); ?></td>
                                    <td class="py-2 px-2"><?php echo htmlspecialchars($b['email']); ?></td>
                                    <td class="py-2 px-2"><?php echo htmlspecialchars($b['service']); ?></td>
                                    <td class="py-2 px-2"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($b['submitted_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            <?php elseif ($tab === 'tickets'): ?>
                <div class="bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl shadow p-6 mb-8 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-indigo-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                        <div>
                            <h2 class="text-xl font-bold text-indigo-900 mb-1">Ticket Management</h2>
                            <p class="text-indigo-700">Track and manage all form submissions with unique ticket numbers.</p>
                        </div>
                    </div>
                    <a href="ticket_manage.php" class="px-3 py-2 border border-indigo-500 text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition">Manage Tickets</a>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Ticket System Overview</h2>
                        <a href="ticket_lookup.php" target="_blank" class="px-3 py-2 border border-indigo-500 text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition">Public Lookup</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-blue-900 mb-2">How It Works</h3>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Every form submission gets a unique ticket number</li>
                                <li>• Users can track their submissions online</li>
                                <li>• Admins can manage and update ticket status</li>
                                <li>• Full activity history is maintained</li>
                            </ul>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-green-900 mb-2">Benefits</h3>
                            <ul class="text-sm text-green-700 space-y-1">
                                <li>• Better customer service tracking</li>
                                <li>• Organized workflow management</li>
                                <li>• Professional communication system</li>
                                <li>• Improved accountability</li>
                            </ul>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h3 class="font-semibold text-purple-900 mb-2">Quick Actions</h3>
                            <ul class="text-sm text-purple-700 space-y-1">
                                <li>• <a href="ticket_manage.php" class="underline">View All Tickets</a></li>
                                <li>• <a href="ticket_lookup.php" target="_blank" class="underline">Public Lookup Page</a></li>
                                <li>• Search by ticket number</li>
                                <li>• Update status and assign tickets</li>
                            </ul>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-900 mb-2">Ticket Number Format</h3>
                        <p class="text-sm text-gray-700 mb-2">All tickets follow the format: <code class="bg-white px-2 py-1 rounded text-indigo-600 font-mono">JLM-YYYYMMDDHHMMSS-XXXX</code></p>
                        <p class="text-sm text-gray-600">Example: <code class="bg-white px-2 py-1 rounded text-indigo-600 font-mono">JLM-20241201143022-A1B2</code></p>
                    </div>
                </div>
            <?php elseif ($tab === 'contacts'): ?>
                <div class="bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl shadow p-6 mb-8 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-blue-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/></svg>
                        <div>
                            <h2 class="text-xl font-bold text-blue-900 mb-1">Contact Submissions</h2>
                            <p class="text-blue-700">All recent contact form entries are shown below.</p>
                        </div>
                    </div>
                    <a href="contact_manage.php" class="px-3 py-2 border border-blue-500 text-blue-600 rounded-lg font-semibold hover:bg-blue-50 transition">Manage Contacts</a>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">Contact Submissions</h2>
                        <a href="?tab=contacts&export=contacts" class="px-3 py-2 border border-blue-500 text-blue-600 rounded-lg font-semibold hover:bg-blue-50 transition" title="Export contact submissions to CSV file">Export to CSV</a>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead><tr class="text-gray-600 font-semibold"><th class="py-2 px-2">Name</th><th class="py-2 px-2">Email</th><th class="py-2 px-2">Subject</th><th class="py-2 px-2">Message</th><th class="py-2 px-2">Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($contacts as $c): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-2 px-2"><?php echo htmlspecialchars($c['name']); ?></td>
                                <td class="py-2 px-2"><?php echo htmlspecialchars($c['email']); ?></td>
                                <td class="py-2 px-2"><?php echo htmlspecialchars($c['subject'] ?? ''); ?></td>
                                <td class="py-2 px-2 max-w-xs truncate" title="<?php echo htmlspecialchars($c['message'] ?? ''); ?>"><?php echo htmlspecialchars(mb_strimwidth($c['message'] ?? '', 0, 40, '...')); ?></td>
                                <td class="py-2 px-2"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($c['submitted_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            <?php elseif ($tab === 'bookings'): ?>
                <div class="bg-gradient-to-br from-pink-100 to-pink-50 rounded-xl shadow p-6 mb-8 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-pink-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                        <div>
                            <h2 class="text-xl font-bold text-pink-900 mb-1">Booking Requests</h2>
                            <p class="text-pink-700">All recent booking requests are shown below.</p>
                        </div>
                    </div>
                                            <div class="flex space-x-2">
                        <a href="?tab=bookings&export=bookings" class="px-3 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition" title="Export booking requests to CSV file">Export to CSV</a>
                        <a href="booking_manage.php" class="px-3 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition" title="Manage booking requests">Manage Bookings</a>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6 mb-8">
                    <input type="text" id="bookingSearch" placeholder="Search bookings..." class="mb-4 px-3 py-2 border rounded-lg w-full max-w-xs focus:ring-2 focus:ring-blue-500" />
                    <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" id="bookingsTable">
                        <thead><tr class="text-gray-600 font-semibold"><th class="py-2 px-2">Name</th><th class="py-2 px-2">Email</th><th class="py-2 px-2">Service</th><th class="py-2 px-2">Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($bookings as $b): ?>
                            <tr class="border-b last:border-0">
                                <td class="py-2 px-2"><?php echo htmlspecialchars($b['name']); ?></td>
                                <td class="py-2 px-2"><?php echo htmlspecialchars($b['email']); ?></td>
                                <td class="py-2 px-2"><?php echo htmlspecialchars($b['service']); ?></td>
                                <td class="py-2 px-2"><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($b['submitted_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Project Due Dates Calendar</h2>
                    <div id="calendar"></div>
                    <!-- Modal for booking details -->
                    <div id="bookingModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
                        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md relative">
                            <button onclick="closeBookingModal()" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
                            <h3 class="text-lg font-bold mb-2" id="modalBookingName"></h3>
                            <div class="mb-2"><b>Email:</b> <span id="modalBookingEmail"></span></div>
                            <div class="mb-2"><b>Service:</b> <span id="modalBookingService"></span></div>
                            <div class="mb-2"><b>Date:</b> <span id="modalBookingDate"></span></div>
                        </div>
                    </div>
                    <script>
                        // Booking data for modal
                        var bookingData = [
                            <?php foreach ($bookings as $b): ?>
                            {
                                name: '<?php echo addslashes($b['name']); ?>',
                                email: '<?php echo addslashes($b['email']); ?>',
                                service: '<?php echo addslashes($b['service']); ?>',
                                date: '<?php echo date('Y-m-d', strtotime($b['submitted_at'])); ?>',
                            },
                            <?php endforeach; ?>
                        ];
                        // Calendar with event click
                        document.addEventListener('DOMContentLoaded', function() {
                            var calendarEl = document.getElementById('calendar');
                            var calendar = new FullCalendar.Calendar(calendarEl, {
                                initialView: 'dayGridMonth',
                                height: 500,
                                events: bookingData.map(b => ({
                                    title: b.name,
                                    start: b.date,
                                    extendedProps: b
                                })),
                                eventClick: function(info) {
                                    var b = info.event.extendedProps;
                                    document.getElementById('modalBookingName').textContent = b.name;
                                    document.getElementById('modalBookingEmail').textContent = b.email;
                                    document.getElementById('modalBookingService').textContent = b.service;
                                    document.getElementById('modalBookingDate').textContent = b.date;
                                    document.getElementById('bookingModal').classList.remove('hidden');
                                },
                                headerToolbar: {
                                    left: 'prev,next today',
                                    center: 'title',
                                    right: ''
                                },
                            });
                            calendar.render();
                        });
                        function closeBookingModal() {
                            document.getElementById('bookingModal').classList.add('hidden');
                        }
                        // Booking table search
                        document.addEventListener('DOMContentLoaded', function() {
                            var search = document.getElementById('bookingSearch');
                            var table = document.getElementById('bookingsTable');
                            search.addEventListener('input', function() {
                                var filter = search.value.toLowerCase();
                                Array.from(table.tBodies[0].rows).forEach(function(row) {
                                    var text = row.textContent.toLowerCase();
                                    row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
                                });
                            });
                        });
                    </script>
                </div>
            <?php elseif ($tab === 'testimonials'): ?>
                <div class="bg-gradient-to-br from-yellow-100 to-yellow-50 rounded-xl shadow p-6 mb-8 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-yellow-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polygon points="12 2 15 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 9 8.5 12 2"/></svg>
                        <div>
                            <h2 class="text-xl font-bold text-yellow-900 mb-1">Testimonial Management</h2>
                            <p class="text-yellow-700">Approve, reject, and manage testimonials before publishing.</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="testimonial_manage.php" class="px-3 py-2 bg-yellow-500 text-white rounded-lg font-semibold hover:bg-yellow-600 transition">Manage Testimonials</a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Testimonials</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $totalTestimonials; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending Review</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $pendingTestimonials; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-100 text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Approved</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $totalTestimonials; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-red-100 text-red-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Rejected</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'rejected'")->fetchColumn(); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="testimonial_manage.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="p-2 rounded-full bg-yellow-100 text-yellow-600 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <polygon points="12 2 15 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 9 8.5 12 2"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Manage Testimonials</h4>
                                <p class="text-sm text-gray-600">Approve, reject, and delete testimonials</p>
                            </div>
                        </a>
                        
                        <a href="testimonial_manage.php?status=pending" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="p-2 rounded-full bg-orange-100 text-orange-600 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Review Pending</h4>
                                <p class="text-sm text-gray-600">Review testimonials awaiting approval</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Testimonials -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Testimonials</h3>
                    <div class="space-y-4">
                        <?php foreach ($testimonials as $t): ?>
                        <div class="flex items-center p-3 border border-gray-100 rounded-lg">
                            <div class="p-2 rounded-full bg-yellow-100 text-yellow-600 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <polygon points="12 2 15 8.5 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 9 8.5 12 2"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($t['name']); ?></p>
                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars(mb_strimwidth($t['text'] ?? '', 0, 50, '...')); ?></p>
                                <div class="flex items-center mt-1">
                                    <span class="text-xs text-gray-500 mr-2"><?php echo date('M j', strtotime($t['created_at'])); ?></span>
                                    <?php
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    $status_color = $status_colors[$t['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                                        <?php echo ucfirst($t['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($tab === 'subscriptions'): ?>
                <div class="bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl shadow p-6 mb-8 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-purple-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <div>
                            <h2 class="text-xl font-bold text-purple-900 mb-1">Email Subscriptions</h2>
                            <p class="text-purple-700">Manage newsletter subscribers and email campaigns.</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="subscription_manage.php" class="px-3 py-2 border border-purple-500 text-purple-600 rounded-lg font-semibold hover:bg-purple-50 transition">Manage Subscriptions</a>
                    </div>
                </div>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Active Subscribers</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $totalSubscribers; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-100 text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Campaigns</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $totalCampaigns; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Sent Campaigns</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $pdo->query("SELECT COUNT(*) FROM email_campaigns WHERE status = 'sent'")->fetchColumn(); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-red-100 text-red-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Draft Campaigns</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $pdo->query("SELECT COUNT(*) FROM email_campaigns WHERE status = 'draft'")->fetchColumn(); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        
                        <a href="subscription_manage.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition" title="Manage email subscribers and view subscription list">
                            <div class="p-2 rounded-full bg-purple-100 text-purple-600 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Manage Subscribers</h4>
                                <p class="text-sm text-gray-600">Add, remove, and manage email subscribers</p>
                            </div>
                        </a>
                        
                        <a href="subscription_manage.php?tab=campaigns" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition" title="Create and manage email campaigns">
                            <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Create Campaign</h4>
                                <p class="text-sm text-gray-600">Send newsletters to subscribers</p>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
                    <div class="space-y-4">
                        <?php
                        // Get recent contact messages
                        $recentContacts = $pdo->query("SELECT * FROM contact_submissions WHERE status != 'deleted' ORDER BY submitted_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($recentContacts as $contact):
                        ?>
                        <div class="flex items-center p-3 border border-gray-100 rounded-lg">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($contact['name']); ?></p>
                                <p class="text-xs text-gray-600"><?php echo htmlspecialchars(substr($contact['message'], 0, 50)) . '...'; ?></p>
                            </div>
                            <span class="text-xs text-gray-500"><?php echo date('M j', strtotime($contact['submitted_at'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($tab === 'blog'): ?>
                <div class="bg-gradient-to-br from-green-100 to-green-50 rounded-xl shadow p-6 mb-8 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-green-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <div>
                            <h2 class="text-xl font-bold text-green-900 mb-1">Blog Management</h2>
                            <p class="text-green-700">Create, edit, and manage your blog posts.</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="blog_manage.php" class="px-3 py-2 border border-green-500 text-green-600 rounded-lg font-semibold hover:bg-green-50 transition">Manage Posts</a>
                        <a href="blog_create.php" class="px-3 py-2 bg-green-500 text-white rounded-lg font-semibold hover:bg-green-600 transition">Create New Post</a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-100 text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Posts</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $totalBlogPosts; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Published</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $publishedBlogPosts; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-yellow-100 text-yellow-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Drafts</p>
                                <p class="text-2xl font-semibold text-gray-900"><?php echo $totalBlogPosts - $publishedBlogPosts; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow p-6">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-purple-100 text-purple-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Public Blog</p>
                                <p class="text-2xl font-semibold text-gray-900"><a href="blog.php" target="_blank" class="text-purple-600 hover:text-purple-800">View</a></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <a href="blog_create.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 4v16m8-8H4"/></svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Create New Post</h4>
                                <p class="text-sm text-gray-600">Write and publish a new blog post</p>
                            </div>
                        </a>

                        <a href="blog_manage.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Manage Posts</h4>
                                <p class="text-sm text-gray-600">Edit, delete, and organize blog posts</p>
                            </div>
                        </a>

                        <a href="blog.php" target="_blank" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <div class="p-2 rounded-full bg-purple-100 text-purple-600 mr-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">View Public Blog</h4>
                                <p class="text-sm text-gray-600">See how your blog appears to visitors</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Blog Posts -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Recent Blog Posts</h3>
                    <div class="space-y-4">
                        <?php
                        // Get recent blog posts
                        $recentPosts = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                        if (empty($recentPosts)):
                        ?>
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            <p>No blog posts yet. <a href="blog_create.php" class="text-green-600 hover:text-green-800 font-medium">Create your first post</a></p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($recentPosts as $post): ?>
                        <div class="flex items-center p-3 border border-gray-100 rounded-lg">
                            <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($post['title']); ?></p>
                                <p class="text-xs text-gray-600">Status: <?php echo ucfirst($post['status']); ?> • <?php echo date('M j, Y', strtotime($post['created_at'])); ?></p>
                            </div>
                            <div class="flex space-x-2">
                                <a href="blog_edit.php?id=<?php echo $post['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                                <a href="blog_post.php?slug=<?php echo urlencode($post['slug']); ?>" target="_blank" class="text-green-600 hover:text-green-800 text-sm">View</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($tab === 'jogigs'): ?>
                <div class="bg-gradient-to-br from-blue-100 to-purple-50 rounded-xl shadow p-6 mb-8 flex items-center">
                    <svg class="w-10 h-10 text-blue-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"/></svg>
                    <div>
                        <h2 class="text-xl font-bold text-blue-900 mb-1">JoGigs Freelancer Platform</h2>
                        <p class="text-blue-700">Manage your freelancer marketplace and job assignments.</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Job Management -->
                    <a href="jogigs/admin/job_management.php" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Job Management</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Create, manage, and assign jobs to freelancers. Review proposals and track job progress.</p>
                        <div class="flex items-center text-blue-600 font-medium">
                            <span>Manage Jobs</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>

                    <!-- Booking Management -->
                    <a href="jogigs/admin/booking_management.php" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Booking Management</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Process booking submissions and create jobs from them. Track booking workflow.</p>
                        <div class="flex items-center text-green-600 font-medium">
                            <span>Manage Bookings</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>

                    <!-- Proposal Management -->
                    <a href="jogigs/admin/proposal_management.php" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Proposal Management</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Review freelancer proposals, accept or reject them, and manage the selection process.</p>
                        <div class="flex items-center text-purple-600 font-medium">
                            <span>Review Proposals</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>

                    <!-- Admin Dashboard -->
                    <a href="jogigs/admin/" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-orange-100 text-orange-600 mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">JoGigs Dashboard</h3>
                        </div>
                        <p class="text-gray-600 mb-4">View comprehensive statistics and manage the entire freelancer platform.</p>
                        <div class="flex items-center text-orange-600 font-medium">
                            <span>View Dashboard</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </a>

                    <!-- Freelancer Portal -->
                    <a href="jogigs/" target="_blank" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Freelancer Portal</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Access the freelancer portal to see how freelancers interact with the platform.</p>
                        <div class="flex items-center text-indigo-600 font-medium">
                            <span>Visit Portal</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </div>
                    </a>

                    <!-- Client Confirmation -->
                    <a href="jogigs/client/confirm_job.php" target="_blank" class="bg-white rounded-xl shadow p-6 hover:shadow-lg transition-shadow">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-full bg-teal-100 text-teal-600 mr-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Client Confirmation</h3>
                        </div>
                        <p class="text-gray-600 mb-4">Test the client confirmation system for completed jobs.</p>
                        <div class="flex items-center text-teal-600 font-medium">
                            <span>Test Confirmation</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </div>
                    </a>
                </div>

            <?php elseif ($tab === 'settings'): ?>
                <div class="bg-gradient-to-br from-pink-100 to-pink-50 rounded-xl shadow p-6 mb-8 flex items-center">
                    <svg class="w-10 h-10 text-pink-500 mr-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <div>
                        <h2 class="text-xl font-bold text-pink-900 mb-1">Admin Settings</h2>
                        <p class="text-pink-700">Manage your admin account and notification preferences.</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow p-6">
                    <div class="mb-4">Email: <b><?php echo htmlspecialchars($admin_email); ?></b></div>
                    <div class="mb-4">Role: <b>Administrator</b></div>
                    <div class="mb-4">Notification Preferences:<br>
                        <label class="block"><input type="checkbox" checked disabled class="mr-2"> Email notifications for new contacts</label>
                        <label class="block"><input type="checkbox" checked disabled class="mr-2"> Email notifications for new bookings</label>
                        <label class="block"><input type="checkbox" checked disabled class="mr-2"> Email notifications for new testimonials</label>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<!-- Back to Top Button -->
<button id="backToTopBtn-admin" class="fixed bottom-8 right-8 z-50 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white p-3 rounded-full shadow-lg hover:scale-110 transition-all duration-300 hidden" aria-label="Back to Top">
  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
</button>
<script>
  const backToTopBtn = document.getElementById('backToTopBtn-admin');
  window.addEventListener('scroll', function() {
    if (window.scrollY > 300) {
      backToTopBtn.classList.remove('hidden');
    } else {
      backToTopBtn.classList.add('hidden');
    }
  });
  backToTopBtn.addEventListener('click', function() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
</script>
<script>
// Confirmation dialogs for destructive actions
function confirmDeleteBooking() {
    return confirm('Are you sure you want to delete this booking? This action cannot be undone.');
}
function confirmRejectTestimonial() {
    return confirm('Are you sure you want to reject this testimonial?');
}
</script>
</body>
</html> 