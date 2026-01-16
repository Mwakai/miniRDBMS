<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation - miniRDBMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/docs.css">
</head>

<body>
    <div class="container">
        <header>
            <h1>miniRDBMS</h1>
            <p class="subtitle">Documentation</p>
        </header>

        <div class="content docs-layout">
            <!-- Sidebar Navigation -->
            <aside class="sidebar docs-sidebar">
                <div class="sidebar-section">
                    <h3>Navigation</h3>
                    <ul class="docs-nav">
                        <li><a href="#overview">Overview</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#quick-start">Quick Start</a></li>
                        <li><a href="#project-structure">Project Structure</a></li>
                        <li><a href="#sql-syntax">SQL Syntax</a></li>
                        <li><a href="#rest-api">REST API</a></li>
                        <li><a href="#example">Example App</a></li>
                        <li><a href="#architecture">Architecture</a></li>
                        <li><a href="#limitations">Limitations</a></li>
                        <li><a href="#requirements">Requirements</a></li>
                    </ul>
                </div>
                <div class="sidebar-section">
                    <h3>Quick Links</h3>
                    <div class="quick-actions">
                        <a href="index.php" class="btn btn-primary">Back to App</a>
                    </div>
                </div>
            </aside>

            <!-- Main Documentation Content -->
            <main class="main-content docs-content">
                <!-- Overview -->
                <section class="card" id="overview">
                    <h2>miniRDBMS - Simple Relational Database Management System</h2>
                    <div class="card-body">
                        <p>A lightweight, file-based relational database management system built in PHP with SQL-like querying, demonstrating core database concepts.</p>
                    </div>
                </section>

                <!-- Features -->
                <section class="card" id="features">
                    <h2>Features</h2>
                    <div class="card-body">
                        <h3>Core Functionality</h3>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>SQL-like Interface</strong></td>
                                    <td>Execute SQL commands with familiar syntax</td>
                                </tr>
                                <tr>
                                    <td><strong>Data Types</strong></td>
                                    <td>INT, VARCHAR, TEXT, DATE, DATETIME, BOOLEAN</td>
                                </tr>
                                <tr>
                                    <td><strong>CRUD Operations</strong></td>
                                    <td>CREATE, SELECT, INSERT, UPDATE, DELETE</td>
                                </tr>
                                <tr>
                                    <td><strong>Table Management</strong></td>
                                    <td>CREATE TABLE, DROP TABLE with schema definitions</td>
                                </tr>
                                <tr>
                                    <td><strong>Constraints</strong></td>
                                    <td>PRIMARY KEY, UNIQUE, NOT NULL, AUTO_INCREMENT</td>
                                </tr>
                                <tr>
                                    <td><strong>Indexing</strong></td>
                                    <td>Automatic indexing on primary and unique keys</td>
                                </tr>
                                <tr>
                                    <td><strong>Joins</strong></td>
                                    <td>INNER JOIN support for multi-table queries</td>
                                </tr>
                                <tr>
                                    <td><strong>Filtering</strong></td>
                                    <td>WHERE clauses with operators: =, !=, <, >, <=, >=, LIKE</td>
                                </tr>
                                <tr>
                                    <td><strong>Sorting & Limiting</strong></td>
                                    <td>ORDER BY (ASC/DESC) and LIMIT clauses</td>
                                </tr>
                                <tr>
                                    <td><strong>JSON Storage</strong></td>
                                    <td>Human-readable file-based persistence</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Interfaces</h3>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Interface</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><strong>Interactive REPL</strong></td>
                                    <td>Command-line interface for SQL queries</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><strong>REST API</strong></td>
                                    <td>JSON API for programmatic access</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><strong>Web Interface</strong></td>
                                    <td>Task management demo application</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Quick Start -->
                <section class="card" id="quick-start">
                    <h2>Quick Start</h2>
                    <div class="card-body">
                        <h3>1. Test the RDBMS</h3>
                        <p>Run the test suite to verify installation:</p>
                        <pre class="code-block"><code>php test.php</code></pre>
                        <p>Expected output: <span class="success-text">All 16 tests passed!</span></p>

                        <h3>2. Interactive REPL</h3>
                        <p>Start the interactive SQL shell:</p>
                        <pre class="code-block"><code>php repl.php</code></pre>

                        <p>Try these commands:</p>
                        <pre class="code-block"><code class="sql">-- Create a table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100)
);

-- Insert data
INSERT INTO users (username, email)
VALUES ('alice', 'alice@example.com');

-- Query data
SELECT * FROM users;

-- List all tables
.tables

-- Show table schema
.schema users

-- Exit
.exit</code></pre>

                        <h3>3. Web Interface</h3>
                        <p>1. Start your web server (XAMPP, WAMP, or built-in):</p>
                        <pre class="code-block"><code>php -S localhost:8000 -t web</code></pre>

                        <p>2. Open browser: <code class="inline-code">http://localhost:8000</code></p>
                        <p>3. Click "Initialize Database" to set up sample tables and data</p>
                        <p>4. Explore the task management system</p>
                    </div>
                </section>

                <!-- Project Structure -->
                <section class="card" id="project-structure">
                    <h2>Project Structure</h2>
                    <div class="card-body">
                        <pre class="code-block"><code>miniRDBMS/
├── bootstrap.php           # Autoloader and initialization
├── repl.php                # Interactive CLI
├── test.php                # Test suite
├── server/
│   ├── Core/
│   │   ├── Config.php      # Configuration management
│   │   ├── Database.php    # Main database orchestrator
│   │   └── Table.php       # Table abstraction
│   ├── Query/
│   │   ├── Tokenizer.php   # SQL lexical analyzer
│   │   ├── Parser.php      # SQL syntax parser
│   │   ├── Executor.php    # Query execution engine
│   │   └── WhereEvaluator.php  # WHERE clause logic
│   ├── Storage/
│   │   ├── StorageEngine.php   # JSON file I/O
│   │   └── tables/         # Data files (JSON)
│   └── Utils/
│       ├── DataType.php    # Type validation
│       └── IndexBuilder.php # Index management
└── web/
    ├── index.php           # Web interface
    ├── api.php             # REST API endpoint
    ├── setup.php           # Database initialization
    └── assets/
        ├── css/style.css   # Styling
        └── js/app.js       # Frontend logic</code></pre>
                    </div>
                </section>

                <!-- SQL Syntax -->
                <section class="card" id="sql-syntax">
                    <h2>Supported SQL Syntax</h2>
                    <div class="card-body">
                        <h3>CREATE TABLE</h3>
                        <pre class="code-block"><code class="sql">CREATE TABLE table_name (
    column_name TYPE [constraints],
    ...
);</code></pre>
                        <p><strong>Data Types:</strong> <code class="inline-code">INT</code>, <code class="inline-code">VARCHAR(n)</code>, <code class="inline-code">TEXT</code>, <code class="inline-code">DATE</code>, <code class="inline-code">DATETIME</code>, <code class="inline-code">BOOLEAN</code></p>
                        <p><strong>Constraints:</strong> <code class="inline-code">PRIMARY KEY</code>, <code class="inline-code">UNIQUE</code>, <code class="inline-code">NOT NULL</code>, <code class="inline-code">AUTO_INCREMENT</code>, <code class="inline-code">DEFAULT value</code></p>

                        <h3>INSERT</h3>
                        <pre class="code-block"><code class="sql">-- Single row
INSERT INTO table_name (col1, col2) VALUES (val1, val2);

-- Multiple rows
INSERT INTO table_name (col1, col2)
VALUES (val1, val2), (val3, val4);</code></pre>

                        <h3>SELECT</h3>
                        <pre class="code-block"><code class="sql">-- Basic select
SELECT * FROM table_name;
SELECT col1, col2 FROM table_name;

-- With WHERE
SELECT * FROM table_name WHERE column = value;
SELECT * FROM table_name WHERE age > 18 AND status = 'active';

-- With JOIN
SELECT u.username, t.title
FROM users u
INNER JOIN tasks t ON u.id = t.user_id;

-- With ORDER BY and LIMIT
SELECT * FROM table_name ORDER BY column DESC LIMIT 10;</code></pre>

                        <h3>UPDATE</h3>
                        <pre class="code-block"><code class="sql">UPDATE table_name
SET col1 = val1, col2 = val2
WHERE condition;</code></pre>

                        <h3>DELETE</h3>
                        <pre class="code-block"><code class="sql">DELETE FROM table_name WHERE condition;</code></pre>

                        <h3>DROP TABLE</h3>
                        <pre class="code-block"><code class="sql">DROP TABLE table_name;</code></pre>
                    </div>
                </section>

                <!-- REST API -->
                <section class="card" id="rest-api">
                    <h2>REST API</h2>
                    <div class="card-body">
                        <p>Base URL: <code class="inline-code">http://localhost:8000/api.php</code></p>

                        <h3>Execute SQL Query</h3>
                        <pre class="code-block"><code>POST /api.php
Content-Type: application/json

{
  "sql": "SELECT * FROM users"
}</code></pre>

                        <p><strong>Response:</strong></p>
                        <pre class="code-block"><code class="json">{
  "success": true,
  "type": "SELECT",
  "rows": [...],
  "rowCount": 3,
  "executionTime": 0.0042,
  "durationMs": 4.2
}</code></pre>

                        <h3>List Tables</h3>
                        <pre class="code-block"><code>GET /api.php?action=tables</code></pre>

                        <h3>Get Table Schema</h3>
                        <pre class="code-block"><code>GET /api.php?action=schema&table=users</code></pre>
                    </div>
                </section>

                <!-- Example App -->
                <section class="card" id="example">
                    <h2>Example: Task Management System</h2>
                    <div class="card-body">
                        <p>The included web demo demonstrates a task management system:</p>

                        <h3>Users Table</h3>
                        <table class="results-table schema-table">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Constraints</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>id</strong></td>
                                    <td>INT</td>
                                    <td><span class="key-badge key-pri">PRI</span> AUTO_INCREMENT</td>
                                </tr>
                                <tr>
                                    <td><strong>username</strong></td>
                                    <td>VARCHAR(50)</td>
                                    <td><span class="key-badge key-uni">UNI</span> NOT NULL</td>
                                </tr>
                                <tr>
                                    <td><strong>email</strong></td>
                                    <td>VARCHAR(100)</td>
                                    <td>NOT NULL</td>
                                </tr>
                                <tr>
                                    <td><strong>created_at</strong></td>
                                    <td>DATETIME</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Tasks Table</h3>
                        <table class="results-table schema-table">
                            <thead>
                                <tr>
                                    <th>Column</th>
                                    <th>Type</th>
                                    <th>Constraints</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>id</strong></td>
                                    <td>INT</td>
                                    <td><span class="key-badge key-pri">PRI</span> AUTO_INCREMENT</td>
                                </tr>
                                <tr>
                                    <td><strong>user_id</strong></td>
                                    <td>INT</td>
                                    <td>NOT NULL</td>
                                </tr>
                                <tr>
                                    <td><strong>title</strong></td>
                                    <td>VARCHAR(200)</td>
                                    <td>NOT NULL</td>
                                </tr>
                                <tr>
                                    <td><strong>description</strong></td>
                                    <td>TEXT</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><strong>status</strong></td>
                                    <td>VARCHAR(20)</td>
                                    <td>DEFAULT 'pending'</td>
                                </tr>
                                <tr>
                                    <td><strong>due_date</strong></td>
                                    <td>DATE</td>
                                    <td></td>
                                </tr>
                                <tr>
                                    <td><strong>completed</strong></td>
                                    <td>BOOLEAN</td>
                                    <td>DEFAULT 0</td>
                                </tr>
                                <tr>
                                    <td><strong>created_at</strong></td>
                                    <td>DATETIME</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Example Queries</h3>
                        <pre class="code-block"><code class="sql">-- Get all pending tasks with user information
SELECT u.username, t.title, t.due_date
FROM users u
INNER JOIN tasks t ON u.id = t.user_id
WHERE t.completed = 0
ORDER BY t.due_date;

-- Mark task as completed
UPDATE tasks SET completed = 1, status = 'completed' WHERE id = 5;

-- Delete completed tasks
DELETE FROM tasks WHERE completed = 1;</code></pre>
                    </div>
                </section>

                <!-- Architecture -->
                <section class="card" id="architecture">
                    <h2>Architecture</h2>
                    <div class="card-body">
                        <h3>Three-Tier Design</h3>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Layer</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>1</td>
                                    <td><strong>Entry Layer</strong></td>
                                    <td>Database.php receives SQL queries</td>
                                </tr>
                                <tr>
                                    <td>2</td>
                                    <td><strong>Processing Layer</strong></td>
                                    <td>Tokenizer → Parser → Executor</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td><strong>Storage Layer</strong></td>
                                    <td>StorageEngine handles JSON file I/O</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Data Flow</h3>
                        <div class="flow-diagram">
                            <div class="flow-item">SQL Query</div>
                            <div class="flow-arrow">↓</div>
                            <div class="flow-item">Tokenizer <span class="flow-desc">(breaks into tokens)</span></div>
                            <div class="flow-arrow">↓</div>
                            <div class="flow-item">Parser <span class="flow-desc">(creates structured array)</span></div>
                            <div class="flow-arrow">↓</div>
                            <div class="flow-item">Executor <span class="flow-desc">(routes to specific handler)</span></div>
                            <div class="flow-arrow">↓</div>
                            <div class="flow-item">StorageEngine <span class="flow-desc">(reads/writes JSON files)</span></div>
                            <div class="flow-arrow">↓</div>
                            <div class="flow-item">Result</div>
                        </div>

                        <h3>Storage Format</h3>
                        <p>Each table has three files:</p>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>File</th>
                                    <th>Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code class="inline-code">tablename.json</code></td>
                                    <td>Row data</td>
                                </tr>
                                <tr>
                                    <td><code class="inline-code">tablename_schema.json</code></td>
                                    <td>Column definitions</td>
                                </tr>
                                <tr>
                                    <td><code class="inline-code">tablename_index.json</code></td>
                                    <td>Index mappings</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Limitations -->
                <section class="card" id="limitations">
                    <h2>Limitations</h2>
                    <div class="card-body">
                        <p>This is an educational RDBMS with intentional simplifications:</p>
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Limitation</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>No transactions</td><td>Auto-commit only</td></tr>
                                <tr><td>No aggregate functions</td><td>COUNT, SUM, AVG not supported</td></tr>
                                <tr><td>No GROUP BY / HAVING</td><td>Grouping not implemented</td></tr>
                                <tr><td>No subqueries</td><td>Nested queries not supported</td></tr>
                                <tr><td>No LEFT/RIGHT JOIN</td><td>INNER JOIN only</td></tr>
                                <tr><td>No ALTER TABLE</td><td>Schema modifications not supported</td></tr>
                                <tr><td>Single-threaded</td><td>No concurrent writes</td></tr>
                                <tr><td>Memory loaded</td><td>Entire table loaded into memory</td></tr>
                                <tr><td>Nested loop JOIN</td><td>O(n×m) complexity</td></tr>
                                <tr><td>No query optimization</td><td>Basic execution only</td></tr>
                                <tr><td>No foreign keys</td><td>Referential integrity not enforced</td></tr>
                                <tr><td>No views/procedures</td><td>Not implemented</td></tr>
                            </tbody>
                        </table>

                        <h3>Performance Considerations</h3>
                        <div class="info-box">
                            <strong>Designed for:</strong>
                            <ul>
                                <li>Small to medium datasets (<10,000 rows per table)</li>
                                <li>Educational and demonstration purposes</li>
                                <li>Low-concurrency scenarios</li>
                                <li>Simple relational queries</li>
                            </ul>
                        </div>
                        <div class="error-box">
                            <strong>Not suitable for:</strong>
                            <ul>
                                <li>Production applications</li>
                                <li>Large datasets</li>
                                <li>High-concurrency environments</li>
                                <li>Performance-critical systems</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Requirements -->
                <section class="card" id="requirements">
                    <h2>Requirements</h2>
                    <div class="card-body">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>Requirement</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>PHP Version</strong></td>
                                    <td>8.0 or higher</td>
                                </tr>
                                <tr>
                                    <td><strong>File Permissions</strong></td>
                                    <td>Write permissions for storage directory</td>
                                </tr>
                                <tr>
                                    <td><strong>Web Server</strong></td>
                                    <td>Apache/Nginx or PHP built-in server (for web interface)</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3>Test Results</h3>
                        <div class="success-box">
                            <strong>All core features tested and verified:</strong>
                            <ul>
                                <li>✓ CREATE TABLE with various data types and constraints</li>
                                <li>✓ INSERT single and multiple rows</li>
                                <li>✓ SELECT with * and specific columns</li>
                                <li>✓ WHERE clauses with comparison operators</li>
                                <li>✓ INNER JOIN between tables</li>
                                <li>✓ ORDER BY ascending and descending</li>
                                <li>✓ LIMIT clause</li>
                                <li>✓ UPDATE with WHERE conditions</li>
                                <li>✓ DELETE with WHERE conditions</li>
                                <li>✓ DROP TABLE</li>
                                <li>✓ Primary key and unique constraints</li>
                                <li>✓ Auto-increment functionality</li>
                            </ul>
                        </div>
                    </div>
                </section>

            </main>
        </div>

        <footer>
            <p>miniRDBMS v1.0.0 · PHP-Based Relational Database Management System</p>
            <div class="footer-links">
                <a href="index.php">Back to App</a>
            </div>
        </footer>
    </div>
</body>

</html>
