<?php
require_once __DIR__ . '/../php/auth_check.php';
require_login('admin');

require_once __DIR__ . '/../php/db_config.php';

$pdo = getPDO();
$employees = [];
$departments = [];
$message = '';
$message_type = '';
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Get all departments for dropdown
$deptStmt = $pdo->query('SELECT department_id, department_name FROM dept ORDER BY department_name');
$departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $fname = trim($_POST['employee_fname'] ?? '');
                $lname = trim($_POST['employee_lname'] ?? '');
                $mname = trim($_POST['employee_mname'] ?? '');
                $dept_id = intval($_POST['department_id'] ?? 0);
                
                if (!$fname || !$lname || !$dept_id) {
                    throw new Exception('Invalid employee name or department');
                }
                
                // Get next employee_id
                $result = $pdo->query('SELECT MAX(employee_id) AS maxId FROM employee');
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $nextId = ($row && isset($row['maxId'])) ? (int)$row['maxId'] + 1 : 1;
                
                $pdo->prepare('INSERT INTO employee (employee_id, employee_fname, employee_lname, employee_mname, department_id) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$nextId, $fname, $lname, $mname, $dept_id]);
                logAudit($pdo, $user_id, 'INSERT', 'employee', $nextId, "Added employee: $fname $lname");
                $message = 'Employee added successfully!';
                $message_type = 'success';
            }
            
            elseif ($_POST['action'] === 'update') {
                $employee_id = intval($_POST['employee_id'] ?? 0);
                $fname = trim($_POST['employee_fname'] ?? '');
                $lname = trim($_POST['employee_lname'] ?? '');
                $mname = trim($_POST['employee_mname'] ?? '');
                $dept_id = intval($_POST['department_id'] ?? 0);
                
                if (!$employee_id || !$fname || !$lname || !$dept_id) {
                    throw new Exception('Invalid employee data');
                }
                
                $pdo->prepare('UPDATE employee SET employee_fname = ?, employee_lname = ?, employee_mname = ?, department_id = ? WHERE employee_id = ?')
                    ->execute([$fname, $lname, $mname, $dept_id, $employee_id]);
                logAudit($pdo, $user_id, 'UPDATE', 'employee', $employee_id, "Updated employee: $fname $lname");
                $message = 'Employee updated successfully!';
                $message_type = 'success';
            }
            
            elseif ($_POST['action'] === 'delete') {
                $employee_id = intval($_POST['employee_id'] ?? 0);
                
                if (!$employee_id) {
                    throw new Exception('Invalid employee ID');
                }
                
                // Check if employee has requests
                $check = $pdo->prepare('SELECT COUNT(*) as count FROM request WHERE employee_id = ?');
                $check->execute([$employee_id]);
                $result = $check->fetch(PDO::FETCH_ASSOC);
                
                if ($result['count'] > 0) {
                    throw new Exception('Cannot delete employee with existing requests');
                }
                
                $pdo->prepare('DELETE FROM employee WHERE employee_id = ?')->execute([$employee_id]);
                logAudit($pdo, $user_id, 'DELETE', 'employee', $employee_id, "Deleted employee ID: $employee_id");
                $message = 'Employee deleted successfully!';
                $message_type = 'success';
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get all employees with department names
$stmt = $pdo->query('SELECT e.employee_id, e.employee_fname, e.employee_lname, e.employee_mname, e.department_id, d.department_name FROM employee e LEFT JOIN dept d ON e.department_id = d.department_id ORDER BY e.employee_fname');
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: 'DM Sans', Arial, sans-serif;
            background-image: url("../image/sims_bg.png");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
            box-shadow: 2px 0 15px rgba(0,0,0,0.2);
            transition: width 0.3s ease;
            z-index: 100;
            overflow-y: auto;
            scrollbar-width: none; 
        }

        .sidebar::-webkit-scrollbar {
            display: none; 
        }

        .sidebar.expanded {
            width: 350px;
        }

        .sidebar.expanded .logout {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar .logo {
            width: 250px;
            margin-bottom: 10px;
        }

        .nav-links {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: opacity 0.2s ease;
        }

        .sidebar.expanded .nav-links {
            opacity: 0;
            pointer-events: none;
        }

        .sidebar a {
            width: 90%;
            padding: 12px 20px;
            margin: 8px 0;
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 700;
            border-radius: 10px;
            transition: 0.3s;
            display: flex;
            align-items: center;
            box-sizing: border-box;
        }

        .sidebar a:hover {
            background: rgba(15, 27, 101, 0.67);
        }

        .sidebar a.active {
            background: rgba(15, 27, 101, 0.8);
        }

        .sidebar a .icon {
            width: 30px;
            height: 30px;
            margin-right: 12px;
        }

        .sidebar a.logout {
            margin-top: auto;
            margin-bottom: 40px;
            background: rgba(15, 27, 101, 0.67);
            justify-content: center;
        }

        .sidebar a.logout:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        /* Profile Panel */
        .profile-panel {
            position: absolute;
            top: 120px;
            left: 0;
            width: 100%;
            height: calc(100% - 120px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
            box-sizing: border-box;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease 0.1s;
        }

        .sidebar.expanded .profile-panel {
            opacity: 1;
            pointer-events: all;
        }

        .profile-name {
            color: white;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            margin: 5px 0;
            line-height: 1.2;
        }

        .profile-email {
            color: white;
            font-size: 16px;
            text-align: center;
            margin: 10px 0 0 0;
        }

        .profile-logout {
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 12px 30px;
            background: rgba(15, 27, 101, 0.67);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .profile-logout:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .profile-logout img {
            width: 20px;
            height: 20px;
        }

        .profile-back {
            margin-top: auto;
            margin-bottom: 20px;
            padding: 12px 30px;
            background: rgba(15, 27, 101, 0.67);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: 0.3s;
        }

        .profile-back:hover {
            background: rgba(15, 27, 101, 0.85);
        }

        .profile-back img {
            width: 20px;
            height: 20px;
        }

        /* Main content */
        .main-content {
            margin-left: 220px;
            padding: 40px;
            height: 100vh;
            box-sizing: border-box;
            transition: margin-left 0.3s ease;
        }

        .sidebar.expanded ~ .main-content {
            margin-left: 350px;
        }

        .main-content h1 {
            color: white;
            margin: 0 0 25px 0;
            font-size: 36px;
        }

        /* Message alerts */
        .message {
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }

        .message.success {
            background: rgba(40, 167, 69, 0.3);
            color: #98fb98;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .message.error {
            background: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        /* Layout of main panel */
        .content-wrapper {
            display: flex;
            gap: 25px;
            height: calc(100vh - 120px);
        }

        /* LEFT PANEL */
        .items-panel {
            flex: 2;
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
            padding: 25px;
            border-radius: 18px;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .items-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .search-bar {
            width: 200px;
            padding: 8px 12px;
            border-radius: 20px;
            border: none;
            outline: none;
        }

        .sort-btn {
            appearance: none;
            background-color: white;
            padding: 8px 15px;
            border-radius: 20px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            color: #0F1B65;
        }

        .scroll-area {
            overflow-y: auto;
            padding-right: 10px;
        }

        .scroll-area::-webkit-scrollbar {
            width: 6px;
        }

        .scroll-area::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.5);
            border-radius: 5px;
        }

        .inner-card {
            background: rgba(255,255,255,0.85);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 12px;
            color: #0F1B65;
            box-shadow: 0px 3px 8px rgba(0,0,0,0.15);
        }

        .item-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .inner-card .edit-btn {
            background: transparent;
            border: none;
            color: #0f6513;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            padding: 5px 10px;
        }

        /* RIGHT PANEL */
        .right-panel {
            flex: 1;
            padding: 25px;
            color: white;
            text-align: center;
        }

        .right-panel h2 {
            margin-bottom: 30px;
        }

        .right-panel label {
            display: block;
            text-align: left;
            margin-left: 15px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .right-panel input,
        .right-panel select {
            width: 92%;
            padding: 10px 12px;
            border-radius: 12px;
            border: none;
            margin-bottom: 18px;
            outline: none;
        }

        .add-btn {
            width: 60%;
            padding: 10px 15px;
            border-radius: 15px;
            background: #0F1B65;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
            display: block;
            margin: 10px auto 0 auto;
        }

        .add-btn:hover {
            background: #162897;
        }

        /* Edit buttons */
        .edit-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 10px;
        }

        .delete-btn {
            padding: 10px 25px;
            border-radius: 15px;
            background: #d32f2f;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .delete-btn:hover {
            background: #b71c1c;
        }

        .save-btn {
            padding: 10px 25px;
            border-radius: 15px;
            background: #0F1B65;
            border: none;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .save-btn:hover {
            background: #162897;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>

    <?php include __DIR__ . '/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Employees</h1>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="content-wrapper">

            <!-- LEFT side - Employees List -->
            <div class="items-panel">

                <div class="items-header">
                    <input type="text" class="search-bar" id="searchInput" placeholder="Search">

                    <select class="sort-btn" id="sortSelect">
                        <option value="name-asc">First Name A-Z</option>
                        <option value="name-desc">First Name Z-A</option>
                        <option value="dept-asc">Department A-Z</option>
                    </select>
                </div>

                <div class="scroll-area" id="itemsList">
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                            <div class="inner-card" data-fname="<?php echo htmlspecialchars($emp['employee_fname']); ?>" data-lname="<?php echo htmlspecialchars($emp['employee_lname']); ?>" data-dept="<?php echo htmlspecialchars($emp['department_name'] ?? 'Unassigned'); ?>">
                                <div class="item-details">
                                    <span><b>Name:</b> <?php echo htmlspecialchars($emp['employee_fname'] . ' ' . $emp['employee_lname']); ?></span>
                                    <span><b>Department:</b> <?php echo htmlspecialchars($emp['department_name'] ?? 'Unassigned'); ?></span>
                                </div>
                                <button class="edit-btn" onclick="showEditPanel(<?php echo $emp['employee_id']; ?>, '<?php echo htmlspecialchars($emp['employee_fname']); ?>', '<?php echo htmlspecialchars($emp['employee_lname']); ?>', '<?php echo htmlspecialchars($emp['employee_mname'] ?? ''); ?>', <?php echo $emp['department_id']; ?>)">Edit</button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: rgba(255,255,255,0.7); text-align: center; padding: 20px;">No employees found</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT side - ADD PANEL -->
            <div class="right-panel" id="addPanel">
                <h2>Add an Employee</h2>

                <form method="POST">
                    <label>First Name:</label>
                    <input type="text" name="employee_fname" required>

                    <label>Middle Name:</label>
                    <input type="text" name="employee_mname">

                    <label>Last Name:</label>
                    <input type="text" name="employee_lname" required>

                    <label>Department:</label>
                    <select name="department_id" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" name="action" value="add" class="add-btn">Add Employee</button>
                </form>
            </div>

            <!-- RIGHT SIDE - EDIT PANEL -->
            <div class="right-panel hidden" id="editPanel">
                <h2>Edit Employee</h2>

                <label>First Name:</label>
                <input type="text" id="editFirstName">

                <label>Middle Name:</label>
                <input type="text" id="editMiddleName">

                <label>Last Name:</label>
                <input type="text" id="editLastName">

                <label>Department:</label>
                <select id="editDepartment">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="hidden" id="editItemId">

                <div class="edit-buttons">
                    <button class="delete-btn" onclick="deleteItem()">Delete</button>
                    <button class="save-btn" onclick="saveItem()">Save</button>
                </div>
            </div>

        </div>
    </div>

    <script>
        const sidebar = document.getElementById('sidebar');
        let profileOpen = false;

        function toggleProfile(event) {
            event.preventDefault();
            profileOpen = !profileOpen;

            if (profileOpen) {
                sidebar.classList.add('expanded');
            } else {
                sidebar.classList.remove('expanded');
            }
        }

        document.addEventListener('click', function(event) {
            if (profileOpen && !sidebar.contains(event.target)) {
                sidebar.classList.remove('expanded');
                profileOpen = false;
            }
        });

        function showEditPanel(empId, fname, lname, mname, deptId) {
            document.getElementById('addPanel').classList.add('hidden');
            document.getElementById('editPanel').classList.remove('hidden');

            document.getElementById('editItemId').value = empId;
            document.getElementById('editFirstName').value = fname;
            document.getElementById('editLastName').value = lname;
            document.getElementById('editMiddleName').value = mname;
            document.getElementById('editDepartment').value = deptId;
        }

        function saveItem() {
            const empId = document.getElementById('editItemId').value;
            const fname = document.getElementById('editFirstName').value;
            const lname = document.getElementById('editLastName').value;
            const mname = document.getElementById('editMiddleName').value;
            const deptId = document.getElementById('editDepartment').value;

            if (!fname.trim() || !lname.trim() || !deptId) {
                alert('Please fill in all required fields');
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="employee_id" value="${empId}">
                <input type="hidden" name="employee_fname" value="${fname.replace(/"/g, '&quot;')}">
                <input type="hidden" name="employee_lname" value="${lname.replace(/"/g, '&quot;')}">
                <input type="hidden" name="employee_mname" value="${mname.replace(/"/g, '&quot;')}">
                <input type="hidden" name="department_id" value="${deptId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function deleteItem() {
            const fname = document.getElementById('editFirstName').value;
            const lname = document.getElementById('editLastName').value;
            const empId = document.getElementById('editItemId').value;

            if (confirm(`Delete ${fname} ${lname}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="employee_id" value="${empId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.inner-card');
            
            cards.forEach(card => {
                const fname = card.dataset.fname.toLowerCase();
                const lname = card.dataset.lname.toLowerCase();
                const dept = card.dataset.dept.toLowerCase();
                
                if (fname.includes(searchTerm) || lname.includes(searchTerm) || dept.includes(searchTerm)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Sort functionality
        document.getElementById('sortSelect').addEventListener('change', function(e) {
            const sortBy = e.target.value;
            const container = document.getElementById('itemsList');
            const cards = Array.from(container.querySelectorAll('.inner-card'));
            
            cards.sort((a, b) => {
                const fnameA = a.dataset.fname.toLowerCase();
                const fnameB = b.dataset.fname.toLowerCase();
                const deptA = a.dataset.dept.toLowerCase();
                const deptB = b.dataset.dept.toLowerCase();
                
                switch(sortBy) {
                    case 'name-asc':
                        return fnameA.localeCompare(fnameB);
                    case 'name-desc':
                        return fnameB.localeCompare(fnameA);
                    case 'dept-asc':
                        return deptA.localeCompare(deptB);
                    default:
                        return 0;
                }
            });
            cards.forEach(card => container.appendChild(card));
        });
    </script>

</body>
</html>
