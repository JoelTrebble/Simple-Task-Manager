<?php
/**
 * Task Manager API
 * 
 * This API handles CRUD operations for a task management system
 * using a MySQL database with MySQLi extension.
 * 
 * Flow of execution:
 * 1. Include database configuration (config.php)
 * 2. Set HTTP headers for API responses
 * 3. Handle the request based on the 'action' parameter
 * 4. Execute the requested operation and send a JSON response
 */

//Include database configuration from external file
//This file likely contains database credentials and connection settings
require_once 'config.php';

//Set proper HTTP headers for API responses
//Content-Type: application/json tells the client to expect JSON data
header('Content-Type: application/json');

//CORS (Cross-Origin Resource Sharing) headers
//These allow the API to be accessed from different domains during development
header('Access-Control-Allow-Origin: *');         //Allow requests from any origin
header('Access-Control-Allow-Methods: GET, POST'); //Allow GET and POST methods
header('Access-Control-Allow-Headers: Content-Type'); //Allow Content-Type header

/**
 * Main API controller function
 * 
 * Serves as the entry point that routes requests to appropriate handler functions
 * based on the 'action' parameter provided in the request.
 */
function handleRequest() {
    //Extract the 'action' parameter from GET or POST request
    //If not provided, default to empty string
    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
    
    //Router: Process different actions using a switch statement
    try {
        switch ($action) {
            case 'get_tasks':
                //Retrieve all tasks from the database
                getTasks();
                break;
            case 'add_task':
                //Create a new task in the database
                addTask();
                break;
            case 'toggle_status':
                //Toggle the completion status of a task
                toggleTaskStatus();
                break;
            case 'delete_task':
                //Remove a task from the database
                deleteTask();
                break;
            default:
                //Handle invalid or missing action parameter
                sendResponse(false, 'Invalid action requested');
        }
    } catch (Exception $e) {
        //Catch and handle any exceptions from the handler functions
        sendResponse(false, $e->getMessage());
    }
}

/**
 * Retrieve all tasks from the database
 * 
 * Gets all tasks ordered by creation date (newest first)
 * and formats them for the frontend.
 */
function getTasks() {
    try {
        //Connect to the database
        //Note: There's a commented error here, but it's noted that everything still works
        $db = getDbConnection(); 
        
        //SQL query to get all tasks, ordered by creation date (descending)
        $query = "SELECT * FROM tasks ORDER BY created_at DESC";
        $result = $db->query($query);
        
        //Check if the query executed successfully
        if (!$result) {
            throw new Exception("Database query error: " . $db->error);
        }
        
        //Initialize an empty array to store the tasks
        $tasks = []; 
        
        //Loop through each row in the result set
        while ($row = $result->fetch_assoc()) {
            //Format each task for the frontend
            //- Convert ID to string (for JavaScript compatibility)
            //- Convert 'completed' to boolean
            $tasks[] = [
                'id' => (string) $row['id'],
                'title' => $row['title'],
                'completed' => (bool) $row['completed'],
                'created_at' => $row['created_at']
            ];
        }
        
        //Free the result set to release memory
        $result->free();
        
        //Close the database connection
        $db->close();
        
        //Send the formatted tasks as a JSON response
        sendResponse(true, 'Tasks retrieved successfully', [
            'tasks' => $tasks
        ]);
    } catch (Exception $e) {
        //Re-throw exception with a more specific message
        throw new Exception('Error retrieving tasks: ' . $e->getMessage());
    }
}

/**
 * Add a new task to the database
 * 
 * Validates the request method and task title,
 * then inserts a new task and returns the created task.
 */
function addTask() {
    //Verify that the request is using POST method
    //This is a security measure since we're modifying data
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }
    
    //Get and validate the task title
    //Trim whitespace and check if it's empty
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    if (empty($title)) {
        sendResponse(false, 'Task title is required');
        return;
    }
    
    try {
        //Connect to the database
        $db = getDbConnection();
        
        //Prepare a parameterized SQL statement to prevent SQL injection
        $stmt = $db->prepare("INSERT INTO tasks (title) VALUES (?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        //Bind the title parameter to the statement
        //'s' indicates that the parameter is a string
        $stmt->bind_param("s", $title);
        
        //Execute the prepared statement
        $success = $stmt->execute();
        
        //Check if execution was successful
        if (!$success) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        //Get the ID of the newly inserted task
        $newTaskId = $db->insert_id;
        
        //Close the prepared statement
        $stmt->close();
        
        //Retrieve the complete task data from the database
        //This includes default values set by the database (like created_at)
        $query = "SELECT * FROM tasks WHERE id = $newTaskId";
        $result = $db->query($query);
        
        //Check if the query executed successfully
        if (!$result) {
            throw new Exception("Database query error: " . $db->error);
        }
        
        //Fetch the task data
        $task = $result->fetch_assoc();
        
        //Free the result set
        $result->free();
        
        //Close the database connection
        $db->close();
        
        //Format the task for the frontend
        $newTask = [
            'id' => (string) $task['id'],
            'title' => $task['title'],
            'completed' => (bool) $task['completed'],
            'created_at' => $task['created_at']
        ];
        
        //Send the newly created task as a JSON response
        sendResponse(true, 'Task added successfully', [
            'task' => $newTask
        ]);
    } catch (Exception $e) {
        //Re-throw exception with a more specific message
        throw new Exception('Error adding task: ' . $e->getMessage());
    }
}

/**
 * Toggle the completion status of a task
 * 
 * Flips the 'completed' status between 0 and 1 for the specified task.
 */
function toggleTaskStatus() {
    //Verify that the request is using POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }
    
    //Get and validate the task ID
    //Check that it exists and is numeric
    $taskId = isset($_POST['id']) ? $_POST['id'] : '';
    if (empty($taskId) || !is_numeric($taskId)) {
        sendResponse(false, 'Valid task ID is required');
        return;
    }
    
    try {
        //Connect to the database
        $db = getDbConnection();
        
        //First, check if the task exists and get its current status
        $query = "SELECT completed FROM tasks WHERE id = $taskId";
        $result = $db->query($query);
        
        //Check if the query executed successfully
        if (!$result) {
            throw new Exception("Database query error: " . $db->error);
        }
        
        //If no rows were returned, the task doesn't exist
        if ($result->num_rows === 0) {
            $result->free();
            $db->close();
            sendResponse(false, 'Task not found');
            return;
        }
        
        //Get the current completion status
        $task = $result->fetch_assoc();
        $result->free();
    
        //Toggle the status (0 to 1, or 1 to 0)
        $newStatus = $task['completed'] ? 0 : 1;
        
        //Prepare a parameterized SQL statement to update the task
        $stmt = $db->prepare("UPDATE tasks SET completed = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        //Bind parameters: status (integer) and task ID (integer)
        //'ii' indicates that both parameters are integers
        $stmt->bind_param("ii", $newStatus, $taskId);
        
        //Execute the prepared statement
        $success = $stmt->execute();
        
        //Check if execution was successful
        if (!$success) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        //Close the prepared statement
        $stmt->close();
        
        //Close the database connection
        $db->close();
        
        //Send success response
        sendResponse(true, 'Task status updated successfully');
    } catch (Exception $e) {
        //Re-throw exception with a more specific message
        throw new Exception('Error updating task: ' . $e->getMessage());
    }
}

/**
 * Delete a task from the database
 * 
 * Removes the specified task if it exists.
 */
function deleteTask() {
    //Verify that the request is using POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(false, 'Invalid request method');
        return;
    }
    
    //Get and validate the task ID
    //Check that it exists and is numeric
    $taskId = isset($_POST['id']) ? $_POST['id'] : '';
    if (empty($taskId) || !is_numeric($taskId)) {
        sendResponse(false, 'Valid task ID is required');
        return;
    }
    
    try {
        //Connect to the database
        $db = getDbConnection();
        
        //First, check if the task exists
        $query = "SELECT id FROM tasks WHERE id = $taskId";
        $result = $db->query($query);
        
        //Check if the query executed successfully
        if (!$result) {
            throw new Exception("Database query error: " . $db->error);
        }
        
        //If no rows were returned, the task doesn't exist
        if ($result->num_rows === 0) {
            $result->free();
            $db->close();
            sendResponse(false, 'Task not found');
            return;
        }
        
        //Free the result set
        $result->free();
        
        //Prepare a parameterized SQL statement to delete the task
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $db->error);
        }
        
        //Bind the task ID parameter
        //'i' indicates that the parameter is an integer
        $stmt->bind_param("i", $taskId);
        
        //Execute the prepared statement
        $success = $stmt->execute();
        
        //Check if execution was successful
        if (!$success) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        //Close the prepared statement
        $stmt->close();
        
        //Close the database connection
        $db->close();
        
        //Send success response
        sendResponse(true, 'Task deleted successfully');
    } catch (Exception $e) {
        //Re-throw exception with a more specific message
        throw new Exception('Error deleting task: ' . $e->getMessage());
    }
}

/**
 * Send a JSON response to the client
 * 
 * @param bool $success - Whether the operation was successful
 * @param string $message - A message describing the result
 * @param array $data - Optional additional data to include in the response
 */
function sendResponse($success, $message, $data = []) {
    //Create the base response array
    $response = [
        'success' => $success,
        'message' => $message
    ];

    //If additional data was provided, merge it into the response
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }

    //Encode the response as JSON, output it, and terminate execution
    echo json_encode($response);
    exit;
}

//Start processing the request
//This is the entry point of the script execution
handleRequest();