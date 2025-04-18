<?php
session_start();
include 'db.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

// Get role from URL parameter, default to job_seeker
$role = isset($_GET['role']) ? sanitize_input($_GET['role']) : 'job_seeker';
if (!in_array($role, ['job_seeker', 'agent'])) {
    $role = 'job_seeker';
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    $role = sanitize_input($_POST['role']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Username or email already exists";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash password
        $hashed_password = hash_password($password);
        
        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            
            // Create profile based on role
            if ($role === 'job_seeker') {
                $stmt = $conn->prepare("INSERT INTO job_seeker_profiles (user_id, full_name) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $username);
            } else {
                $stmt = $conn->prepare("INSERT INTO agent_profiles (user_id, company_name) VALUES (?, 'Your Company')");
                $stmt->bind_param("i", $user_id);
            }
            
            if ($stmt->execute()) {
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                
                // Redirect to dashboard
                redirect('dashboard.php', 'Registration successful! Welcome to AgentConnect.', 'success');
            } else {
                $errors[] = "Error creating profile: " . $conn->error;
            }
        } else {
            $errors[] = "Error registering user: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AgentConnect</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-blue-600">AgentConnect</a>
            <div class="space-x-4">
                <a href="login.php" class="text-blue-600 hover:text-blue-800">Login</a>
            </div>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="container mx-auto px-4 py-12">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 text-white py-4 px-6">
                <h2 class="text-2xl font-bold">Create an Account</h2>
                <p>Join AgentConnect as a <?php echo ucfirst(str_replace('_', ' ', $role)); ?></p>
            </div>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="bg-red-100 text-red-700 p-4 border-l-4 border-red-500">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="register.php" method="POST" class="py-6 px-8">
                <input type="hidden" name="role" value="<?php echo $role; ?>">
                
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Enter your Name" required>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Email Address</label>
                    <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Enter your Email" required>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Enter Password" required>
                    <p class="text-sm text-gray-500 mt-1">Must be at least 6 characters</p>
                </div>
                
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500" placeholder="Enter Password" required>
                </div>
                
                <div class="mb-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="terms" name="terms" class="mr-2" required>
                        <label for="terms" class="text-gray-700">I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a></label>
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:bg-blue-700">Register</button>
                
                <div class="mt-4 text-center">
                    <p class="text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:underline">Login</a></p>
                </div>
                
                <?php if ($role === 'job_seeker'): ?>
                    <div class="mt-4 text-center">
                        <p class="text-gray-600">Are you a hiring agent? <a href="register.php?role=agent" class="text-blue-600 hover:underline">Register as Agent</a></p>
                    </div>
                <?php else: ?>
                    <div class="mt-4 text-center">
                        <p class="text-gray-600">Looking for a job? <a href="register.php?role=job_seeker" class="text-blue-600 hover:underline">Register as Job Seeker</a></p>
                    </div>
                <?php endif; ?>
            </form>
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
