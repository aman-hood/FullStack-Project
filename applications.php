<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please login to view applications', 'error');
}

// Get user information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Handle application status update (for agents)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'agent' && isset($_POST['application_id']) && isset($_POST['status'])) {
    $application_id = intval($_POST['application_id']);
    $status = sanitize_input($_POST['status']);
    
    // Verify the application belongs to a job listing owned by this agent
    $stmt = $conn->prepare("SELECT a.id FROM applications a 
                           JOIN job_listings j ON a.job_listing_id = j.id 
                           WHERE a.id = ? AND j.agent_id = ?");
    $stmt->bind_param("ii", $application_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update application status
        $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $application_id);
        
        if ($stmt->execute()) {
            $success_message = "Application status updated successfully!";
        } else {
            $error_message = "Error updating application status: " . $conn->error;
        }
    } else {
        $error_message = "You don't have permission to update this application.";
    }
}

// Get applications based on role
if ($role === 'job_seeker') {
    // Job seekers see their own applications
    $stmt = $conn->prepare("SELECT a.*, j.title, j.company_name, u.username as agent_username 
                           FROM applications a 
                           JOIN job_listings j ON a.job_listing_id = j.id 
                           JOIN users u ON j.agent_id = u.id 
                           WHERE a.job_seeker_id = ? 
                           ORDER BY a.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applications = $result->fetch_all(MYSQLI_ASSOC);
} elseif ($role === 'agent') {
    // Agents see applications for their job listings
    $stmt = $conn->prepare("SELECT a.*, j.title, j.company_name, u.username as applicant_username,
                           jsp.full_name as applicant_name, jsp.skills, jsp.experience 
                           FROM applications a 
                           JOIN job_listings j ON a.job_listing_id = j.id 
                           JOIN users u ON a.job_seeker_id = u.id 
                           LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id 
                           WHERE j.agent_id = ? 
                           ORDER BY a.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applications = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Admins see all applications
    $query = "SELECT a.*, j.title, j.company_name, 
             u_seeker.username as applicant_username, 
             u_agent.username as agent_username 
             FROM applications a 
             JOIN job_listings j ON a.job_listing_id = j.id 
             JOIN users u_seeker ON a.job_seeker_id = u_seeker.id 
             JOIN users u_agent ON j.agent_id = u_agent.id 
             ORDER BY a.created_at DESC";
    $result = $conn->query($query);
    $applications = $result->fetch_all(MYSQLI_ASSOC);
}

// Check if there's a message to display
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - AgentConnect</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-blue-600">AgentConnect</a>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
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
    
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
    <div class="container mx-auto mt-4 px-4">
        <div class="p-4 rounded bg-green-100 text-green-700">
            <?php echo $success_message; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="container mx-auto mt-4 px-4">
        <div class="p-4 rounded bg-red-100 text-red-700">
            <?php echo $error_message; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Applications Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">
                <?php if ($role === 'job_seeker'): ?>
                    My Applications
                <?php elseif ($role === 'agent'): ?>
                    Applications Received
                <?php else: ?>
                    All Applications
                <?php endif; ?>
            </h2>
        </div>
        
        <!-- Applications List -->
        <div class="space-y-4">
            <?php if (empty($applications)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-500">
                    <?php if ($role === 'job_seeker'): ?>
                        You haven't submitted any applications yet.
                    <?php elseif ($role === 'agent'): ?>
                        You haven't received any applications yet.
                    <?php else: ?>
                        No applications in the system.
                    <?php endif; ?>
                </p>
            </div>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($application['title']); ?></h3>
                                <p class="text-gray-600 mb-1"><?php echo htmlspecialchars($application['company_name']); ?></p>
                                
                                <?php if ($role === 'job_seeker' || $role === 'admin'): ?>
                                <p class="text-gray-500 mb-4">
                                    <?php if (isset($application['agent_username'])): ?>
                                    Agent: <?php echo htmlspecialchars($application['agent_username']); ?>
                                    <?php endif; ?>
                                </p>
                                <?php elseif ($role === 'agent'): ?>
                                <p class="text-gray-500 mb-4">
                                    Applicant: <?php echo htmlspecialchars($application['applicant_name'] ?: $application['applicant_username']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="px-3 py-1 text-sm rounded-full 
                                    <?php 
                                    switch($application['status']) {
                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'reviewed': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'contacted': echo 'bg-purple-100 text-purple-800'; break;
                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                        case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($role === 'agent'): ?>
                        <!-- Application Details for Agents -->
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <div class="mb-4">
                                <h4 class="font-semibold mb-2">Cover Letter</h4>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                            </div>
                            
                            <?php if (!empty($application['skills'])): ?>
                            <div class="mb-4">
                                <h4 class="font-semibold mb-2">Skills</h4>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($application['skills'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($application['experience'])): ?>
                            <div class="mb-4">
                                <h4 class="font-semibold mb-2">Experience</h4>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($application['experience'])); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-6 flex justify-between items-center">
                                <div>
                                    <label for="status-<?php echo $application['id']; ?>" class="block text-gray-700 font-semibold mb-2">Update Status</label>
                                    <form method="POST" action="applications.php" class="flex items-center">
                                        <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                        <select name="status" id="status-<?php echo $application['id']; ?>" class="application-status-select px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500 mr-2" data-application-id="<?php echo $application['id']; ?>">
                                            <option value="pending" <?php echo $application['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="reviewed" <?php echo $application['status'] === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                            <option value="contacted" <?php echo $application['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                                            <option value="rejected" <?php echo $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                            <option value="accepted" <?php echo $application['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                        </select>
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md">Update</button>
                                    </form>
                                </div>
                                <a href="contact.php?user_id=<?php echo $application['job_seeker_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">Contact Applicant</a>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Application Details for Job Seekers and Admins -->
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <div class="mb-4">
                                <h4 class="font-semibold mb-2">Cover Letter</h4>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?></p>
                            </div>
                            
                            <div class="mt-4 text-sm text-gray-500 flex items-center">
                                <i class="far fa-clock mr-1"></i>
                                <span>Applied on <?php echo date('M j, Y', strtotime($application['created_at'])); ?></span>
                                <?php if ($application['updated_at'] !== $application['created_at']): ?>
                                <span class="mx-2">•</span>
                                <span>Last updated on <?php echo date('M j, Y', strtotime($application['updated_at'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> AgentConnect. All rights reserved.</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
