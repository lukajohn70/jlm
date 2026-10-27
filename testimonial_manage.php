<?php
session_start();
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/config/config.php';
require_admin_auth();

// Handle actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_testimonial'])) {
        $id = (int)$_POST['approve_testimonial'];
        try {
            $stmt = $pdo->prepare('UPDATE testimonials SET status = "approved" WHERE id = ?');
            $stmt->execute([$id]);
            cache_delete('approved_testimonials_10');
            $message = 'Testimonial approved successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error approving testimonial.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['reject_testimonial'])) {
        $id = (int)$_POST['reject_testimonial'];
        try {
            $stmt = $pdo->prepare('UPDATE testimonials SET status = "rejected" WHERE id = ?');
            $stmt->execute([$id]);
            cache_delete('approved_testimonials_10');
            $message = 'Testimonial rejected.';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error rejecting testimonial.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['delete_testimonial'])) {
        $id = (int)$_POST['delete_testimonial'];
        try {
            $stmt = $pdo->prepare('DELETE FROM testimonials WHERE id = ?');
            $stmt->execute([$id]);
            cache_delete('approved_testimonials_10');
            $message = 'Testimonial deleted permanently.';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error deleting testimonial.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['archive_testimonial'])) {
        $id = (int)$_POST['archive_testimonial'];
        try {
            $stmt = $pdo->prepare('UPDATE testimonials SET status = "archived" WHERE id = ?');
            $stmt->execute([$id]);
            cache_delete('approved_testimonials_10');
            $message = 'Testimonial archived successfully!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error archiving testimonial.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['unarchive_testimonial'])) {
        $id = (int)$_POST['unarchive_testimonial'];
        try {
            $stmt = $pdo->prepare('UPDATE testimonials SET status = "pending" WHERE id = ?');
            $stmt->execute([$id]);
            cache_delete('approved_testimonials_10');
            $message = 'Testimonial unarchived and set to pending review!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error unarchiving testimonial.';
            $message_type = 'error';
        }
    } elseif (isset($_POST['unreject_testimonial'])) {
        $id = (int)$_POST['unreject_testimonial'];
        try {
            $stmt = $pdo->prepare('UPDATE testimonials SET status = "pending" WHERE id = ?');
            $stmt->execute([$id]);
            cache_delete('approved_testimonials_10');
            $message = 'Testimonial unrejected and set to pending review!';
            $message_type = 'success';
        } catch (Exception $e) {
            $message = 'Error unrejecting testimonial.';
            $message_type = 'error';
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = 'status = ?';
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = '(name LIKE ? OR company LIKE ? OR text LIKE ?)';
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get testimonials
$query = "SELECT * FROM testimonials $where_clause ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_testimonials = $pdo->query('SELECT COUNT(*) FROM testimonials')->fetchColumn();
$pending_testimonials = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'pending'")->fetchColumn();
$approved_testimonials = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'approved'")->fetchColumn();
$rejected_testimonials = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'rejected'")->fetchColumn();
$archived_testimonials = $pdo->query("SELECT COUNT(*) FROM testimonials WHERE status = 'archived'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Testimonial Management | JLM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/jlm.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Action Button Styles */
                 .action-btn {
             display: inline-flex;
             align-items: center;
             justify-content: center;
             width: 32px;
             height: 32px;
             border-radius: 6px;
             border: none;
             cursor: pointer;
             transition: all 0.2s ease;
             margin: 0 1px;
             position: relative;
         }
        
        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .approve-btn {
            background: #f0fdf4;
            color: #16a34a;
        }
        
        .approve-btn:hover {
            background: #dcfce7;
            color: #15803d;
        }
        
        .reject-btn {
            background: #fef2f2;
            color: #dc2626;
        }
        
        .reject-btn:hover {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .archive-btn {
            background: #f8fafc;
            color: #64748b;
        }
        
        .archive-btn:hover {
            background: #f1f5f9;
            color: #475569;
        }
        
        .delete-btn {
            background: #fef2f2;
            color: #dc2626;
        }
        
                 .delete-btn:hover {
             background: #fee2e2;
             color: #b91c1c;
         }
         
         .preview-btn {
             background: #fdf4ff;
             color: #a855f7;
         }
         
         .preview-btn:hover {
             background: #f3e8ff;
             color: #9333ea;
         }
         
         .archive-btn {
             background: #fef7ed;
             color: #ea580c;
         }
         
         .archive-btn:hover {
             background: #fed7aa;
             color: #c2410c;
         }
         
         .unarchive-btn {
             background: #f0f9ff;
             color: #0284c7;
         }
         
         .unarchive-btn:hover {
             background: #e0f2fe;
             color: #0369a1;
         }
         
         .unreject-btn {
             background: #fef3c7;
             color: #d97706;
         }
         
         .unreject-btn:hover {
             background: #fde68a;
             color: #b45309;
         }
        
        /* Tooltip Styles */
        .action-btn::after {
            content: attr(title);
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            background: #1f2937;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 10;
            pointer-events: none;
        }
        
        .action-btn::before {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-bottom: 5px solid #1f2937;
            opacity: 0;
            visibility: hidden;
            transition: all 0.2s ease;
            z-index: 10;
            pointer-events: none;
        }
        
        .action-btn:hover::after,
        .action-btn:hover::before {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center py-4">
            <div class="flex items-center space-x-3">
                <img src="assets/images/jlm.png" alt="JLM Logo" class="h-10 w-auto rounded-lg">
                <span class="text-xl font-bold text-gray-900">Testimonial Management</span>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin_dashboard.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <a href="?logout=1" class="px-4 py-2 border border-pink-500 text-pink-600 rounded-lg font-semibold hover:bg-pink-50 transition">Sign Out</a>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Message -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

                 <!-- Statistics Cards -->
         <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Testimonials</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $total_testimonials; ?></p>
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
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $pending_testimonials; ?></p>
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
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $approved_testimonials; ?></p>
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
                         <p class="text-2xl font-semibold text-gray-900"><?php echo $rejected_testimonials; ?></p>
                     </div>
                 </div>
             </div>
             
             <div class="bg-white rounded-xl shadow p-6">
                 <div class="flex items-center">
                     <div class="p-2 rounded-full bg-gray-100 text-gray-600">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-14 0h14"/>
                         </svg>
                     </div>
                     <div class="ml-4">
                         <p class="text-sm font-medium text-gray-600">Archived</p>
                         <p class="text-2xl font-semibold text-gray-900"><?php echo $archived_testimonials; ?></p>
                     </div>
                 </div>
             </div>
         </div>

        <!-- Filters and Search -->
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <form method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" placeholder="Search by name, company, or testimonial text..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                                         <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                         <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                         <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                         <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                         <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                         <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                     </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition">
                        Filter
                    </button>
                    <a href="?" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition">
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Testimonials Table -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Testimonials (<?php echo count($testimonials); ?>)</h2>
            </div>
            
            <?php if (empty($testimonials)): ?>
            <div class="p-8 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No testimonials found</h3>
                <p class="text-gray-600">Try adjusting your search or filter criteria.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                                 <table class="w-full divide-y divide-gray-200">
                     <thead class="bg-gray-50">
                         <tr>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Name</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Company/Role</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Rating</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Testimonial</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Status</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Date</th>
                             <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Actions</th>
                         </tr>
                     </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($testimonials as $testimonial): ?>
                                                 <tr class="hover:bg-gray-50">
                             <td class="px-4 py-4 whitespace-nowrap">
                                 <div class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($testimonial['name'] ?? ''); ?>"><?php echo htmlspecialchars($testimonial['name'] ?? ''); ?></div>
                             </td>
                             <td class="px-4 py-4 whitespace-nowrap">
                                 <div class="text-sm text-gray-900 truncate" title="<?php 
                                     $role_company = [];
                                     if (!empty($testimonial['role'] ?? '')) $role_company[] = htmlspecialchars($testimonial['role'] ?? '');
                                     if (!empty($testimonial['company'] ?? '')) $role_company[] = htmlspecialchars($testimonial['company'] ?? '');
                                     echo implode(', ', $role_company) ?: '-';
                                 ?>">
                                     <?php 
                                     $role_company = [];
                                     if (!empty($testimonial['role'] ?? '')) $role_company[] = htmlspecialchars($testimonial['role'] ?? '');
                                     if (!empty($testimonial['company'] ?? '')) $role_company[] = htmlspecialchars($testimonial['company'] ?? '');
                                     echo implode(', ', $role_company) ?: '-';
                                     ?>
                                 </div>
                             </td>
                             <td class="px-4 py-4 whitespace-nowrap">
                                 <div class="flex items-center">
                                     <?php for ($i = 1; $i <= 5; $i++): ?>
                                     <svg class="w-3 h-3 <?php echo $i <= ($testimonial['rating'] ?? 0) ? 'text-yellow-400 fill-current' : 'text-gray-300'; ?>" viewBox="0 0 20 20">
                                         <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                     </svg>
                                     <?php endfor; ?>
                                     <span class="ml-1 text-xs text-gray-500">(<?php echo $testimonial['rating'] ?? 0; ?>/5)</span>
                                 </div>
                             </td>
                                                                                      <td class="px-4 py-4">
                                 <div class="text-sm text-gray-900 truncate" title="<?php echo htmlspecialchars($testimonial['text'] ?? ''); ?>">
                                     <?php echo htmlspecialchars(mb_strimwidth($testimonial['text'] ?? '', 0, 60, '...')); ?>
                                 </div>
                             </td>
                             <td class="px-4 py-4 whitespace-nowrap">
                                                                  <?php
                                  $status_colors = [
                                      'pending' => 'bg-yellow-100 text-yellow-800',
                                      'approved' => 'bg-green-100 text-green-800',
                                      'rejected' => 'bg-red-100 text-red-800',
                                      'archived' => 'bg-gray-100 text-gray-800'
                                  ];
                                  $status_color = $status_colors[$testimonial['status'] ?? 'pending'] ?? 'bg-gray-100 text-gray-800';
                                  ?>
                                 <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_color; ?>">
                                     <?php echo ucfirst($testimonial['status'] ?? 'pending'); ?>
                                 </span>
                             </td>
                             <td class="px-4 py-4 whitespace-nowrap text-xs text-gray-500">
                                 <?php echo date('M j, Y', strtotime($testimonial['created_at'] ?? 'now')); ?>
                             </td>
                                                          <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                  <div class="flex space-x-1">
                                                                           <!-- Preview Button -->
                                                                             <button type="button" 
                                               onclick="previewTestimonial(<?php echo htmlspecialchars(json_encode($testimonial)); ?>)"
                                               class="action-btn preview-btn"
                                               title="Preview testimonial">
                                           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                           </svg>
                                       </button>
                                     
                                     <?php if (($testimonial['status'] ?? '') === 'pending'): ?>
                                     <form method="POST" class="inline">
                                                                                   <button type="submit" name="approve_testimonial" value="<?php echo $testimonial['id']; ?>" 
                                                  class="action-btn approve-btn"
                                                  onclick="return confirm('Approve this testimonial?')"
                                                  title="Approve testimonial">
                                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                              </svg>
                                          </button>
                                     </form>
                                     <form method="POST" class="inline">
                                                                                   <button type="submit" name="reject_testimonial" value="<?php echo $testimonial['id']; ?>" 
                                                  class="action-btn reject-btn"
                                                  onclick="return confirm('Reject this testimonial?')"
                                                  title="Reject testimonial">
                                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                              </svg>
                                          </button>
                                     </form>
                                     <?php endif; ?>
                                     
                                                                           <?php if (($testimonial['status'] ?? '') !== 'archived'): ?>
                                      <form method="POST" class="inline">
                                                                                     <button type="submit" name="archive_testimonial" value="<?php echo $testimonial['id']; ?>" 
                                                   class="action-btn archive-btn"
                                                   onclick="return confirm('Archive this testimonial?')"
                                                   title="Archive testimonial">
                                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-14 0h14"/>
                                               </svg>
                                           </button>
                                      </form>
                                      <?php endif; ?>
                                      
                                      <?php if (($testimonial['status'] ?? '') === 'archived'): ?>
                                      <form method="POST" class="inline">
                                                                                     <button type="submit" name="unarchive_testimonial" value="<?php echo $testimonial['id']; ?>" 
                                                   class="action-btn unarchive-btn"
                                                   onclick="return confirm('Unarchive this testimonial and set to pending review?')"
                                                   title="Unarchive testimonial">
                                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                               </svg>
                                           </button>
                                      </form>
                                      <?php endif; ?>
                                      
                                      <?php if (($testimonial['status'] ?? '') === 'rejected'): ?>
                                      <form method="POST" class="inline">
                                                                                     <button type="submit" name="unreject_testimonial" value="<?php echo $testimonial['id']; ?>" 
                                                   class="action-btn unreject-btn"
                                                   onclick="return confirm('Unreject this testimonial and set to pending review?')"
                                                   title="Unreject testimonial">
                                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                               </svg>
                                           </button>
                                      </form>
                                      <?php endif; ?>
                                     
                                     <form method="POST" class="inline">
                                                                                   <button type="submit" name="delete_testimonial" value="<?php echo $testimonial['id']; ?>" 
                                                  class="action-btn delete-btn"
                                                  onclick="return confirm('Delete this testimonial permanently? This action cannot be undone.')"
                                                  title="Delete testimonial permanently">
                                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                              </svg>
                                          </button>
                                     </form>
                                 </div>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

         <!-- Preview Modal -->
     <div id="previewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
         <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
             <div class="mt-3">
                 <div class="flex justify-between items-center mb-4">
                     <h3 class="text-lg font-semibold text-gray-900">Testimonial Preview</h3>
                     <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600">
                         <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                         </svg>
                     </button>
                 </div>
                 
                 <div id="previewContent" class="space-y-4">
                     <!-- Content will be populated by JavaScript -->
                 </div>
                 
                 <div class="flex justify-end mt-6">
                     <button onclick="closePreviewModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-400 transition">
                         Close
                     </button>
                 </div>
             </div>
         </div>
     </div>

     <script>
         // Auto-hide notification messages after 5 seconds (but not status badges)
         setTimeout(function() {
             const messages = document.querySelectorAll('.mb-6 .bg-green-100, .mb-6 .bg-red-100');
             messages.forEach(function(message) {
                 message.style.display = 'none';
             });
         }, 5000);
         
         // Preview functionality
         function previewTestimonial(testimonial) {
             const modal = document.getElementById('previewModal');
             const content = document.getElementById('previewContent');
             
             // Generate initials for avatar
             const initials = testimonial.name.split(' ').map(n => n[0]).join('').toUpperCase();
             
             // Generate star rating
             let stars = '';
             for (let i = 1; i <= 5; i++) {
                 if (i <= testimonial.rating) {
                     stars += '<svg class="w-5 h-5 text-yellow-400 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
                 } else {
                     stars += '<svg class="w-5 h-5 text-gray-300" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>';
                 }
             }
             
             // Status badge
             const statusColors = {
                 'pending': 'bg-yellow-100 text-yellow-800',
                 'approved': 'bg-green-100 text-green-800',
                 'rejected': 'bg-red-100 text-red-800',
                 'archived': 'bg-gray-100 text-gray-800'
             };
             const statusColor = statusColors[testimonial.status] || 'bg-gray-100 text-gray-800';
             
             content.innerHTML = `
                 <div class="bg-gray-50 rounded-lg p-6">
                     <div class="flex items-start space-x-4">
                         <div class="flex-shrink-0">
                             <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                                 ${initials}
                             </div>
                         </div>
                         <div class="flex-1 min-w-0">
                             <div class="flex items-center justify-between mb-2">
                                 <h4 class="text-lg font-semibold text-gray-900">${testimonial.name}</h4>
                                 <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusColor}">
                                     ${testimonial.status.charAt(0).toUpperCase() + testimonial.status.slice(1)}
                                 </span>
                             </div>
                             ${testimonial.role || testimonial.company ? `
                                 <p class="text-sm text-gray-600 mb-2">
                                     ${testimonial.role ? testimonial.role : ''}${testimonial.role && testimonial.company ? ' at ' : ''}${testimonial.company ? testimonial.company : ''}
                                 </p>
                             ` : ''}
                             <div class="flex items-center mb-3">
                                 ${stars}
                                 <span class="ml-2 text-sm text-gray-500">(${testimonial.rating}/5)</span>
                             </div>
                             <blockquote class="text-gray-700 italic">
                                 "${testimonial.text}"
                             </blockquote>
                             <div class="mt-4 text-xs text-gray-500">
                                 Submitted on ${new Date(testimonial.created_at).toLocaleDateString('en-US', { 
                                     year: 'numeric', 
                                     month: 'long', 
                                     day: 'numeric' 
                                 })}
                             </div>
                         </div>
                     </div>
                 </div>
             `;
             
             modal.classList.remove('hidden');
         }
         
         function closePreviewModal() {
             document.getElementById('previewModal').classList.add('hidden');
         }
         
         // Close modal when clicking outside
         document.getElementById('previewModal').addEventListener('click', function(e) {
             if (e.target === this) {
                 closePreviewModal();
             }
         });
         
         // Close modal with Escape key
         document.addEventListener('keydown', function(e) {
             if (e.key === 'Escape') {
                 closePreviewModal();
             }
         });
     </script>
 </body>
 </html> 
