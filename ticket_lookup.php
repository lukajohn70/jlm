<?php
/**
 * Public Ticket Lookup Page
 * Allows users to check the status of their submissions using ticket numbers
 */

session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/ticket_utils.php';

$ticketInfo = null;
$error = null;
$success = null;

// Handle ticket lookup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_ticket'])) {
    $ticketNumber = trim($_POST['ticket_number']);
    
    if (empty($ticketNumber)) {
        $error = 'Please enter a ticket number.';
    } else {
        $ticketInfo = getTicketInfo($ticketNumber);
        if (!$ticketInfo) {
            $error = 'Ticket number not found. Please check your ticket number and try again.';
        } else {
            $success = 'Ticket found!';
        }
    }
}

// Get ticket activities if ticket is found
$ticketActivities = [];
if ($ticketInfo) {
    $ticketActivities = getTicketActivities($ticketInfo['ticket_number']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Lookup - JLM Creative Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f7f8fa 0%, #e8e4f6 100%);
            min-height: 100vh;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-open { background: #e3f2fd; color: #1976d2; }
        .status-in_progress { background: #fff3e0; color: #f57c00; }
        .status-resolved { background: #e8f5e8; color: #388e3c; }
        .status-closed { background: #f5f5f5; color: #616161; }
        .priority-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .priority-low { background: #e8f5e8; color: #388e3c; }
        .priority-medium { background: #fff3e0; color: #f57c00; }
        .priority-high { background: #ffebee; color: #d32f2f; }
        .priority-urgent { background: #fce4ec; color: #c2185b; }
    </style>
</head>
<body class="min-h-screen">
    <!-- Back to Home Button -->
    <a href="index.php" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-[9999] px-4 py-3 bg-white/90 backdrop-blur-sm text-gray-700 rounded-full font-semibold hover:bg-gray-100 transition-all duration-200 shadow-lg border border-gray-200 hover:shadow-xl">
        <i class="fas fa-arrow-left mr-2"></i>Back to Home
    </a>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">Ticket Lookup</h1>
                <p class="text-lg text-gray-600">Check the status of your submission using your ticket number</p>
            </div>

            <!-- Lookup Form -->
            <div class="card p-8 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Find Your Ticket</h2>
                
                <form method="post" class="space-y-6">
                    <div>
                        <label for="ticket_number" class="block text-sm font-medium text-gray-700 mb-2">
                            Ticket Number
                        </label>
                        <input 
                            type="text" 
                            id="ticket_number" 
                            name="ticket_number" 
                            placeholder="Enter your ticket number (e.g., JLM-20241201143022-A1B2)"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            value="<?php echo isset($_POST['ticket_number']) ? htmlspecialchars($_POST['ticket_number']) : ''; ?>"
                            required
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        name="lookup_ticket"
                        class="w-full bg-gradient-to-r from-[#1b2299] to-[#e4306d] text-white px-6 py-3 rounded-lg font-semibold hover:from-[#151d7a] hover:to-[#c2285a] transition-all duration-200"
                    >
                        <i class="fas fa-search mr-2"></i>Look Up Ticket
                    </button>
                </form>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="card p-6 mb-8 border-l-4 border-red-500 bg-red-50">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3 text-xl"></i>
                        <p class="text-red-700 font-medium"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="card p-6 mb-8 border-l-4 border-green-500 bg-green-50">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
                        <p class="text-green-700 font-medium"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Ticket Information -->
            <?php if ($ticketInfo): ?>
                <div class="card p-8 mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-semibold text-gray-900">Ticket Details</h2>
                        <div class="flex items-center space-x-3">
                            <span class="status-badge status-<?php echo $ticketInfo['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $ticketInfo['status'])); ?>
                            </span>
                            <span class="priority-badge priority-<?php echo $ticketInfo['priority']; ?>">
                                <?php echo ucfirst($ticketInfo['priority']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Ticket Number</h3>
                            <p class="text-lg font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($ticketInfo['ticket_number']); ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Form Type</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo ucfirst($ticketInfo['form_type']); ?> Submission</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Submitter Name</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($ticketInfo['submitter_name']); ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Submitter Email</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($ticketInfo['submitter_email']); ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Created</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo date('F j, Y \a\t g:i A', strtotime($ticketInfo['created_at'])); ?></p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Last Updated</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo date('F j, Y \a\t g:i A', strtotime($ticketInfo['updated_at'])); ?></p>
                        </div>
                    </div>

                    <?php if ($ticketInfo['assigned_to_name']): ?>
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Assigned To</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($ticketInfo['assigned_to_name']); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($ticketInfo['notes']): ?>
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <pre class="text-sm text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($ticketInfo['notes']); ?></pre>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Ticket Activities -->
                <?php if ($ticketActivities): ?>
                    <div class="card p-8">
                        <h2 class="text-2xl font-semibold text-gray-900 mb-6">Activity History</h2>
                        
                        <div class="space-y-4">
                            <?php foreach ($ticketActivities as $activity): ?>
                                <div class="flex items-start space-x-4 p-4 bg-gray-50 rounded-lg">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <?php
                                        $icon = 'fa-info-circle';
                                        switch ($activity['activity_type']) {
                                            case 'created': $icon = 'fa-plus-circle'; break;
                                            case 'status_changed': $icon = 'fa-exchange-alt'; break;
                                            case 'assigned': $icon = 'fa-user-plus'; break;
                                            case 'note_added': $icon = 'fa-sticky-note'; break;
                                            case 'email_sent': $icon = 'fa-envelope'; break;
                                            case 'resolved': $icon = 'fa-check-circle'; break;
                                        }
                                        ?>
                                        <i class="fas <?php echo $icon; ?> text-blue-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                                        <div class="flex items-center space-x-4 mt-1 text-xs text-gray-500">
                                            <span><?php echo date('F j, Y \a\t g:i A', strtotime($activity['performed_at'])); ?></span>
                                            <?php if ($activity['performed_by_name']): ?>
                                                <span>by <?php echo htmlspecialchars($activity['performed_by_name']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Help Section -->
            <div class="card p-8 mt-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Need Help?</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Where to Find Your Ticket Number</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-start">
                                <i class="fas fa-envelope text-blue-500 mr-2 mt-1"></i>
                                <span>Check your email confirmation after form submission</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-phone text-blue-500 mr-2 mt-1"></i>
                                <span>Contact us at +2348150917741</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-envelope text-blue-500 mr-2 mt-1"></i>
                                <span>Email us at support@jlm.com.ng</span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Ticket Status Meanings</h3>
                        <ul class="space-y-2 text-gray-600">
                            <li class="flex items-center">
                                <span class="status-badge status-open mr-3">Open</span>
                                <span>We've received your submission</span>
                            </li>
                            <li class="flex items-center">
                                <span class="status-badge status-in_progress mr-3">In Progress</span>
                                <span>We're working on your request</span>
                            </li>
                            <li class="flex items-center">
                                <span class="status-badge status-resolved mr-3">Resolved</span>
                                <span>Your request has been completed</span>
                            </li>
                            <li class="flex items-center">
                                <span class="status-badge status-closed mr-3">Closed</span>
                                <span>Ticket has been closed</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
