<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>miniRDBMS - Task Management Demo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>RDBMS</h1>
            <p class="subtitle">Task Management System Demo</p>
        </header>

        <div class="content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-section">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions">
                        <button onclick="loadSetup()" class="btn btn-primary">Initialize Database</button>
                        <button onclick="refreshTables()" class="btn btn-secondary">Refresh Tables</button>
                    </div>

                </div>

                <div class="sidebar-section">
                    <h3>Tables</h3>
                    <div id="tables-list">
                        <p class="text-muted">Loading tables...</p>
                    </div>
                </div>

                <div class="sidebar-section">
                    <h3>Example Queries</h3>
                    <select id="example-queries" onchange="loadExampleQuery(this.value)" class="select-full">
                        <option value="">-- Select Example --</option>
                        <option value="select-users">List all users</option>
                        <option value="select-tasks">List all tasks</option>
                        <option value="select-pending">Pending tasks</option>
                        <option value="select-join">Users with tasks (JOIN)</option>
                        <option value="insert-user">Insert new user</option>
                        <option value="insert-task">Insert new task</option>
                        <option value="update-task">Complete a task</option>
                        <option value="delete-task">Delete completed tasks</option>
                    </select>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- SQL Query Editor -->
                <section class="card">
                    <h2>SQL Query Editor</h2>
                    <textarea id="sql-input" placeholder="Enter your SQL query here...&#10;eg: SELECT * FROM users;"
                        rows="6"></textarea>
                    <div class="button-group">
                        <button onclick="executeQuery()" class="btn btn-primary">Execute Query</button>
                        <button onclick="clearQuery()" class="btn btn-secondary">Clear</button>
                        <span id="query-status" class="status-text"></span>
                    </div>
                </section>

                <!-- Query Results -->
                <section class="card">
                    <h2>Results</h2>
                    <div id="results-container">
                        <p class="text-muted">Execute a query to see results here...</p>
                    </div>
                </section>

                <!-- Task Management Interface -->
                <section class="card">
                    <h2>Task Management</h2>

                    <div class="tabs">
                        <button class="tab-button active" onclick="switchTab('users')">Manage Users</button>
                        <button class="tab-button" onclick="switchTab('tasks')">Manage Tasks</button>
                        <button class="tab-button" onclick="switchTab('view')">View Tasks</button>
                    </div>

                    <!-- Users Tab -->
                    <div id="tab-users" class="tab-content active">
                        <h3>Add New User</h3>
                        <form onsubmit="addUser(event)" class="form">
                            <div class="form-group">
                                <label>Username:</label>
                                <input type="text" id="user-username" required>
                            </div>
                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" id="user-email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Add User</button>
                        </form>

                        <h3>Current Users</h3>
                        <div id="users-list" class="data-list">
                            <p class="text-muted">No users found. Initialize the database first.</p>
                        </div>
                    </div>

                    <!-- Tasks Tab -->
                    <div id="tab-tasks" class="tab-content">
                        <h3>Add New Task</h3>
                        <form onsubmit="addTask(event)" class="form">
                            <div class="form-group">
                                <label>User:</label>
                                <select id="task-user" required>
                                    <option value="">-- Select User --</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Title:</label>
                                <input type="text" id="task-title" required>
                            </div>
                            <div class="form-group">
                                <label>Description:</label>
                                <textarea id="task-description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Due Date:</label>
                                <input type="date" id="task-due-date">
                            </div>
                            <div class="form-group">
                                <label>Status:</label>
                                <select id="task-status">
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Task</button>
                        </form>
                    </div>

                    <!-- View Tab -->
                    <div id="tab-view" class="tab-content">
                        <div class="button-group">
                            <button onclick="loadTasks('all')" class="btn btn-secondary">All Tasks</button>
                            <button onclick="loadTasks('pending')" class="btn btn-secondary">Pending</button>
                            <button onclick="loadTasks('completed')" class="btn btn-secondary">Completed</button>
                        </div>

                        <div id="tasks-list" class="data-list">
                            <p class="text-muted">Click a button to load tasks.</p>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <footer>
            <p>&copy; 2026 miniRDBMS - A Simple PHP-Based Relational Database Management System</p>
        </footer>
    </div>

    <script src="assets/js/app.js"></script>
</body>

</html>