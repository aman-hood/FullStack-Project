<?php
session_start();
include 'db.php';

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
    <title>AgentConnect - Connect Job Seekers with Hiring Agents</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
    <style>
        .hero-section {
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1521737711867-e3b97375f902?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-blue-600">AgentConnect</a>
            <div class="space-x-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="text-blue-600 hover:text-blue-800">Login</a>
                    <a href="register.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Register</a>
                <?php endif; ?>
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

    <!-- Hero Section -->
    <section class="hero-section text-white py-32">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-6">Connect with the Right Opportunities</h1>
            <p class="text-xl mb-10 max-w-2xl mx-auto">AgentConnect bridges the gap between talented job seekers and hiring agents, making the recruitment process seamless and efficient.</p>
            <div class="flex justify-center space-x-6">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php?role=job_seeker" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-lg font-semibold">I'm a Job Seeker</a>
                    <a href="register.php?role=agent" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-lg font-semibold">I'm a Hiring Agent</a>
                <?php else: ?>
                    <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-lg font-semibold">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">How AgentConnect Works</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Create Your Profile</h3>
                    <p class="text-gray-600">Register as a job seeker or agent and create a detailed profile showcasing your skills or company.</p>
                </div>
                <!-- Feature 2 -->
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Connect & Apply</h3>
                    <p class="text-gray-600">Job seekers can browse agents and apply for positions. Agents can search for qualified candidates.</p>
                </div>
                <!-- Feature 3 -->
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Communicate</h3>
                    <p class="text-gray-600">Direct messaging system allows seamless communication between job seekers and agents.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Success Stories</h2>
            <div class="grid md:grid-cols-2 gap-8">

                <!-- Testimonial  -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <p class="text-gray-600 mb-4">"AgentConnect got me hired fast. Within weeks, I was working my dream job—all thanks to the perfect agent match."</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full mr-4"><img src="Image/aman.jpeg" alt="Description" class="w-full max-w-sm rounded-lg"></div>
                        
                        <div>
                            <h4 class="font-semibold">Aman Kumar</h4>
                            <p class="text-sm text-gray-500">C++ Developer</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <p class="text-gray-600 mb-4">"AgentConnect helped me find my dream job within weeks. The platform made it easy to connect with the right agents in my industry."</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full mr-4"><img src="Image/chandu.jpeg" alt="Description" class="w-full max-w-sm rounded-lg"></div>
                        <div>
                            <h4 class="font-semibold">Naga Chandu</h4>
                            <p class="text-sm text-gray-500">Software Developer</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <p class="text-gray-600 mb-4">"AgentConnect swiftly connected me with top agents—secured my ideal role within weeks."</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full mr-4"><img src="Image/monish.png" alt="Description" class="w-full max-w-sm rounded-lg"></div>
                        <div>
                            <h4 class="font-semibold">Monish Surya</h4>
                            <p class="text-sm text-gray-500">Web Developer</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <p class="text-gray-600 mb-4">"As a hiring agent, I've been able to find qualified candidates much faster. The platform's filtering tools are incredibly useful."</p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full mr-4"><img src="Image/anvesha.jpg" alt="Description" class="w-full max-w-sm rounded-lg"></div>
                        <div>
                            <h4 class="font-semibold">Anvesha Sharma</h4>
                            <p class="text-sm text-gray-500">Recruitment Specialist</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h3 class="text-xl font-bold">AgentConnect</h3>
                    <p class="text-gray-400">Connecting talent with opportunity</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-blue-400">About</a>
                    <a href="#" class="hover:text-blue-400">Privacy</a>
                    <a href="#" class="hover:text-blue-400">Terms</a>
                    <a href="#" class="hover:text-blue-400">Contact</a>
                </div>
            </div>
            <div class="mt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> AgentConnect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
