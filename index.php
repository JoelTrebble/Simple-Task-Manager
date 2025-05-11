<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container">
        <h1>Task Manager</h1>

        <form id="task-form">
            <input type="text" id="task-input" placeholder="Add a new task..." required>
            <button type="submit">Add Task</button>
        </form>

        <div id="error-container" class="error" style="display: none;"></div>

        <div id="loading" class="loading" style="display: none;">Loading tasks...</div>

        <ul id="task-list" class="task-list"></ul>

        <div class="stats">
            <div>
                <span id="total-tasks">0</span>
                Total Tasks
            </div>
            <div>
                <span id="completed-tasks">0</span>
                Completed
            </div>
            <div>
                <span id="pending-tasks">0</span>
                Pending
            </div>
        </div>
    </div>
    <script src="scripts.js"></script>
</body>

</html>