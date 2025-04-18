<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please login to submit an application', 'error');
}

// Check if user is a job seeker
if ($_SESSION['role'] !== 'job_seeker') {
    redirect('dashboard.php', 'Only job seekers can submit applications', 'error');
}

// Get job listing ID from URL
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;

// Check if job ID is valid
if ($job_id <= 0) {
    redirect('job_listings.php', 'Invalid job listing', 'error');
}

// Get job listing details
$stmt = $conn->prepare("SELECT j.*, u.username as agent_name FROM job_listings j 
                        JOIN users u ON j.agent_id = u.id 
                        WHERE j.id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('job_listings.php', 'Job listing not found', 'error');
}

$job = $result->fetch_assoc();

// Check if user has already applied for this job
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id FROM applications WHERE job_seeker_id = ? AND job_listing_id = ?");
$stmt->bind_param("ii", $user_id, $job_id);
$stmt->execute();
$result = $stmt->get_result();

$already_applied = ($result->num_rows > 0);

// Process application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already_applied) {
    $cover_letter = sanitize_input($_POST['cover_letter']);
    
    // Insert application into database
    $stmt = $conn->prepare("INSERT INTO applications (job_seeker_id, job_listing_id, cover_letter, status) 
                           VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iis", $user_id, $job_id, $cover_letter);
    
    if ($stmt->execute()) {
        redirect('applications.php', 'Application submitted successfully!', 'success');
    } else {
        $error = "Error submitting application: " . $conn->error;
    }
}

// Get job seeker profile
$stmt = $conn->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job - AgentConnect</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-blue-600">AgentConnect</a>
            <div class="space-x-4">
                <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Application Form -->
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 text-white py-4 px-6">
                <h2 class="text-2xl font-bold">Apply for Job</h2>
                <p><?php echo htmlspecialchars($job['title']); ?> at <?php echo htmlspecialchars($job['company_name']); ?></p>
            </div>
            
            <div class="p-6">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($already_applied): ?>
                <div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-6">
                    <p>You have already applied for this position.</p>
                    <p class="mt-2">
                        <a href="applications.php" class="text-blue-600 hover:underline">View your applications</a>
                    </p>
                </div>
                <?php else: ?>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Job Details</h3>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="mb-2"><strong>Title:</strong> <?php echo htmlspecialchars($job['title']); ?></p>
                        <p class="mb-2"><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?></p>
                        <p class="mb-2"><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                        <p class="mb-2"><strong>Salary Range:</strong> <?php echo htmlspecialchars($job['salary_range']); ?></p>
                        <p class="mb-2"><strong>Posted by:</strong> <?php echo htmlspecialchars($job['agent_name']); ?></p>
                        <p class="mb-4"><strong>Posted on:</strong> <?php echo date('F j, Y', strtotime($job['created_at'])); ?></p>
                        
                        <div class="mb-2"><strong>Description:</strong></div>
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                        
                        <div class="mb-2"><strong>Requirements:</strong></div>
                        <p><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Your Profile</h3>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($profile['full_name']); ?></p>
                        <?php if (!empty($profile['phone'])): ?>
                        <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($profile['skills'])): ?>
                        <div class="mb-2"><strong>Skills:</strong></div>
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($profile['skills'])); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($profile['experience'])): ?>
                        <div class="mb-2"><strong>Experience:</strong></div>
                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($profile['experience'])); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($profile['education'])): ?>
                        <div class="mb-2"><strong>Education:</strong></div>
                        <p><?php echo nl2br(htmlspecialchars($profile['education'])); ?></p>
                        <?php endif; ?>
                        
                        <?php if (empty($profile['skills']) || empty($profile['experience']) || empty($profile['education'])): ?>
                        <div class="bg-yellow-100 text-yellow-800 p-3 rounded mt-4">
                            <p>Your profile is incomplete. Consider <a href="profile.php" class="text-blue-600 hover:underline">updating your profile</a> before applying.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form action="apply.php?job_id=<?php echo $job_id; ?>" method="POST">
                    <div class="mb-6">
                        <label for="cover_letter" class="block text-gray-700 font-semibold mb-2">Cover Letter</label>
                        <textarea id="cover_letter" name="cover_letter" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required></textarea>
                        <p class="text-sm text-gray-500 mt-1">Explain why you're a good fit for this position.</p>
                    </div>
                    
                    <div class="flex justify-between">
                        <a href="job_listings.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-md">Back to Jobs</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-md">Submit Application</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
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
