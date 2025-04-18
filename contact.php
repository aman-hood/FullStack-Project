<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please login to contact users', 'error');
}

// Check if user is an agent
if ($_SESSION['role'] !== 'agent') {
    redirect('dashboard.php', 'Only agents can use this feature', 'error');
}

// Get recipient ID from URL
$recipient_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Check if recipient ID is valid
if ($recipient_id <= 0) {
    redirect('dashboard.php', 'Invalid recipient', 'error');
}

// Get recipient details
$stmt = $conn->prepare("SELECT u.username, u.email, u.role, j.full_name 
                        FROM users u 
                        LEFT JOIN job_seeker_profiles j ON u.id = j.user_id 
                        WHERE u.id = ? AND u.role = 'job_seeker'");
$stmt->bind_param("i", $recipient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('dashboard.php', 'Recipient not found or not a job seeker', 'error');
}

$recipient = $result->fetch_assoc();

// Process message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = sanitize_input($_POST['message']);
    $sender_id = $_SESSION['user_id'];
    
    // Insert message into database
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) 
                           VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $sender_id, $recipient_id, $message);
    
    if ($stmt->execute()) {
        redirect('dashboard.php', 'Message sent successfully!', 'success');
    } else {
        $error = "Error sending message: " . $conn->error;
    }
}

// Get agent profile
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM agent_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Job Seeker - AgentConnect</title>
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

    <!-- Contact Form -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 text-white py-4 px-6">
                <h2 class="text-2xl font-bold">Contact Job Seeker</h2>
                <p>Send a message to <?php echo htmlspecialchars($recipient['full_name'] ?: $recipient['username']); ?></p>
            </div>
            
            <div class="p-6">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Recipient Information</h3>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($recipient['full_name'] ?: $recipient['username']); ?></p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-xl font-semibold mb-2">Your Information</h3>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="mb-2"><strong>Company:</strong> <?php echo htmlspecialchars($profile['company_name']); ?></p>
                        <p class="mb-2"><strong>Position:</strong> <?php echo htmlspecialchars($profile['position']); ?></p>
                        <?php if (!empty($profile['company_description'])): ?>
                        <p class="mb-2"><strong>Company Description:</strong> <?php echo htmlspecialchars($profile['company_description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form action="contact.php?user_id=<?php echo $recipient_id; ?>" method="POST">
                    <div class="mb-6">
                        <label for="message" class="block text-gray-700 font-semibold mb-2">Message</label>
                        <textarea id="message" name="message" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required></textarea>
                        <p class="text-sm text-gray-500 mt-1">Introduce yourself and explain why you're contacting this job seeker.</p>
                    </div>
                    
                    <div class="flex justify-between">
                        <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-md">Back to Dashboard</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-md">Send Message</button>
                    </div>
                </form>
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
