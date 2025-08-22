<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['user_role'] ?? '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - JLM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($role); ?>!</p>
    <?php if ($role === 'admin'): ?>
        <a href="admin_dashboard.php">Admin Dashboard</a> |
        <a href="blog_manage.php">Manage Blog Posts</a>
    <?php elseif ($role === 'freelancer'): ?>
        <a href="freelancer_dashboard.php">Freelancer Dashboard</a>
    <?php endif; ?>
    <br><br>
    <a href="logout.php">Logout</a>
    <!-- Back to Top Button -->
    <button id="backToTopBtn-dashboard" class="fixed bottom-8 right-8 z-50 bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white p-3 rounded-full shadow-lg hover:scale-110 transition-all duration-300 hidden" aria-label="Back to Top">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
    </button>
    <script>
      const backToTopBtn = document.getElementById('backToTopBtn-dashboard');
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
</body>
</html> 