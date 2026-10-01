<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Taskboard | Fieldwork</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main id="login-screen" class="auth-screen">
        <section class="auth-layout" aria-label="Sign in">
            <div class="auth-aside">
                <a class="brand" href="/" aria-label="Fieldwork home">
                    <span class="brand-mark">F</span>
                    <span><span class="brand-name">Fieldwork</span><span class="brand-caption">Task management</span></span>
                </a>
                <div class="auth-message">
                    <h1>Make work<br>move forward.</h1>
                    <p>A clear view of what's moving, what's next, and who owns it.</p>
                </div>
                <div class="auth-aside-note">WORKSPACE / TASKS</div>
            </div>
            <div class="auth-form-side">
                <form id="login-form" class="auth-form">
                    <p class="eyebrow">Welcome back</p>
                    <h2>Sign in to your workspace</h2>
                    <p>Use your account credentials to continue.</p>
                    <div class="field">
                        <label for="login-email">Email</label>
                        <input id="login-email" name="email" type="email" autocomplete="username" required>
                    </div>
                    <div class="field">
                        <label for="login-password">Password</label>
                        <input id="login-password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="button-primary" type="submit">Sign in <span aria-hidden="true">→</span></button>
                    <p id="login-error" class="form-error" role="alert"></p>
                </form>
            </div>
        </section>
    </main>

    <div id="app" class="app-shell hidden">
        <aside class="sidebar">
            <a class="brand" href="/" aria-label="Fieldwork home">
                <span class="brand-mark">F</span>
                <span><span class="brand-name">Fieldwork</span><span class="brand-caption">Task management</span></span>
            </a>
            <p class="nav-label">Workspace</p>
            <div class="nav-item active"><span class="nav-dot"></span>Tasks</div>
            <div class="sidebar-spacer"></div>
            <div class="account-block">
                <p id="account-name" class="account-name"></p>
                <p id="account-email" class="account-email"></p>
                <button id="logout-button" class="text-button" type="button">Sign out</button>
            </div>
        </aside>

        <section class="workspace" aria-label="Task workspace">
            <div class="topline">
                <div class="crumb">Workspace <span aria-hidden="true">/</span> Tasks</div>
                <div class="connection">Task workspace</div>
            </div>

            <header class="page-heading">
                <div>
                    <p class="eyebrow">Overview / Task management</p>
                    <h1>Tasks</h1>
                    <p class="page-subtitle">Plan the work. Keep it moving.</p>
                </div>
                <button id="new-task-button" class="button-primary" type="button"><span aria-hidden="true">＋</span> New task</button>
            </header>

            <div class="overview">
                <span class="overview-mark" aria-hidden="true">↗</span>
                <div class="overview-copy"><strong id="task-total">0</strong> matching tasks</div>
            </div>

            <section class="filters" aria-label="Task filters">
                <div class="search-wrap">
                    <svg class="search-glyph" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg>
                    <input id="search-tasks" class="control" type="search" placeholder="Search tasks..." aria-label="Search tasks">
                </div>
                <div class="filter-control">
                    <svg class="filter-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>
                    <select id="filter-status" class="control" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In progress</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="filter-control">
                    <svg class="filter-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 20v-4m6 4V9m6 11V4"></path></svg>
                    <select id="filter-priority" class="control" aria-label="Filter by priority">
                        <option value="">All priorities</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div class="filter-control">
                    <svg class="filter-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                    <select id="filter-assignee" class="control" aria-label="Filter by assignee">
                        <option value="">All assignees</option>
                    </select>
                </div>
                <div class="filter-control">
                    <svg class="filter-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 7V3L4 7l4 4V7h12M16 17v4l4-4-4-4v4H4"></path></svg>
                    <select id="sort-by" class="control" aria-label="Sort tasks by">
                        <option value="created_at">Recently created</option>
                        <option value="due_date">Due date</option>
                        <option value="title">Title</option>
                        <option value="priority">Priority</option>
                        <option value="status">Status</option>
                        <option value="assigned_user_id">Assignee</option>
                    </select>
                </div>
                <div class="filter-control">
                    <svg class="filter-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 7V3L4 7l4 4V7h12M16 17v4l4-4-4-4v4H4"></path></svg>
                    <select id="sort-direction" class="control" aria-label="Sort direction">
                        <option value="desc">Descending</option>
                        <option value="asc">Ascending</option>
                    </select>
                </div>
            </section>

            <div class="results-line">
                <span id="result-range">Loading tasks...</span>
                <span id="page-status"></span>
            </div>
            <section id="task-list" class="task-list" aria-live="polite">
                <div class="loading-state">Loading tasks...</div>
            </section>
            <nav class="pagination" aria-label="Task pages">
                <div class="pagination-actions">
                    <button id="previous-page" class="button-secondary" type="button" aria-label="Previous page" disabled>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>
                        <span>Previous</span>
                    </button>
                    <div id="page-numbers" class="page-numbers" aria-label="Page numbers"></div>
                    <button id="next-page" class="button-secondary" type="button" aria-label="Next page" disabled>
                        <span>Next</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>
                    </button>
                </div>
            </nav>
        </section>
    </div>

    <dialog id="task-dialog">
        <form id="task-form">
            <header class="dialog-head">
                <div><h2 id="task-dialog-title">New task</h2><p>Task details</p></div>
                <button class="button-quiet" type="button" data-close="task-dialog" aria-label="Close">Close</button>
            </header>
            <div class="dialog-body">
                <div class="form-grid">
                    <div class="field span-two"><label for="task-title">Title</label><input id="task-title" name="title" maxlength="255" required></div>
                    <div class="field span-two"><label for="task-description">Description</label><textarea id="task-description" name="description"></textarea></div>
                    <div class="field"><label for="task-status">Status</label><select id="task-status" name="status"><option value="pending">Pending</option><option value="in_progress">In progress</option><option value="completed">Completed</option></select></div>
                    <div class="field"><label for="task-priority">Priority</label><select id="task-priority" name="priority"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
                    <div class="field"><label for="task-assignee">Assigned to</label><select id="task-assignee" name="assigned_user_id"><option value="">Unassigned</option></select></div>
                    <div class="field"><label for="task-due-date">Due date</label><input id="task-due-date" name="due_date" type="datetime-local"></div>
                </div>
                <p id="task-error" class="form-error" role="alert"></p>
            </div>
            <footer class="dialog-actions">
                <button class="button-secondary" type="button" data-close="task-dialog">Cancel</button>
                <button class="button-primary" type="submit">Save task</button>
            </footer>
        </form>
    </dialog>

    <dialog id="attachment-dialog">
        <form id="attachment-form">
            <header class="dialog-head">
                <div><h2>Attachments</h2><p id="attachment-task-title"></p></div>
                <button class="button-quiet" type="button" data-close="attachment-dialog" aria-label="Close">Close</button>
            </header>
            <div class="dialog-body">
                <div id="attachment-dropzone" class="attachment-picker" tabindex="0" role="group" aria-label="File upload area">
                    <span class="upload-mark" aria-hidden="true">↑</span>
                    <p class="drop-title">Drop a file here</p>
                    <p class="drop-copy">or choose one from your device</p>
                    <label class="button-secondary file-browse" for="attachment-file">Choose file</label>
                    <input class="attachment-file-input" id="attachment-file" name="file" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.csv,.txt,.mp4,.webm" required>
                    <p id="attachment-file-name" class="selected-file-name" aria-live="polite">No file selected</p>
                </div>
                <p id="attachment-error" class="form-error" role="alert"></p>
                <p class="eyebrow">Recent uploads</p>
                <div id="attachment-list" class="attachment-list"></div>
            </div>
            <footer class="dialog-actions">
                <button class="button-secondary" type="button" data-close="attachment-dialog">Close</button>
                <button class="button-primary" type="submit">Upload file</button>
            </footer>
        </form>
    </dialog>

    <div id="toast" class="toast hidden" role="status" aria-live="polite"></div>

    <script>
        document.querySelectorAll('[data-close]').forEach((button) => {
            button.addEventListener('click', () => document.getElementById(button.dataset.close).close());
        });
    </script>
</body>
</html>