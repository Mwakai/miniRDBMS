/**
 * miniRDBMS Web Interface JavaScript
 */

const API_URL = "api.php";

// Example queries
const EXAMPLE_QUERIES = {
  "select-users": "SELECT * FROM users",
  "select-tasks": "SELECT * FROM tasks",
  "select-pending": "SELECT * FROM tasks WHERE completed = 0 ORDER BY due_date",
  "select-join":
    "SELECT u.username, t.title, t.status, t.due_date\nFROM users u\nINNER JOIN tasks t ON u.id = t.user_id\nWHERE t.completed = 0",
  "insert-user":
    "INSERT INTO users (username, email, created_at)\nVALUES ('john_doe', 'john@example.com', '2026-01-15 10:00:00')",
  "insert-task":
    "INSERT INTO tasks (user_id, title, description, status, due_date, completed, created_at)\nVALUES (1, 'Complete project', 'Finish the miniRDBMS project', 'pending', '2026-01-20', 0, '2026-01-15 10:00:00')",
  "update-task":
    "UPDATE tasks SET completed = 1, status = 'completed' WHERE id = 1",
  "delete-task": "DELETE FROM tasks WHERE completed = 1",
};

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  refreshTables();
  loadUsers();
});

/**
 * Execute SQL query via API
 */
async function executeQuery() {
  const sql = document.getElementById("sql-input").value.trim();
  const statusEl = document.getElementById("query-status");
  const resultsEl = document.getElementById("results-container");

  if (!sql) {
    alert("Please enter a SQL query");
    return;
  }

  statusEl.textContent = "Executing...";
  statusEl.className = "status-text status-loading";

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sql }),
    });

    const result = await response.json();

    if (result.success) {
      statusEl.textContent = `Success (${result.durationMs}ms)`;
      statusEl.className = "status-text status-success";
      displayResults(result);

      // Refresh relevant data
      if (sql.toLowerCase().includes("users")) {
        loadUsers();
      }
      if (
        sql.toLowerCase().includes("create") ||
        sql.toLowerCase().includes("drop")
      ) {
        refreshTables();
      }
    } else {
      statusEl.textContent = `Error: ${result.error}`;
      statusEl.className = "status-text status-error";
      resultsEl.innerHTML = `<div class="error-box">${result.error}</div>`;
    }
  } catch (error) {
    statusEl.textContent = `Network error: ${error.message}`;
    statusEl.className = "status-text status-error";
    resultsEl.innerHTML = `<div class="error-box">Network error: ${error.message}</div>`;
  }
}

/**
 * Display query results
 */
function displayResults(result) {
  const resultsEl = document.getElementById("results-container");

  if (result.type === "SELECT" && result.rows) {
    if (result.rows.length === 0) {
      resultsEl.innerHTML = '<p class="text-muted">Query returned no rows.</p>';
      return;
    }

    const table = createTable(result.rows);
    resultsEl.innerHTML = `
            <p><strong>${result.rowCount} row(s) returned</strong></p>
            ${table}
        `;
  } else {
    resultsEl.innerHTML = `
            <div class="success-box">
                <strong>${result.message || "Success"}</strong>
                ${
                  result.affectedRows !== undefined
                    ? `<br>${result.affectedRows} row(s) affected`
                    : ""
                }
            </div>
        `;
  }
}

/**
 * Create HTML table from rows
 */
function createTable(rows) {
  if (rows.length === 0) return "<p>No data</p>";

  const columns = Object.keys(rows[0]);

  let html = '<table class="results-table"><thead><tr>';
  columns.forEach((col) => {
    html += `<th>${escapeHtml(col)}</th>`;
  });
  html += "</tr></thead><tbody>";

  rows.forEach((row) => {
    html += "<tr>";
    columns.forEach((col) => {
      const value =
        row[col] === null ? "<em>NULL</em>" : escapeHtml(String(row[col]));
      html += `<td>${value}</td>`;
    });
    html += "</tr>";
  });

  html += "</tbody></table>";
  return html;
}

/**
 * Refresh tables list
 */
async function refreshTables() {
  const listEl = document.getElementById("tables-list");
  listEl.innerHTML = '<p class="text-muted">Loading...</p>';

  try {
    const response = await fetch(`${API_URL}?action=tables`);
    const result = await response.json();

    if (result.success && result.tables.length > 0) {
      let html = '<ul class="table-list">';
      result.tables.forEach((table) => {
        html += `<li onclick="showTableSchema('${table}')">${table}</li>`;
      });
      html += "</ul>";
      listEl.innerHTML = html;
    } else {
      listEl.innerHTML = '<p class="text-muted">No tables found.</p>';
    }
  } catch (error) {
    listEl.innerHTML = `<p class="error-text">Error: ${error.message}</p>`;
  }
}

/**
 * Show table schema
 */
async function showTableSchema(tableName) {
  try {
    const response = await fetch(`${API_URL}?action=schema&table=${tableName}`);
    const result = await response.json();

    if (result.success) {
      const schema = result.schema;
      let info = `Table: ${schema.name}\n\nColumns:\n`;

      for (const [colName, colDef] of Object.entries(schema.columns)) {
        let type = colDef.type;
        if (type === "VARCHAR" && colDef.length) {
          type += `(${colDef.length})`;
        }

        const constraints = [];
        if (colDef.primaryKey) constraints.push("PK");
        if (colDef.unique) constraints.push("UNIQUE");
        if (!colDef.nullable) constraints.push("NOT NULL");
        if (colDef.autoIncrement) constraints.push("AUTO_INCREMENT");

        info += `  ${colName}: ${type}`;
        if (constraints.length) {
          info += ` [${constraints.join(", ")}]`;
        }
        info += "\n";
      }

      alert(info);
    }
  } catch (error) {
    alert(`Error loading schema: ${error.message}`);
  }
}

/**
 * Load example query
 */
function loadExampleQuery(key) {
  if (key && EXAMPLE_QUERIES[key]) {
    document.getElementById("sql-input").value = EXAMPLE_QUERIES[key];
  }
}

/**
 * Clear query input
 */
function clearQuery() {
  document.getElementById("sql-input").value = "";
  document.getElementById("query-status").textContent = "";
  document.getElementById("results-container").innerHTML =
    '<p class="text-muted">Execute a query to see results here...</p>';
}

/**
 * Switch tabs
 */
function switchTab(tabName) {
  // Hide all tabs
  document.querySelectorAll(".tab-content").forEach((tab) => {
    tab.classList.remove("active");
  });

  // Deactivate all buttons
  document.querySelectorAll(".tab-button").forEach((btn) => {
    btn.classList.remove("active");
  });

  // Show selected tab
  document.getElementById(`tab-${tabName}`).classList.add("active");

  // Activate button
  event.target.classList.add("active");
}

/**
 * Load setup script
 */
async function loadSetup() {
  if (!confirm("This will create sample tables and data. Continue?")) {
    return;
  }

  try {
    const response = await fetch("setup.php");
    const text = await response.text();
    alert(text);
    refreshTables();
    loadUsers();
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

/**
 * Load users for dropdown and display
 */
async function loadUsers() {
  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sql: "SELECT * FROM users" }),
    });

    const result = await response.json();

    if (result.success && result.rows) {
      // Update dropdown
      const selectEl = document.getElementById("task-user");
      selectEl.innerHTML = '<option value="">-- Select User --</option>';
      result.rows.forEach((user) => {
        selectEl.innerHTML += `<option value="${user.id}">${user.username}</option>`;
      });

      // Update users list
      const listEl = document.getElementById("users-list");
      if (result.rows.length === 0) {
        listEl.innerHTML = '<p class="text-muted">No users found.</p>';
      } else {
        let html = '<table class="results-table">';
        html += `<thead>
                    <tr>
                        <th>id</th>
                        <th>username</th>
                        <th>email</th>
                        <th>created At</th>
                        </tr>
                    </thead>
                    <tbody>`;
        result.rows.forEach((user) => {
          html += `
                        <tr>
                            <td>${user.id}</td>
                            <td>${escapeHtml(user.username)}</td>
                            <td>${escapeHtml(user.email)}</td>
                            <td>${user.created_at || "-"}</td>
                        </tr>
                    `;
        });
        html += "</tbody></table>";
        listEl.innerHTML = html;
      }
    }
  } catch (error) {
    console.error("Error loading users:", error);
  }
}

/**
 * Add new user
 */
async function addUser(event) {
  event.preventDefault();

  const username = document.getElementById("user-username").value;
  const email = document.getElementById("user-email").value;
  const created_at = new Date().toISOString().slice(0, 19).replace("T", " ");

  const sql = `INSERT INTO users (username, email, created_at) VALUES ('${username}', '${email}', '${created_at}')`;

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sql }),
    });

    const result = await response.json();

    if (result.success) {
      alert("User added successfully!");
      event.target.reset();
      loadUsers();
    } else {
      alert(`Error: ${result.error}`);
    }
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

/**
 * Add new task
 */
async function addTask(event) {
  event.preventDefault();

  const user_id = document.getElementById("task-user").value;
  const title = document.getElementById("task-title").value;
  const description = document.getElementById("task-description").value;
  const due_date = document.getElementById("task-due-date").value;
  const status = document.getElementById("task-status").value;
  const created_at = new Date().toISOString().slice(0, 19).replace("T", " ");

  const sql = `INSERT INTO tasks (user_id, title, description, status, due_date, completed, created_at) VALUES (${user_id}, '${title}', '${description}', '${status}', '${due_date}', 0, '${created_at}')`;

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sql }),
    });

    const result = await response.json();

    if (result.success) {
      alert("Task added successfully!");
      event.target.reset();
    } else {
      alert(`Error: ${result.error}`);
    }
  } catch (error) {
    alert(`Error: ${error.message}`);
  }
}

/**
 * Load tasks
 */
async function loadTasks(filter) {
  let sql =
    "SELECT t.id, u.username, t.title, t.description, t.status, t.due_date, t.completed FROM tasks t INNER JOIN users u ON t.user_id = u.id";

  if (filter === "pending") {
    sql += " WHERE t.completed = 0";
  } else if (filter === "completed") {
    sql += " WHERE t.completed = 1";
  }

  sql += " ORDER BY t.due_date";

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sql }),
    });

    const result = await response.json();
    const listEl = document.getElementById("tasks-list");

    if (result.success && result.rows) {
      if (result.rows.length === 0) {
        listEl.innerHTML = '<p class="text-muted">No tasks found.</p>';
      } else {
        let html = '<table class="results-table">';
        html += `<thead>
                    <tr>
                        <th>id</th>
                        <th>username</th>
                        <th>title</th>
                        <th>description</th>
                        <th>status</th>
                        <th>due_date</th>
                        <th>completed</th>
                    </tr>
                </thead>
                <tbody>`;
        result.rows.forEach((task) => {
          html += `
                    <tr>
                        <td>${task["t.id"]}</td>
                        <td>${escapeHtml(task["u.username"])}</td>
                        <td>${escapeHtml(task["t.title"])}</td>
                        <td>${escapeHtml(task["t.description"] || "")}</td>
                        <td>${escapeHtml(task["t.status"])}</td>
                        <td>${task["t.due_date"] || "-"}</td>
                        <td>${task["t.completed"] ? "Yes" : "No"}</td>
                    </tr>
                `;
        });
        html += "</tbody></table>";
        listEl.innerHTML = html;
      }
    } else {
      listEl.innerHTML = `<p class="error-text">Error: ${result.error}</p>`;
    }
  } catch (error) {
    listEl.innerHTML = `<p class="error-text">Error: ${error.message}</p>`;
  }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return String(text).replace(/[&<>"']/g, (m) => map[m]);
}
