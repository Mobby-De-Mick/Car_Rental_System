<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection and admin check
require_once "connect.php";
require_once __DIR__ . '/check_admin.php';

// Verify table exists
$table_check = mysqli_query($connect, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_check) == 0) {
    die("<div class='error'>Error: The 'users' table does not exist in the database.</div>");
}

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $results_per_page;

// Search functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : '';
$search_condition = $search ? 
    "WHERE fname LIKE '%$search%' OR lname LIKE '%$search%' OR email LIKE '%$search%' OR mobile LIKE '%$search%'" : 
    '';

// Fetch users with pagination and search
$res = "SELECT * FROM users $search_condition ORDER BY created_at DESC LIMIT $offset, $results_per_page"; 
$result = mysqli_query($connect, $res);

if (!$result) {
    die("<div class='error'>Error fetching data: " . mysqli_error($connect) . "</div>");
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM users $search_condition";
$count_result = mysqli_query($connect, $count_query);
$total_row = mysqli_fetch_assoc($count_result);
$total_users = $total_row['total'];
$total_pages = ceil($total_users / $results_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Mobby Cars</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #7b7fed;
            --secondary-color: #5bc0de;
            --danger-color: #d9534f;
            --success-color: #5cb85c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-nav {
            background-color: var(--dark-color);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .admin-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 15px;
        }
        
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .admin-nav a:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .admin-nav a.active {
            background-color: var(--primary-color);
        }
        
        .header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .search-btn {
            padding: 10px 20px;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .search-btn:hover {
            background-color: #46b8da;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 5px;
            overflow: hidden;
        }
        
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .user-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        .user-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .edit-btn {
            background-color: var(--success-color);
            color: white;
        }
        
        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }
        
        .action-btn:hover {
            opacity: 0.9;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .page-btn {
            padding: 8px 15px;
            border: 1px solid #ddd;
            background-color: white;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .page-btn:hover, .page-btn.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .stats-card {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .error {
            color: var(--danger-color);
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            color: var(--success-color);
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .user-table {
                display: block;
                overflow-x: auto;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .admin-nav ul {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Built-in navigation to replace admin_nav.php -->
        <nav class="admin-nav">
            <ul>
                <li><a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="cars.php"><i class="fas fa-car"></i> Manage Cars</a></li>
                <li><a href="viewuser.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="report0.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <div class="header">
            <h1><i class="fas fa-users"></i> User Management</h1>
            <div>
                
                </a>
            </div>
        </div>
        
        <?php
        // Display success/error messages if any
        if (isset($_GET['success'])) {
            echo '<div class="success">' . htmlspecialchars($_GET['success']) . '</div>';
        }
        if (isset($_GET['error'])) {
            echo '<div class="error">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>
        
        <div class="stats-card">
            Total Users: <?php echo $total_users; ?>
        </div>
        
        <form method="GET" action="viewuser.php" class="search-container">
            <input type="text" name="search" class="search-input" placeholder="Search users..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
            </button>
            <?php if ($search): ?>
                <a href="viewuser.php" class="action-btn delete-btn">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </form>
        
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Age</th>
                    <th>Mobile</th>
                    <th>License No.</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($user = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($user['fname'] . ' ' . htmlspecialchars($user['lname'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['age']); ?></td>
                            <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($user['dlno']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="view_user.php?id=<?php echo $user['user_id']; ?>" class="action-btn view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_user.php?id=<?php echo $user['user_id']; ?>" 
                                   class="action-btn delete-btn"
                                   onclick="return confirm('Are you sure you want to delete this user?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center;">No users found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="viewuser.php?page=1&search=<?php echo urlencode($search); ?>" class="page-btn">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="viewuser.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                        <i class="fas fa-angle-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php 
                // Show page numbers
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="viewuser.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="viewuser.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="viewuser.php?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>" class="page-btn">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Confirm before deleting
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this user?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>