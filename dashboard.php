<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please login to access the dashboard', 'error');
}

// Get user information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Check if there's a message to display
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// Get user profile information based on role
if ($role === 'job_seeker') {
    $stmt = $conn->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
} elseif ($role === 'agent') {
    $stmt = $conn->prepare("SELECT * FROM agent_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
} else {
    // Admin or other role
    $profile = null;
}

// Get counts for dashboard stats
$stats = [
    'total_users' => 0,
    'total_job_seekers' => 0,
    'total_agents' => 0,
    'total_applications' => 0,
    'total_job_listings' => 0
];

if ($role === 'admin') {
    // Admin stats
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'job_seeker'");
    $stats['total_job_seekers'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'agent'");
    $stats['total_agents'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM applications");
    $stats['total_applications'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM job_listings");
    $stats['total_job_listings'] = $result->fetch_assoc()['count'];
} elseif ($role === 'job_seeker') {
    // Job seeker stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE job_seeker_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['total_applications'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'agent'");
    $stats['total_agents'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM job_listings");
    $stats['total_job_listings'] = $result->fetch_assoc()['count'];
} elseif ($role === 'agent') {
    // Agent stats
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_listings WHERE agent_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['total_job_listings'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM applications WHERE job_listing_id IN (SELECT id FROM job_listings WHERE agent_id = ?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['total_applications'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'job_seeker'");
    $stats['total_job_seekers'] = $result->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - AgentConnect</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-blue-600">AgentConnect</a>
            <div class="flex items-center space-x-4">
                <div class="relative group">
                    <button class="flex items-center space-x-1 text-gray-700 hover:text-blue-600">
                        <span><?php echo htmlspecialchars($username); ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
                        <a href="dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Dashboard</a>
                        <a href="profile.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Alert Message -->
    <?php if (!empty($message)): ?>
    <div class="container mx-auto mt-4 px-4">
        <div class="p-4 rounded <?php echo $message_type === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <?php echo $message; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Dashboard Content -->
    <div class="flex-grow container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row">
            <!-- Sidebar -->
            <div class="w-full md:w-1/4 mb-6 md:mb-0 md:pr-6">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($username); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $role)); ?></p>
                        </div>
                    </div>
                    <div class="border-t border-gray-200 pt-4">
                        <ul class="space-y-2">
                            <li>
                                <a href="dashboard.php" class="flex items-center text-blue-600 font-medium">
                                    <i class="fas fa-tachometer-alt w-5 mr-2"></i>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="profile.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-user-circle w-5 mr-2"></i>
                                    <span>Profile</span>
                                </a>
                            </li>
                            <?php if ($role === 'job_seeker'): ?>
                            <li>
                                <a href="applications.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-file-alt w-5 mr-2"></i>
                                    <span>My Applications</span>
                                </a>
                            </li>
                            <li>
                                <a href="job_listings.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-briefcase w-5 mr-2"></i>
                                    <span>Find Jobs</span>
                                </a>
                            </li>
                            <?php elseif ($role === 'agent'): ?>
                            <li>
                                <a href="job_listings.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-briefcase w-5 mr-2"></i>
                                    <span>My Job Listings</span>
                                </a>
                            </li>
                            <li>
                                <a href="applications.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-file-alt w-5 mr-2"></i>
                                    <span>Applications</span>
                                </a>
                            </li>
                            <?php elseif ($role === 'admin'): ?>
                            <li>
                                <a href="users.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-users w-5 mr-2"></i>
                                    <span>Manage Users</span>
                                </a>
                            </li>
                            <li>
                                <a href="job_listings.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-briefcase w-5 mr-2"></i>
                                    <span>All Job Listings</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a href="messages.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-envelope w-5 mr-2"></i>
                                    <span>Messages</span>
                                </a>
                            </li>
                            <li>
                                <a href="logout.php" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <i class="fas fa-sign-out-alt w-5 mr-2"></i>
                                    <span>Logout</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php include 'dashboard_content.php'; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> AgentConnect. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
