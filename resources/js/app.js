import "./bootstrap";

const tokenKey = "task-platform-token";
let token = sessionStorage.getItem(tokenKey);
let currentUser = null;
let currentPage = 1;
let lastPage = 1;
let totalTasks = 0;
let tasksById = new Map();
let editingTask = null;
let selectedTask = null;
let searchTimer = null;
const recentAttachments = new Map();

const $ = (selector) => document.querySelector(selector);
const app = $("#app");
const loginScreen = $("#login-screen");
const loginForm = $("#login-form");
const loginError = $("#login-error");
const taskDialog = $("#task-dialog");
const taskForm = $("#task-form");
const taskError = $("#task-error");
const taskAssigneeSelect = taskForm.elements.assigned_user_id;
const filterAssigneeSelect = $("#filter-assignee");
const attachmentDialog = $("#attachment-dialog");
const attachmentForm = $("#attachment-form");
const attachmentError = $("#attachment-error");
const attachmentDropzone = $("#attachment-dropzone");
const attachmentFileInput = $("#attachment-file");
const attachmentFileName = $("#attachment-file-name");
const taskList = $("#task-list");

function setAuthenticated(isAuthenticated) {
    app.classList.toggle("hidden", !isAuthenticated);
    loginScreen.classList.toggle("hidden", isAuthenticated);
    if (!isAuthenticated) {
        loginForm.elements.email.focus();
    }
}

function showToast(message, isError = false) {
    const toast = $("#toast");
    toast.textContent = message;
    toast.classList.toggle("error", isError);
    toast.classList.remove("hidden");
    window.clearTimeout(showToast.timer);
    showToast.timer = window.setTimeout(
        () => toast.classList.add("hidden"),
        3200,
    );
}

async function apiRequest(path, options = {}) {
    const headers = new Headers(options.headers ?? {});
    headers.set("Accept", "application/json");

    if (token && !options.skipAuth) {
        headers.set("Authorization", `Bearer ${token}`);
    }

    let body = options.body;
    if (body && !(body instanceof FormData)) {
        headers.set("Content-Type", "application/json");
        body = JSON.stringify(body);
    }

    const response = await fetch(path, { ...options, headers, body });

    if (response.status === 401 && token && !options.skipAuth) {
        token = null;
        currentUser = null;
        sessionStorage.removeItem(tokenKey);
        setAuthenticated(false);
    }

    if (response.status === 204) {
        return null;
    }

    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
        const validationMessage = Object.values(payload.errors ?? {}).flat()[0];
        throw new Error(
            validationMessage || payload.message || "Request failed.",
        );
    }

    return payload;
}

function escapeHtml(value) {
    return String(value ?? "").replace(
        /[&<>"']/g,
        (character) =>
            ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;",
            })[character],
    );
}

function formatDate(value) {
    if (!value) return "No due date";
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? "No due date"
        : new Intl.DateTimeFormat(undefined, {
              day: "numeric",
              month: "short",
              year: "numeric",
          }).format(date);
}

function formatBytes(bytes) {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function showSelectedFile(file) {
    attachmentFileName.textContent = file
        ? `${file.name} · ${formatBytes(file.size)}`
        : "No file selected";
    attachmentError.textContent = "";
}

function renderUser() {
    $("#account-name").textContent = currentUser?.name ?? "";
    $("#account-email").textContent = currentUser?.email ?? "";
}

function populateUserSelect(select, users, emptyLabel) {
    const selectedId = select.value;
    const options = [new Option(emptyLabel, "")];

    for (const user of users) {
        options.push(
            new Option(`${user.name} (${user.email})`, String(user.id)),
        );
    }

    select.replaceChildren(...options);
    select.value = users.some((user) => String(user.id) === selectedId)
        ? selectedId
        : "";
}

async function loadUsers() {
    const result = await apiRequest("/api/users");
    const users = result.data ?? [];

    populateUserSelect(taskAssigneeSelect, users, "Unassigned");
    populateUserSelect(filterAssigneeSelect, users, "All assignees");
}

function renderTasks(tasks) {
    if (!tasks.length) {
        taskList.innerHTML =
            '<div class="empty-state"><strong>No tasks found</strong>Try changing the filters or create a task.</div>';
        return;
    }

    taskList.innerHTML = tasks
        .map((task, index) => {
            const status = String(task.status ?? "pending")
                .toLowerCase()
                .replace(/[^a-z0-9_-]/g, "");
            const priority = String(task.priority ?? "medium")
                .toLowerCase()
                .replace(/[^a-z0-9_-]/g, "");
            return `<article class="task-row" style="animation-delay:${Math.min(index * 25, 175)}ms">
			<div class="task-main">
				<h2 class="task-title">${escapeHtml(task.title)}</h2>
				<p class="task-description">${escapeHtml(task.description || "No description")}</p>
			</div>
			<div><span class="status-pill status-${status === "pending" || status === "in_progress" || status === "completed" ? status : "other"}">${escapeHtml(task.status || "pending").replaceAll("_", " ")}</span></div>
			<div><span class="priority-pill priority-${priority === "low" || priority === "medium" || priority === "high" ? priority : "medium"}">${escapeHtml(task.priority || "medium")}</span></div>
			<div class="task-meta">${escapeHtml(formatDate(task.due_date))}</div>
			<div class="task-meta">${escapeHtml(task.assigned_user?.name ?? "Unassigned")}</div>
			<div class="task-actions">
				<button class="button-quiet" type="button" data-action="files" data-id="${Number(task.id)}">Files</button>
				<button class="button-quiet" type="button" data-action="edit" data-id="${Number(task.id)}">Edit</button>
				<button class="button-quiet" type="button" data-action="delete" data-id="${Number(task.id)}" aria-label="Delete ${escapeHtml(task.title)}">Delete</button>
			</div>
		</article>`;
        })
        .join("");

    taskList.querySelectorAll("[data-action]").forEach((button) => {
        button.addEventListener("click", () =>
            handleTaskAction(button.dataset.action, Number(button.dataset.id)),
        );
    });
}

function renderPagination() {
    const pageNumbers = $("#page-numbers");
    const startPage = Math.max(1, Math.min(currentPage - 2, lastPage - 4));
    const endPage = Math.min(lastPage, startPage + 4);
    const pages = [];

    if (startPage > 1) {
        pages.push(1);
        if (startPage > 2) pages.push("ellipsis");
    }

    for (let page = startPage; page <= endPage; page++) {
        pages.push(page);
    }

    if (endPage < lastPage) {
        if (endPage < lastPage - 1) pages.push("ellipsis");
        pages.push(lastPage);
    }

    pageNumbers.innerHTML = pages
        .map((page) => {
            if (page === "ellipsis") {
                return '<span class="page-ellipsis" aria-hidden="true">…</span>';
            }

            const current = page === currentPage;
            return `<button class="page-button" type="button" data-page="${page}" aria-label="Go to page ${page}"${current ? ' aria-current="page"' : ""}>${page}</button>`;
        })
        .join("");
    pageNumbers.classList.toggle("hidden", lastPage <= 1);

    pageNumbers.querySelectorAll("[data-page]").forEach((button) => {
        button.addEventListener("click", () =>
            loadTasks(Number(button.dataset.page)),
        );
    });

    $("#previous-page").disabled = currentPage <= 1;
    $("#next-page").disabled = currentPage >= lastPage;
}

async function loadTasks(page = currentPage) {
    taskList.innerHTML = '<div class="loading-state">Loading tasks...</div>';
    const params = new URLSearchParams({
        page: String(page),
        per_page: "10",
        sort_by: $("#sort-by").value,
        sort_direction: $("#sort-direction").value,
    });

    for (const [id, queryName] of [
        ["filter-status", "status"],
        ["filter-priority", "priority"],
        ["search-tasks", "search"],
    ]) {
        const value = $(`#${id}`).value.trim();
        if (value) params.set(queryName, value);
    }
    const assignedUserId = $("#filter-assignee").value.trim();
    if (assignedUserId) params.set("assigned_user_id", assignedUserId);

    try {
        const result = await apiRequest(`/api/tasks?${params}`);
        currentPage = result.current_page;
        lastPage = result.last_page;
        totalTasks = result.total;
        tasksById = new Map(
            (result.data ?? []).map((task) => [Number(task.id), task]),
        );
        renderTasks(result.data ?? []);
        $("#task-total").textContent = totalTasks;
        $("#page-status").textContent = `Page ${currentPage} of ${lastPage}`;
        $("#result-range").textContent =
            totalTasks === 0
                ? "No tasks"
                : `Showing ${result.from}–${result.to} of ${totalTasks}`;
        renderPagination();
    } catch (error) {
        taskList.innerHTML = `<div class="empty-state"><strong>Could not load tasks</strong>${escapeHtml(error.message)}</div>`;
    }
}

function openTaskDialog(task = null) {
    editingTask = task;
    taskForm.reset();
    taskError.textContent = "";
    $("#task-dialog-title").textContent = task ? "Edit task" : "New task";
    taskForm.elements.title.value = task?.title ?? "";
    taskForm.elements.description.value = task?.description ?? "";
    taskForm.elements.status.value = task?.status ?? "pending";
    taskForm.elements.priority.value = task?.priority ?? "medium";
    taskForm.elements.assigned_user_id.value = task?.assigned_user_id ?? "";
    taskForm.elements.due_date.value = task?.due_date
        ? new Date(task.due_date).toISOString().slice(0, 16)
        : "";
    taskDialog.showModal();
    taskForm.elements.title.focus();
}

async function saveTask(event) {
    event.preventDefault();
    const form = taskForm.elements;
    const payload = {
        title: form.title.value.trim(),
        description: form.description.value.trim() || null,
        status: form.status.value,
        priority: form.priority.value,
        assigned_user_id: form.assigned_user_id.value
            ? Number(form.assigned_user_id.value)
            : null,
        due_date: form.due_date.value
            ? new Date(form.due_date.value).toISOString()
            : null,
    };
    const submitButton = taskForm.querySelector('[type="submit"]');
    submitButton.disabled = true;

    try {
        if (editingTask) {
            await apiRequest(`/api/tasks/${editingTask.id}`, {
                method: "PUT",
                body: payload,
            });
            showToast("Task updated.");
        } else {
            await apiRequest("/api/tasks", { method: "POST", body: payload });
            currentPage = 1;
            showToast("Task created.");
        }
        taskDialog.close();
        await loadTasks(currentPage);
    } catch (error) {
        taskError.textContent = error.message;
    } finally {
        submitButton.disabled = false;
    }
}

async function handleTaskAction(action, id) {
    const task = tasksById.get(id);
    if (action === "files") {
        if (task) openAttachmentDialog(task);
        return;
    }

    if (action === "edit") {
        if (task) openTaskDialog(task);
        return;
    }

    if (
        action === "delete" &&
        window.confirm(
            "Delete this task? Its attachments and comments will also be removed.",
        )
    ) {
        try {
            await apiRequest(`/api/tasks/${id}`, { method: "DELETE" });
            showToast("Task deleted.");
            await loadTasks(currentPage);
        } catch (error) {
            showToast(error.message, true);
        }
    }
}

function openAttachmentDialog(task) {
    selectedTask = task;
    attachmentError.textContent = "";
    attachmentForm.reset();
    showSelectedFile(null);
    attachmentDropzone.classList.remove("is-dragging");
    $("#attachment-task-title").textContent = task.title;
    renderRecentAttachments();
    attachmentDialog.showModal();
}

function renderRecentAttachments() {
    const attachments = recentAttachments.get(Number(selectedTask.id)) ?? [];
    const list = $("#attachment-list");

    if (!attachments.length) {
        list.innerHTML =
            '<div class="empty-state">No recent uploads in this session.</div>';
        return;
    }

    list.innerHTML = attachments
        .map(
            (attachment) => `<div class="attachment-item">
		<div class="attachment-description">
			<p class="attachment-name">${escapeHtml(attachment.file_name)}</p>
			<p class="attachment-size">${formatBytes(Number(attachment.file_size))}</p>
		</div>
		<div class="attachment-actions">
			<button class="button-quiet" type="button" data-file-action="download" data-id="${Number(attachment.id)}">Download</button>
			<button class="button-quiet" type="button" data-file-action="delete" data-id="${Number(attachment.id)}">Delete</button>
		</div>
	</div>`,
        )
        .join("");

    list.querySelectorAll("[data-file-action]").forEach((button) => {
        button.addEventListener("click", () =>
            handleAttachmentAction(
                button.dataset.fileAction,
                Number(button.dataset.id),
            ),
        );
    });
}

async function uploadAttachment(event) {
    event.preventDefault();
    const fileInput = $("#attachment-file");
    const file = fileInput.files[0];
    if (!file || !selectedTask) return;

    const button = attachmentForm.querySelector('[type="submit"]');
    button.disabled = true;
    attachmentError.textContent = "";
    const formData = new FormData();
    formData.append("file", file);

    try {
        const result = await apiRequest(
            `/api/tasks/${selectedTask.id}/attachments`,
            {
                method: "POST",
                body: formData,
            },
        );
        const attachments =
            recentAttachments.get(Number(selectedTask.id)) ?? [];
        attachments.unshift(result.data);
        recentAttachments.set(Number(selectedTask.id), attachments);
        renderRecentAttachments();
        attachmentForm.reset();
        showToast("Attachment uploaded.");
    } catch (error) {
        attachmentError.textContent = error.message;
    } finally {
        button.disabled = false;
    }
}

async function handleAttachmentAction(action, id) {
    const attachments = recentAttachments.get(Number(selectedTask.id)) ?? [];
    const attachment = attachments.find((item) => Number(item.id) === id);
    if (!attachment) return;

    try {
        if (action === "delete") {
            await apiRequest(`/api/attachments/${id}`, { method: "DELETE" });
            recentAttachments.set(
                Number(selectedTask.id),
                attachments.filter((item) => Number(item.id) !== id),
            );
            renderRecentAttachments();
            showToast("Attachment deleted.");
            return;
        }

        const response = await fetch(`/api/attachments/${id}/download`, {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: "application/octet-stream",
            },
        });
        if (!response.ok) throw new Error("Attachment download failed.");
        const objectUrl = URL.createObjectURL(await response.blob());
        const link = document.createElement("a");
        link.href = objectUrl;
        link.download = attachment.file_name;
        link.click();
        URL.revokeObjectURL(objectUrl);
    } catch (error) {
        showToast(error.message, true);
    }
}

async function login(event) {
    event.preventDefault();
    loginError.textContent = "";
    const button = loginForm.querySelector('[type="submit"]');
    button.disabled = true;

    try {
        const result = await apiRequest("/api/auth/login", {
            method: "POST",
            skipAuth: true,
            body: {
                email: loginForm.elements.email.value.trim(),
                password: loginForm.elements.password.value,
            },
        });
        token = result.access_token;
        currentUser = result.user;
        sessionStorage.setItem(tokenKey, token);
        renderUser();
        setAuthenticated(true);
        currentPage = 1;
        await loadUsers();
        await loadTasks();
    } catch (error) {
        loginError.textContent = error.message;
    } finally {
        button.disabled = false;
    }
}

async function logout() {
    try {
        if (token) await apiRequest("/api/auth/logout", { method: "POST" });
    } catch (error) {
        showToast(error.message, true);
    } finally {
        token = null;
        currentUser = null;
        sessionStorage.removeItem(tokenKey);
        setAuthenticated(false);
    }
}

async function initialize() {
    if (!token) {
        setAuthenticated(false);
        return;
    }

    try {
        const result = await apiRequest("/api/auth/me");
        currentUser = result.user;
        renderUser();
        setAuthenticated(true);
        await loadUsers();
        await loadTasks(1);
    } catch {
        setAuthenticated(false);
    }
}

loginForm.addEventListener("submit", login);
taskForm.addEventListener("submit", saveTask);
attachmentForm.addEventListener("submit", uploadAttachment);
attachmentFileInput.addEventListener("change", () => {
    showSelectedFile(attachmentFileInput.files[0] ?? null);
});
attachmentDropzone.addEventListener("click", (event) => {
    if (!event.target.closest("label")) attachmentFileInput.click();
});
attachmentDropzone.addEventListener("keydown", (event) => {
    if (
        event.target === attachmentDropzone &&
        ["Enter", " "].includes(event.key)
    ) {
        event.preventDefault();
        attachmentFileInput.click();
    }
});
for (const eventName of ["dragenter", "dragover"]) {
    attachmentDropzone.addEventListener(eventName, (event) => {
        event.preventDefault();
        attachmentDropzone.classList.add("is-dragging");
    });
}
attachmentDropzone.addEventListener("dragleave", (event) => {
    if (!attachmentDropzone.contains(event.relatedTarget)) {
        attachmentDropzone.classList.remove("is-dragging");
    }
});
attachmentDropzone.addEventListener("drop", (event) => {
    event.preventDefault();
    attachmentDropzone.classList.remove("is-dragging");
    const files = event.dataTransfer?.files;
    if (!files?.length) return;
    if (files.length > 1) {
        attachmentError.textContent = "Upload one file at a time.";
        return;
    }

    const transfer = new DataTransfer();
    transfer.items.add(files[0]);
    attachmentFileInput.files = transfer.files;
    showSelectedFile(files[0]);
});
$("#new-task-button").addEventListener("click", () => openTaskDialog());
$("#logout-button").addEventListener("click", logout);
$("#previous-page").addEventListener("click", () => loadTasks(currentPage - 1));
$("#next-page").addEventListener("click", () => loadTasks(currentPage + 1));

for (const selector of [
    "#filter-status",
    "#filter-priority",
    "#sort-by",
    "#sort-direction",
    "#filter-assignee",
]) {
    $(selector).addEventListener("change", () => loadTasks(1));
}

$("#search-tasks").addEventListener("input", () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(() => loadTasks(1), 300);
});

initialize();
