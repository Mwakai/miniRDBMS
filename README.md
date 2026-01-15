# miniRDBMS - Simple Relational Database Management System

A lightweight, file-based relational database management system built in PHP with SQL-like querying, demonstrating core database concepts.

## Features

### Core Functionality
- **SQL-like Interface** - Execute SQL commands with familiar syntax
- **Data Types** - INT, VARCHAR, TEXT, DATE, DATETIME, BOOLEAN
- **CRUD Operations** - CREATE, SELECT, INSERT, UPDATE, DELETE
- **Table Management** - CREATE TABLE, DROP TABLE with schema definitions
- **Constraints** - PRIMARY KEY, UNIQUE, NOT NULL, AUTO_INCREMENT
- **Indexing** - Automatic indexing on primary and unique keys
- **Joins** - INNER JOIN support for multi-table queries
- **Filtering** - WHERE clauses with operators: =, !=, <, >, <=, >=, LIKE
- **Sorting & Limiting** - ORDER BY (ASC/DESC) and LIMIT clauses
- **JSON Storage** - Human-readable file-based persistence

### Interfaces
1. **Interactive REPL** - Command-line interface for SQL queries
2. **REST API** - JSON API for programmatic access
3. **Web Interface** - Task management demo application

## Quick Start

### 1. Test the RDBMS

Run the test suite to verify installation:

```bash
php test.php
```

Expected output: All 16 tests passed!

### 2. Interactive REPL

Start the interactive SQL shell:

```bash
php repl.php
```

Try these commands:

```sql
-- Create a table
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
.exit
```

### 3. Web Interface

1. Start your web server (XAMPP, WAMP, or built-in):
   ```bash
   php -S localhost:8000 -t web
   ```

2. Open browser: `http://localhost:8000`

3. Click "Initialize Database" to set up sample tables and data

4. Explore the task management system:
   - Execute SQL queries
   - Manage users and tasks
   - View JOIN query results

## Project Structure

```
miniRDBMS/
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
        └── js/app.js       # Frontend logic
```

## Supported SQL Syntax

### CREATE TABLE

```sql
CREATE TABLE table_name (
    column_name TYPE [constraints],
    ...
);
```

**Data Types:** INT, VARCHAR(n), TEXT, DATE, DATETIME, BOOLEAN

**Constraints:** PRIMARY KEY, UNIQUE, NOT NULL, AUTO_INCREMENT, DEFAULT value

### INSERT

```sql
-- Single row
INSERT INTO table_name (col1, col2) VALUES (val1, val2);

-- Multiple rows
INSERT INTO table_name (col1, col2)
VALUES (val1, val2), (val3, val4);
```

### SELECT

```sql
-- Basic select
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
SELECT * FROM table_name ORDER BY column DESC LIMIT 10;
```

### UPDATE

```sql
UPDATE table_name
SET col1 = val1, col2 = val2
WHERE condition;
```

### DELETE

```sql
DELETE FROM table_name WHERE condition;
```

### DROP TABLE

```sql
DROP TABLE table_name;
```

## REST API

Base URL: `http://localhost:8000/api.php`

### Execute SQL Query

```bash
POST /api.php
Content-Type: application/json

{
  "sql": "SELECT * FROM users"
}
```

Response:
```json
{
  "success": true,
  "type": "SELECT",
  "rows": [...],
  "rowCount": 3,
  "executionTime": 0.0042,
  "durationMs": 4.2
}
```

### List Tables

```bash
GET /api.php?action=tables
```

### Get Table Schema

```bash
GET /api.php?action=schema&table=users
```

## Example: Task Management System

The included web demo demonstrates a task management system:

**Users Table:**
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- username (VARCHAR(50), UNIQUE, NOT NULL)
- email (VARCHAR(100), NOT NULL)
- created_at (DATETIME)

**Tasks Table:**
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, NOT NULL)
- title (VARCHAR(200), NOT NULL)
- description (TEXT)
- status (VARCHAR(20), DEFAULT 'pending')
- due_date (DATE)
- completed (BOOLEAN, DEFAULT 0)
- created_at (DATETIME)

**Example Queries:**

```sql
-- Get all pending tasks with user information
SELECT u.username, t.title, t.due_date
FROM users u
INNER JOIN tasks t ON u.id = t.user_id
WHERE t.completed = 0
ORDER BY t.due_date;

-- Mark task as completed
UPDATE tasks SET completed = 1, status = 'completed' WHERE id = 5;

-- Delete completed tasks
DELETE FROM tasks WHERE completed = 1;
```

## Architecture

### Three-Tier Design

1. **Entry Layer** - Database.php receives SQL queries
2. **Processing Layer** - Tokenizer → Parser → Executor
3. **Storage Layer** - StorageEngine handles JSON file I/O

### Data Flow

```
SQL Query
    ↓
Tokenizer (breaks into tokens)
    ↓
Parser (creates structured array)
    ↓
Executor (routes to specific handler)
    ↓
StorageEngine (reads/writes JSON files)
    ↓
Result
```

### Storage Format

Each table has three files:

- `tablename.json` - Row data
- `tablename_schema.json` - Column definitions
- `tablename_index.json` - Index mappings

## Limitations

This is an educational RDBMS with intentional simplifications:

- No transactions (auto-commit only)
- No aggregate functions (COUNT, SUM, AVG)
- No GROUP BY / HAVING
- No subqueries
- No LEFT/RIGHT JOIN (INNER only)
- No ALTER TABLE
- Single-threaded (no concurrent writes)
- Entire table loaded into memory
- Nested loop JOIN (O(n×m) complexity)
- No query optimization
- No foreign key constraints
- No views or stored procedures

## Performance Considerations

Designed for:
- Small to medium datasets (<10,000 rows per table)
- Educational and demonstration purposes
- Low-concurrency scenarios
- Simple relational queries

Not suitable for:
- Production applications
- Large datasets
- High-concurrency environments
- Performance-critical systems

## Requirements

- PHP 8.0 or higher
- File write permissions for storage directory
- Web server (for web interface): Apache/Nginx or PHP built-in server

## Test Results

All core features tested and verified:

- ✓ CREATE TABLE with various data types and constraints
- ✓ INSERT single and multiple rows
- ✓ SELECT with * and specific columns
- ✓ WHERE clauses with comparison operators
- ✓ INNER JOIN between tables
- ✓ ORDER BY ascending and descending
- ✓ LIMIT clause
- ✓ UPDATE with WHERE conditions
- ✓ DELETE with WHERE conditions
- ✓ DROP TABLE
- ✓ Primary key and unique constraints
- ✓ Auto-increment functionality

## License

This is an educational project created for learning purposes.

## Credits

Built to demonstrate fundamental database concepts including:
- SQL parsing and execution
- File-based storage
- Indexing strategies
- Query optimization basics
- Relational data modeling
