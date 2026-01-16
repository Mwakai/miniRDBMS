/**
 * miniRDBMS Web Interface JavaScript
 */

const API_URL = "api.php";

// CodeMirror editor instance
let sqlEditor = null;

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
  initSqlEditor();
  refreshTables();
  loadUsers();
});

/**
 * Initialize CodeMirror SQL editor
 */
function initSqlEditor() {
  const textarea = document.getElementById("sql-input");

  sqlEditor = CodeMirror.fromTextArea(textarea, {
    mode: "text/x-mysql",
    theme: "default",
    lineNumbers: true,
    lineWrapping: true,
    indentWithTabs: true,
    smartIndent: true,
    tabSize: 4,
    indentUnit: 4,
    autofocus: false,
    matchBrackets: true,
    autoCloseBrackets: true,
    placeholder: "Enter your SQL query here...\neg: SELECT * FROM users;",
    extraKeys: {
      "Ctrl-Enter": function(cm) {
        executeQuery();
      },
      "Cmd-Enter": function(cm) {
        executeQuery();
      }
    }
  });

  // Set initial height
  sqlEditor.setSize(null, "auto");
}

/**
 * Get SQL from editor
 */
function getSql() {
  if (sqlEditor) {
    return sqlEditor.getValue().trim();
  }
  return document.getElementById("sql-input").value.trim();
}

/**
 * Set SQL in editor
 */
function setSql(sql) {
  if (sqlEditor) {
    sqlEditor.setValue(sql);
    sqlEditor.focus();
  } else {
    document.getElementById("sql-input").value = sql;
  }
}

/**
 * Execute SQL query via API
 */
async function executeQuery() {
  const sql = getSql();
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
      resultsEl.innerHTML = `
        <div class="results-info">
          <strong>MySQL returned an empty result set (i.e. zero rows).</strong>
        </div>`;
      return;
    }

    const table = createTable(result.rows);
    resultsEl.innerHTML = `
      <div class="results-info">
        Showing rows <strong>0 - ${result.rowCount - 1}</strong> (${result.rowCount} total, Query took ${result.durationMs} ms)
      </div>
      <div class="results-wrapper">
        ${table}
      </div>
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
      if (row[col] === null) {
        html += `<td class="null-value">NULL</td>`;
      } else {
        html += `<td>${escapeHtml(String(row[col]))}</td>`;
      }
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
 * Show table schema in results panel (phpMyAdmin style)
 */
async function showTableSchema(tableName) {
  const resultsEl = document.getElementById("results-container");
  const statusEl = document.getElementById("query-status");

  statusEl.textContent = "Loading structure...";
  statusEl.className = "status-text status-loading";

  try {
    const response = await fetch(`${API_URL}?action=schema&table=${tableName}`);
    const result = await response.json();

    if (result.success) {
      const schema = result.schema;
      const columns = Object.entries(schema.columns);

      statusEl.textContent = `Structure of table "${tableName}"`;
      statusEl.className = "status-text status-success";

      let html = `
        <div class="schema-panel">
          <div class="schema-header">
            <span class="schema-icon">📋</span>
            <strong>Structure of table "${escapeHtml(schema.name)}"</strong>
          </div>
          <div class="results-wrapper">
            <table class="results-table schema-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Column</th>
                  <th>Type</th>
                  <th>Null</th>
                  <th>Key</th>
                  <th>Default</th>
                  <th>Extra</th>
                </tr>
              </thead>
              <tbody>`;

      columns.forEach(([colName, colDef], index) => {
        let type = colDef.type;
        if (type === "VARCHAR" && colDef.length) {
          type += `(${colDef.length})`;
        }

        const isNull = colDef.nullable ? "Yes" : "No";
        const key = colDef.primaryKey ? "PRI" : colDef.unique ? "UNI" : "";
        const defaultVal = colDef.default !== undefined ? colDef.default : '<span class="null-value">NULL</span>';
        const extra = colDef.autoIncrement ? "auto_increment" : "";

        html += `
          <tr>
            <td>${index + 1}</td>
            <td><strong>${escapeHtml(colName)}</strong></td>
            <td>${escapeHtml(type.toLowerCase())}</td>
            <td>${isNull}</td>
            <td>${key ? `<span class="key-badge key-${key.toLowerCase()}">${key}</span>` : ""}</td>
            <td>${defaultVal}</td>
            <td>${extra}</td>
          </tr>`;
      });

      html += `
              </tbody>
            </table>
          </div>
          <div class="schema-footer">
            ${columns.length} column(s)
          </div>
        </div>`;

      resultsEl.innerHTML = html;
    } else {
      statusEl.textContent = `Error: ${result.error}`;
      statusEl.className = "status-text status-error";
      resultsEl.innerHTML = `<div class="error-box">${result.error}</div>`;
    }
  } catch (error) {
    statusEl.textContent = `Error: ${error.message}`;
    statusEl.className = "status-text status-error";
    resultsEl.innerHTML = `<div class="error-box">Error loading schema: ${error.message}</div>`;
  }
}

/**
 * Load example query
 */
function loadExampleQuery(key) {
  if (key && EXAMPLE_QUERIES[key]) {
    setSql(EXAMPLE_QUERIES[key]);
  }
}

/**
 * Clear query input
 */
function clearQuery() {
  setSql("");
  document.getElementById("query-status").textContent = "";
  document.getElementById("query-status").className = "status-text";
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
