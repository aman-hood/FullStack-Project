<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php', 'Please login to view messages', 'error');
}

// Get user information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Handle sending new messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id']) && isset($_POST['message'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = sanitize_input($_POST['message']);
    
    // Insert message into database
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $receiver_id, $message_text);
    
    if ($stmt->execute()) {
        $success_message = "Message sent successfully!";
    } else {
        $error_message = "Error sending message: " . $conn->error;
    }
}

// Mark messages as read
if (isset($_GET['mark_read']) && $_GET['mark_read'] === 'all') {
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Get conversations (unique users the current user has exchanged messages with)
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN sender_id = ? THEN receiver_id 
            ELSE sender_id 
        END as contact_id,
        (SELECT username FROM users WHERE id = contact_id) as contact_username,
        (SELECT 
            CASE 
                WHEN role = 'job_seeker' THEN (SELECT full_name FROM job_seeker_profiles WHERE user_id = contact_id)
                WHEN role = 'agent' THEN (SELECT company_name FROM agent_profiles WHERE user_id = contact_id)
                ELSE username
            END 
        FROM users WHERE id = contact_id) as contact_name,
        (SELECT role FROM users WHERE id = contact_id) as contact_role,
        (SELECT COUNT(*) FROM messages WHERE ((sender_id = ? AND receiver_id = contact_id) OR (sender_id = contact_id AND receiver_id = ?))) as message_count,
        (SELECT COUNT(*) FROM messages WHERE sender_id = contact_id AND receiver_id = ? AND is_read = 0) as unread_count,
        (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = contact_id) OR (sender_id = contact_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message_time
    FROM messages
    WHERE sender_id = ? OR receiver_id = ?
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get messages for a specific conversation if contact_id is provided
$selected_contact = null;
$messages = [];
if (isset($_GET['contact_id'])) {
    $contact_id = intval($_GET['contact_id']);
    
    // Get contact information
    $stmt = $conn->prepare("
        SELECT u.id, u.username, u.role,
        CASE 
            WHEN u.role = 'job_seeker' THEN (SELECT full_name FROM job_seeker_profiles WHERE user_id = u.id)
            WHEN u.role = 'agent' THEN (SELECT company_name FROM agent_profiles WHERE user_id = u.id)
            ELSE u.username
        END as display_name
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $contact_id);
    $stmt->execute();
    $selected_contact = $stmt->get_result()->fetch_assoc();
    
    if ($selected_contact) {
        // Get messages between current user and selected contact
        $stmt = $conn->prepare("
            SELECT m.*, 
            (SELECT username FROM users WHERE id = m.sender_id) as sender_username,
            (SELECT username FROM users WHERE id = m.receiver_id) as receiver_username
            FROM messages m
            WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("iiii", $user_id, $contact_id, $contact_id, $user_id);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Mark messages from this contact as read
        $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $stmt->bind_param("ii", $contact_id, $user_id);
        $stmt->execute();
    }
}

// Get total unread message count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$unread_count = $stmt->get_result()->fetch_assoc()['count'];

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
    <title>Messages - AgentConnect</title>
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

    <!-- Messages Content -->
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Messages</h2>
            <?php if ($unread_count > 0): ?>
            <a href="messages.php?mark_read=all" class="text-blue-600 hover:underline">Mark all as read</a>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="flex h-[600px]">
                <!-- Conversations List -->
                <div class="w-1/3 border-r border-gray-200 overflow-y-auto">
                    <div class="p-4 border-b border-gray-200">
                        <h3 class="font-semibold">Conversations</h3>
                    </div>
                    
                    <?php if (empty($conversations)): ?>
                    <div class="p-4 text-center text-gray-500">
                        <p>No conversations yet.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conversation): ?>
                        <a href="messages.php?contact_id=<?php echo $conversation['contact_id']; ?>" class="block border-b border-gray-200 hover:bg-gray-50 <?php echo isset($_GET['contact_id']) && $_GET['contact_id'] == $conversation['contact_id'] ? 'bg-blue-50' : ''; ?>">
                            <div class="p-4 flex justify-between items-center">
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($conversation['contact_name'] ?: $conversation['contact_username']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo ucfirst($conversation['contact_role']); ?></p>
                                </div>
                                <?php if ($conversation['unread_count'] > 0): ?>
                                <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded-full"><?php echo $conversation['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Messages Area -->
                <div class="w-2/3 flex flex-col">
                    <?php if ($selected_contact): ?>
                    <!-- Contact Info -->
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($selected_contact['display_name'] ?: $selected_contact['username']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo ucfirst($selected_contact['role']); ?></p>
                        </div>
                    </div>
                    
                    <!-- Messages -->
                    <div class="flex-grow p-4 overflow-y-auto">
                        <?php if (empty($messages)): ?>
                        <div class="text-center text-gray-500 my-8">
                            <p>No messages yet. Start the conversation!</p>
                        </div>
                        <?php else: ?>
                            <?php 
                            $current_date = '';
                            foreach ($messages as $msg): 
                                $msg_date = date('Y-m-d', strtotime($msg['created_at']));
                                if ($msg_date !== $current_date) {
                                    $current_date = $msg_date;
                                    echo '<div class="text-center my-4"><span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">' . date('F j, Y', strtotime($msg['created_at'])) . '</span></div>';
                                }
                            ?>
                            <div class="mb-4 <?php echo $msg['sender_id'] == $user_id ? 'text-right' : ''; ?>">
                                <div class="inline-block max-w-3/4 rounded-lg p-3 <?php echo $msg['sender_id'] == $user_id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800'; ?>">
                                    <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    <?php echo date('g:i A', strtotime($msg['created_at'])); ?>
                                    <?php if ($msg['sender_id'] == $user_id): ?>
                                        <?php if ($msg['is_read']): ?>
                                        <span class="ml-1">• Read</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Message Input -->
                    <div class="p-4 border-t border-gray-200">
                        <form action="messages.php?contact_id=<?php echo $selected_contact['id']; ?>" method="POST" class="flex">
                            <input type="hidden" name="receiver_id" value="<?php echo $selected_contact['id']; ?>">
                            <input type="text" name="message" class="flex-grow px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:border-blue-500" placeholder="Type your message..." required>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-r-md">Send</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="flex-grow flex items-center justify-center">
                        <div class="text-center text-gray-500">
                            <i class="fas fa-comments text-4xl mb-2"></i>
                            <p>Select a conversation to view messages</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?php echo date('Y'); ?> AgentConnect. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Auto-scroll to bottom of messages
        $(document).ready(function() {
            const messagesContainer = $('.overflow-y-auto');
            messagesContainer.scrollTop(messagesContainer.prop('scrollHeight'));
        });
    </script>
    <script src="script.js"></script>
</body>
</html>
