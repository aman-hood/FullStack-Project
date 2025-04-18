<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please login to view job listings', 'error');
}

// Get user information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Handle job listing creation for agents
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'agent') {
    $title = sanitize_input($_POST['title']);
    $description = sanitize_input($_POST['description']);
    $requirements = sanitize_input($_POST['requirements']);
    $location = sanitize_input($_POST['location']);
    $salary_range = sanitize_input($_POST['salary_range']);
    
    // Get company name from agent profile
    $stmt = $conn->prepare("SELECT company_name FROM agent_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company_name = $result->fetch_assoc()['company_name'];
    
    // Insert job listing into database
    $stmt = $conn->prepare("INSERT INTO job_listings (agent_id, title, description, requirements, location, salary_range, company_name) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $user_id, $title, $description, $requirements, $location, $salary_range, $company_name);
    
    if ($stmt->execute()) {
        $success_message = "Job listing created successfully!";
    } else {
        $error_message = "Error creating job listing: " . $conn->error;
    }
}

// Handle job listing deletion for agents
if (isset($_GET['delete']) && $role === 'agent') {
    $job_id = intval($_GET['delete']);
    
    // Check if job belongs to this agent
    $stmt = $conn->prepare("SELECT id FROM job_listings WHERE id = ? AND agent_id = ?");
    $stmt->bind_param("ii", $job_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete job listing
        $stmt = $conn->prepare("DELETE FROM job_listings WHERE id = ?");
        $stmt->bind_param("i", $job_id);
        
        if ($stmt->execute()) {
            $success_message = "Job listing deleted successfully!";
        } else {
            $error_message = "Error deleting job listing: " . $conn->error;
        }
    } else {
        $error_message = "You don't have permission to delete this job listing.";
    }
}

// Get job listings based on role
if ($role === 'job_seeker') {
    // Job seekers see all job listings
    $query = "SELECT j.*, u.username as agent_username FROM job_listings j 
              JOIN users u ON j.agent_id = u.id 
              ORDER BY j.created_at DESC";
    $result = $conn->query($query);
    $job_listings = $result->fetch_all(MYSQLI_ASSOC);
} elseif ($role === 'agent') {
    // Agents see only their own job listings
    $stmt = $conn->prepare("SELECT * FROM job_listings WHERE agent_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $job_listings = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Admins see all job listings
    $query = "SELECT j.*, u.username as agent_username FROM job_listings j 
              JOIN users u ON j.agent_id = u.id 
              ORDER BY j.created_at DESC";
    $result = $conn->query($query);
    $job_listings = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>Job Listings - AgentConnect</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

    <!-- Job Listings Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">
                <?php if ($role === 'job_seeker'): ?>
                    Available Job Listings
                <?php elseif ($role === 'agent'): ?>
                    Your Job Listings
                <?php else: ?>
                    All Job Listings
                <?php endif; ?>
            </h2>
            
            <?php if ($role === 'agent'): ?>
            <button id="create-job-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                <i class="fas fa-plus mr-2"></i> Create New Job
            </button>
            <?php endif; ?>
        </div>
        
        <?php if ($role === 'job_seeker'): ?>
        <!-- Job Filter Form for Job Seekers -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Filter Jobs</h3>
            <form id="job-filter-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="filter-keyword" class="block text-gray-700 mb-2">Keyword</label>
                    <input type="text" id="filter-keyword" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Job title, skills, etc.">
                </div>
                <div>
                    <label for="filter-location" class="block text-gray-700 mb-2">Location</label>
                    <input type="text" id="filter-location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="City, state, remote, etc.">
                </div>
                <div>
                    <label for="filter-industry" class="block text-gray-700 mb-2">Industry</label>
                    <input type="text" id="filter-industry" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Technology, finance, etc.">
                </div>
                <div class="md:col-span-3 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if ($role === 'agent'): ?>
        <!-- Job Creation Form for Agents (Hidden by default) -->
        <div id="create-job-form" class="bg-white rounded-lg shadow-md p-6 mb-6 hidden">
            <h3 class="text-lg font-semibold mb-4">Create New Job Listing</h3>
            <form action="job_listings.php" method="POST" class="space-y-4">
                <div>
                    <label for="title" class="block text-gray-700 mb-2">Job Title</label>
                    <input type="text" id="title" name="title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="location" class="block text-gray-700 mb-2">Location</label>
                        <input type="text" id="location" name="location" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="City, state, or Remote" required>
                    </div>
                    <div>
                        <label for="salary_range" class="block text-gray-700 mb-2">Salary Range</label>
                        <input type="text" id="salary_range" name="salary_range" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="e.g. $50,000 - $70,000">
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-gray-700 mb-2">Job Description</label>
                    <textarea id="description" name="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required></textarea>
                </div>
                <div>
                    <label for="requirements" class="block text-gray-700 mb-2">Requirements</label>
                    <textarea id="requirements" name="requirements" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" id="cancel-job-btn" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">Create Job</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Job Listings -->
        <div id="job-listings-container" class="space-y-4">
            <?php if (empty($job_listings)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <p class="text-gray-500">
                    <?php if ($role === 'job_seeker'): ?>
                        No job listings available at the moment.
                    <?php elseif ($role === 'agent'): ?>
                        You haven't posted any job listings yet.
                    <?php else: ?>
                        No job listings in the system.
                    <?php endif; ?>
                </p>
            </div>
            <?php else: ?>
                <?php foreach ($job_listings as $job): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($job['title']); ?></h3>
                                <p class="text-gray-600 mb-1"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                <p class="text-gray-500 mb-4"><?php echo htmlspecialchars($job['location']); ?> 
                                    <?php if (!empty($job['salary_range'])): ?>
                                    <span class="mx-2">•</span> <?php echo htmlspecialchars($job['salary_range']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <?php if ($role === 'job_seeker'): ?>
                                <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-block">Apply Now</a>
                                <?php elseif ($role === 'agent'): ?>
                                <div class="flex space-x-2">
                                    <a href="job_applications.php?job_id=<?php echo $job['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md inline-block text-sm">View Applications</a>
                                    <a href="job_listings.php?delete=<?php echo $job['id']; ?>" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md inline-block text-sm" onclick="return confirm('Are you sure you want to delete this job listing?')">Delete</a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="text-gray-700 mb-4">
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 200) . (strlen($job['description']) > 200 ? '...' : ''))); ?></p>
                                <?php if (strlen($job['description']) > 200): ?>
                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="text-blue-600 hover:underline text-sm">Read more</a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex flex-wrap gap-2 mt-4">
                                <?php
                                // Extract skills from requirements
                                $requirements = $job['requirements'];
                                $skills = [];
                                if (preg_match_all('/\b[A-Za-z0-9#\+]+(?:\s[A-Za-z0-9#\+]+)*\b/', $requirements, $matches)) {
                                    $skills = array_slice($matches[0], 0, 5);
                                }
                                
                                foreach ($skills as $skill):
                                ?>
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4 text-sm text-gray-500 flex items-center">
                                <i class="far fa-clock mr-1"></i>
                                <span>Posted <?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
                                <?php if (isset($job['agent_username']) && $role !== 'agent'): ?>
                                <span class="mx-2">•</span>
                                <span>by <?php echo htmlspecialchars($job['agent_username']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
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

    <script>
        // Toggle job creation form
        $(document).ready(function() {
            $('#create-job-btn').click(function() {
                $('#create-job-form').slideDown();
                $(this).hide();
            });
            
            $('#cancel-job-btn').click(function() {
                $('#create-job-form').slideUp();
                $('#create-job-btn').show();
            });
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
