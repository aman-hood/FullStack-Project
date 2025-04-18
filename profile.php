<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please login to access your profile', 'error');
}

// Get user information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Get user profile information based on role
if ($role === 'job_seeker') {
    $stmt = $conn->prepare("SELECT * FROM job_seeker_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $email = $user['email'];
    
    // Process profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = sanitize_input($_POST['full_name']);
        $phone = sanitize_input($_POST['phone']);
        $address = sanitize_input($_POST['address']);
        $skills = sanitize_input($_POST['skills']);
        $experience = sanitize_input($_POST['experience']);
        $education = sanitize_input($_POST['education']);
        
        // Update profile in database
        $stmt = $conn->prepare("UPDATE job_seeker_profiles SET 
                               full_name = ?, 
                               phone = ?, 
                               address = ?, 
                               skills = ?, 
                               experience = ?, 
                               education = ? 
                               WHERE user_id = ?");
        $stmt->bind_param("ssssssi", $full_name, $phone, $address, $skills, $experience, $education, $user_id);
        
        if ($stmt->execute()) {
            redirect('profile.php', 'Profile updated successfully!', 'success');
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
} elseif ($role === 'agent') {
    $stmt = $conn->prepare("SELECT * FROM agent_profiles WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $email = $user['email'];
    
    // Process profile update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $company_name = sanitize_input($_POST['company_name']);
        $position = sanitize_input($_POST['position']);
        $company_description = sanitize_input($_POST['company_description']);
        $industry = sanitize_input($_POST['industry']);
        $phone = sanitize_input($_POST['phone']);
        
        // Update profile in database
        $stmt = $conn->prepare("UPDATE agent_profiles SET 
                               company_name = ?, 
                               position = ?, 
                               company_description = ?, 
                               industry = ?, 
                               phone = ? 
                               WHERE user_id = ?");
        $stmt->bind_param("sssssi", $company_name, $position, $company_description, $industry, $phone, $user_id);
        
        if ($stmt->execute()) {
            redirect('profile.php', 'Profile updated successfully!', 'success');
        } else {
            $error = "Error updating profile: " . $conn->error;
        }
    }
} else {
    // Admin or other role
    $profile = null;
    
    // Get user email
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $email = $user['email'];
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
    <title>Profile - AgentConnect</title>
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

    <!-- Profile Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 text-white py-4 px-6">
                <h2 class="text-2xl font-bold">Your Profile</h2>
                <p>Update your personal information</p>
            </div>
            
            <div class="p-6">
                <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($role === 'job_seeker'): ?>
                <!-- Job Seeker Profile Form -->
                <form action="profile.php" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($username); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                        </div>
                        
                        <div>
                            <label for="full_name" class="block text-gray-700 font-semibold mb-2">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo isset($profile['full_name']) ? htmlspecialchars($profile['full_name']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-gray-700 font-semibold mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo isset($profile['phone']) ? htmlspecialchars($profile['phone']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="address" class="block text-gray-700 font-semibold mb-2">Address</label>
                        <textarea id="address" name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500"><?php echo isset($profile['address']) ? htmlspecialchars($profile['address']) : ''; ?></textarea>
                    </div>
                    
                    <div>
                        <label for="skills" class="block text-gray-700 font-semibold mb-2">Skills</label>
                        <textarea id="skills" name="skills" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="List your skills (e.g., JavaScript, Project Management, Communication)"><?php echo isset($profile['skills']) ? htmlspecialchars($profile['skills']) : ''; ?></textarea>
                    </div>
                    
                    <div>
                        <label for="experience" class="block text-gray-700 font-semibold mb-2">Work Experience</label>
                        <textarea id="experience" name="experience" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Describe your work experience"><?php echo isset($profile['experience']) ? htmlspecialchars($profile['experience']) : ''; ?></textarea>
                    </div>
                    
                    <div>
                        <label for="education" class="block text-gray-700 font-semibold mb-2">Education</label>
                        <textarea id="education" name="education" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="List your educational background"><?php echo isset($profile['education']) ? htmlspecialchars($profile['education']) : ''; ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-md">Update Profile</button>
                    </div>
                </form>
                <?php elseif ($role === 'agent'): ?>
                <!-- Agent Profile Form -->
                <form action="profile.php" method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($username); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100" readonly>
                        </div>
                        
                        <div>
                            <label for="company_name" class="block text-gray-700 font-semibold mb-2">Company Name</label>
                            <input type="text" id="company_name" name="company_name" value="<?php echo isset($profile['company_name']) ? htmlspecialchars($profile['company_name']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" required>
                        </div>
                        
                        <div>
                            <label for="position" class="block text-gray-700 font-semibold mb-2">Your Position</label>
                            <input type="text" id="position" name="position" value="<?php echo isset($profile['position']) ? htmlspecialchars($profile['position']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="industry" class="block text-gray-700 font-semibold mb-2">Industry</label>
                            <input type="text" id="industry" name="industry" value="<?php echo isset($profile['industry']) ? htmlspecialchars($profile['industry']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-gray-700 font-semibold mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo isset($profile['phone']) ? htmlspecialchars($profile['phone']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="company_description" class="block text-gray-700 font-semibold mb-2">Company Description</label>
                        <textarea id="company_description" name="company_description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Describe your company"><?php echo isset($profile['company_description']) ? htmlspecialchars($profile['company_description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-md">Update Profile</button>
                    </div>
                </form>
                <?php else: ?>
                <!-- Admin Profile Form -->
                <div class="text-center py-8">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-shield text-blue-600 text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($username); ?></h3>
                    <p class="text-gray-500"><?php echo htmlspecialchars($email); ?></p>
                    <p class="mt-4 text-gray-700">You are logged in as an administrator.</p>
                </div>
                <?php endif; ?>
            </div>
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
