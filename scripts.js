//Global variables
const apiUrl = 'api.php';
let tasks = [];

//DOM Elements
const taskForm = document.getElementById('task-form');
const taskInput = document.getElementById('task-input');
const taskList = document.getElementById('task-list');
const loadingElement = document.getElementById('loading');
const errorContainer = document.getElementById('error-container');
const totalTasksElement = document.getElementById('total-tasks');
const completedTasksElement = document.getElementById('completed-tasks');
const pendingTasksElement = document.getElementById('pending-tasks');

//Event Listeners
document.addEventListener('DOMContentLoaded', fetchTasks);
taskForm.addEventListener('submit', addTask);

//Fetch all tasks from the API
async function fetchTasks() {
    showLoading(true);
    hideError();

    try {
        const response = await fetch(apiUrl + '?action=get_tasks');

        if (!response.ok) {
            throw new Error('Failed to fetch tasks');
        }

        const data = await response.json();

        if (data.success) {
            tasks = data.tasks;
            renderTasks();
            updateStats();
        } else {
            showError(data.message || 'Failed to fetch tasks');
        }
    } catch (error) {
        console.error('Error fetching tasks:', error);
        showError('Failed to connect to the server. Please try again later.');
    } finally {
        showLoading(false);
    }
}

//Add a new task
async function addTask(event) {
    event.preventDefault();

    const taskText = taskInput.value.trim();
    if (!taskText) return;

    showLoading(true);
    hideError();

    try {
        const formData = new FormData();
        formData.append('action', 'add_task');
        formData.append('title', taskText);

        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Failed to add task');
        }

        const data = await response.json();

        if (data.success) {
            taskInput.value = '';
            tasks.push(data.task);
            renderTasks();
            updateStats();
        } else {
            showError(data.message || 'Failed to add task');
        }
    } catch (error) {
        console.error('Error adding task:', error);
        showError('Failed to connect to the server. Please try again later.');
    } finally {
        showLoading(false);
    }
}

//Toggle task completion status
async function toggleTaskStatus(id) {
    showLoading(true);
    hideError();

    try {
        const task = tasks.find(t => t.id === id);
        if (!task) return;

        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', id);

        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Failed to update task');
        }

        const data = await response.json();

        if (data.success) {
            task.completed = !task.completed;
            renderTasks();
            updateStats();
        } else {
            showError(data.message || 'Failed to update task');
        }
    } catch (error) {
        console.error('Error updating task:', error);
        showError('Failed to connect to the server. Please try again later.');
    } finally {
        showLoading(false);
    }
}

//Delete a task
async function deleteTask(id) {
    if (!confirm('Are you sure you want to delete this task?')) return;

    showLoading(true);
    hideError();

    try {
        const formData = new FormData();
        formData.append('action', 'delete_task');
        formData.append('id', id);

        const response = await fetch(apiUrl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error('Failed to delete task');
        }

        const data = await response.json();

        if (data.success) {
            tasks = tasks.filter(task => task.id !== id);
            renderTasks();
            updateStats();
        } else {
            showError(data.message || 'Failed to delete task');
        }
    } catch (error) {
        console.error('Error deleting task:', error);
        showError('Failed to connect to the server. Please try again later.');
    } finally {
        showLoading(false);
    }
}

//Render all tasks to the DOM
function renderTasks() {
    taskList.innerHTML = '';

    if (tasks.length === 0) {
        const emptyMessage = document.createElement('li');
        emptyMessage.textContent = 'No tasks yet. Add one above!';
        emptyMessage.style.textAlign = 'center';
        emptyMessage.style.padding = '20px';
        emptyMessage.style.fontStyle = 'italic';
        emptyMessage.style.color = '#777';
        taskList.appendChild(emptyMessage);
        return;
    }

    tasks.forEach(task => {
        const li = document.createElement('li');
        li.className = 'task-item';
        if (task.completed) {
            li.classList.add('completed');
        }

        const titleSpan = document.createElement('span');
        titleSpan.textContent = task.title;

        const actionsDiv = document.createElement('div');
        actionsDiv.className = 'actions';

        const toggleButton = document.createElement('button');
        toggleButton.textContent = task.completed ? '↩️' : '✓';
        toggleButton.style.backgroundColor = task.completed ? '#e67e22' : '#2ecc71';
        toggleButton.onclick = () => toggleTaskStatus(task.id);

        const deleteButton = document.createElement('button');
        deleteButton.textContent = '🗑️';
        deleteButton.style.backgroundColor = '#e74c3c';
        deleteButton.onclick = () => deleteTask(task.id);

        actionsDiv.appendChild(toggleButton);
        actionsDiv.appendChild(deleteButton);

        li.appendChild(titleSpan);
        li.appendChild(actionsDiv);
        taskList.appendChild(li);
    });
}

//Update statistics
function updateStats() {
    const total = tasks.length;
    const completed = tasks.filter(task => task.completed).length;
    const pending = total - completed;

    totalTasksElement.textContent = total;
    completedTasksElement.textContent = completed;
    pendingTasksElement.textContent = pending;
}

//Helper functions for UI
function showLoading(show) {
    loadingElement.style.display = show ? 'block' : 'none';
}

function showError(message) {
    errorContainer.textContent = message;
    errorContainer.style.display = 'block';
}

function hideError() {
    errorContainer.style.display = 'none';
}
