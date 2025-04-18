<!-- Main Content -->
<div class="w-full md:w-3/4">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <?php if ($role === 'job_seeker'): ?>
        <!-- Job Seeker Stats -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-file-alt text-blue-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">My Applications</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_applications']; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-user-tie text-green-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">Available Agents</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_agents']; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-briefcase text-purple-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">Available Jobs</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_job_listings']; ?></h3>
                </div>
            </div>
        </div>
        <?php elseif ($role === 'agent'): ?>
        <!-- Agent Stats -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-briefcase text-blue-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">My Job Listings</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_job_listings']; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-file-alt text-green-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">Applications Received</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_applications']; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-users text-purple-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">Available Job Seekers</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_job_seekers']; ?></h3>
                </div>
            </div>
        </div>
        <?php elseif ($role === 'admin'): ?>
        <!-- Admin Stats -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-users text-blue-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">Total Users</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_users']; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-file-alt text-green-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">Total Applications</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_applications']; ?></h3>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-briefcase text-purple-600"></i>
                </div>
                <div>
                    <p class="text-gray-500">Total Job Listings</p>
                    <h3 class="text-2xl font-bold"><?php echo $stats['total_job_listings']; ?></h3>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Recent Activity</h2>
        
        <?php if ($role === 'job_seeker'): ?>
        <!-- Job Seeker Recent Activity -->
        <div class="space-y-4">
            <div class="border-b border-gray-200 pb-4">
                <h3 class="font-medium">Recent Applications</h3>
                <?php
                $stmt = $conn->prepare("SELECT a.*, j.title, j.company_name FROM applications a 
                                        JOIN job_listings j ON a.job_listing_id = j.id 
                                        WHERE a.job_seeker_id = ? 
                                        ORDER BY a.created_at DESC LIMIT 5");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $recent_applications = $stmt->get_result();
                
                if ($recent_applications->num_rows > 0):
                ?>
                <ul class="mt-2 space-y-2">
                    <?php while ($app = $recent_applications->fetch_assoc()): ?>
                    <li class="flex justify-between items-center">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($app['title']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($app['company_name']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php 
                            switch($app['status']) {
                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'reviewed': echo 'bg-blue-100 text-blue-800'; break;
                                case 'contacted': echo 'bg-purple-100 text-purple-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($app['status']); ?>
                        </span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="text-gray-500 mt-2">You haven't submitted any applications yet.</p>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="applications.php" class="text-blue-600 hover:underline text-sm">View all applications</a>
                </div>
            </div>
            
            <div>
                <h3 class="font-medium">Recommended Jobs</h3>
                <?php
                // Simple recommendation based on job seeker's skills
                if (isset($profile['skills']) && !empty($profile['skills'])) {
                    $skills_array = explode(',', $profile['skills']);
                    $skill_search = '';
                    foreach ($skills_array as $skill) {
                        $skill = trim($skill);
                        if (!empty($skill)) {
                            $skill_search .= " OR j.requirements LIKE '%" . $conn->real_escape_string($skill) . "%'";
                        }
                    }
                    
                    if (!empty($skill_search)) {
                        $skill_search = substr($skill_search, 4); // Remove the first " OR "
                        $query = "SELECT j.* FROM job_listings j WHERE $skill_search ORDER BY j.created_at DESC LIMIT 3";
                        $result = $conn->query($query);
                        
                        if ($result && $result->num_rows > 0):
                        ?>
                        <ul class="mt-2 space-y-2">
                            <?php while ($job = $result->fetch_assoc()): ?>
                            <li>
                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="block hover:bg-gray-50 p-2 rounded">
                                    <p class="font-medium"><?php echo htmlspecialchars($job['title']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($job['location']); ?></p>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                        <?php else: ?>
                        <p class="text-gray-500 mt-2">No recommended jobs found based on your skills.</p>
                        <?php endif;
                    } else {
                        echo '<p class="text-gray-500 mt-2">Add skills to your profile to get job recommendations.</p>';
                    }
                } else {
                    echo '<p class="text-gray-500 mt-2">Add skills to your profile to get job recommendations.</p>';
                }
                ?>
                <div class="mt-4">
                    <a href="job_listings.php" class="text-blue-600 hover:underline text-sm">Browse all jobs</a>
                </div>
            </div>
        </div>
        <?php elseif ($role === 'agent'): ?>
        <!-- Agent Recent Activity -->
        <div class="space-y-4">
            <div class="border-b border-gray-200 pb-4">
                <h3 class="font-medium">Recent Applications</h3>
                <?php
                $stmt = $conn->prepare("SELECT a.*, j.title, u.username FROM applications a 
                                        JOIN job_listings j ON a.job_listing_id = j.id 
                                        JOIN users u ON a.job_seeker_id = u.id
                                        WHERE j.agent_id = ? 
                                        ORDER BY a.created_at DESC LIMIT 5");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $recent_applications = $stmt->get_result();
                
                if ($recent_applications->num_rows > 0):
                ?>
                <ul class="mt-2 space-y-2">
                    <?php while ($app = $recent_applications->fetch_assoc()): ?>
                    <li class="flex justify-between items-center">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($app['title']); ?></p>
                            <p class="text-sm text-gray-500">Applicant: <?php echo htmlspecialchars($app['username']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php 
                            switch($app['status']) {
                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                case 'reviewed': echo 'bg-blue-100 text-blue-800'; break;
                                case 'contacted': echo 'bg-purple-100 text-purple-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($app['status']); ?>
                        </span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="text-gray-500 mt-2">You haven't received any applications yet.</p>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="applications.php" class="text-blue-600 hover:underline text-sm">View all applications</a>
                </div>
            </div>
            
            <div>
                <h3 class="font-medium">Your Job Listings</h3>
                <?php
                $stmt = $conn->prepare("SELECT * FROM job_listings WHERE agent_id = ? ORDER BY created_at DESC LIMIT 3");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $recent_jobs = $stmt->get_result();
                
                if ($recent_jobs->num_rows > 0):
                ?>
                <ul class="mt-2 space-y-2">
                    <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                    <li>
                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="block hover:bg-gray-50 p-2 rounded">
                            <p class="font-medium"><?php echo htmlspecialchars($job['title']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($job['location']); ?></p>
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="text-gray-500 mt-2">You haven't posted any job listings yet.</p>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="job_listings.php" class="text-blue-600 hover:underline text-sm">Manage job listings</a>
                </div>
            </div>
        </div>
        <?php elseif ($role === 'admin'): ?>
        <!-- Admin Recent Activity -->
        <div class="space-y-4">
            <div class="border-b border-gray-200 pb-4">
                <h3 class="font-medium">Recent Users</h3>
                <?php
                $result = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
                if ($result->num_rows > 0):
                ?>
                <ul class="mt-2 space-y-2">
                    <?php while ($user = $result->fetch_assoc()): ?>
                    <li class="flex justify-between items-center">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full 
                            <?php 
                            switch($user['role']) {
                                case 'job_seeker': echo 'bg-blue-100 text-blue-800'; break;
                                case 'agent': echo 'bg-green-100 text-green-800'; break;
                                case 'admin': echo 'bg-purple-100 text-purple-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                        </span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="text-gray-500 mt-2">No users found.</p>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="users.php" class="text-blue-600 hover:underline text-sm">Manage all users</a>
                </div>
            </div>
            
            <div>
                <h3 class="font-medium">Recent Job Listings</h3>
                <?php
                $result = $conn->query("SELECT j.*, u.username FROM job_listings j JOIN users u ON j.agent_id = u.id ORDER BY j.created_at DESC LIMIT 3");
                if ($result->num_rows > 0):
                ?>
                <ul class="mt-2 space-y-2">
                    <?php while ($job = $result->fetch_assoc()): ?>
                    <li>
                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="block hover:bg-gray-50 p-2 rounded">
                            <p class="font-medium"><?php echo htmlspecialchars($job['title']); ?></p>
                            <p class="text-sm text-gray-500">Posted by: <?php echo htmlspecialchars($job['username']); ?></p>
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php else: ?>
                <p class="text-gray-500 mt-2">No job listings found.</p>
                <?php endif; ?>
                <div class="mt-4">
                    <a href="job_listings.php" class="text-blue-600 hover:underline text-sm">View all job listings</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
