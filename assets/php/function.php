<?php
require_once 'config.php';

$db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME) or die("Database is not connected");

// Function to show pages
function showPage($page)
{
    $safePage = basename($page); // Prevent directory traversal
    include("./assets/pages/$safePage.php");
}

// Function to show error
function showError($field)
{
    if (isset($_SESSION['error'])) {
        $error = $_SESSION['error'];
        if (isset($error['field']) && $field == $error['field']) {
            echo "<div class='alert alert-danger my-2' role='alert'>{$error['msg']}</div>";
        }
    }
}

// Function to show previous form data
function showFormData($field)
{
    if (isset($_SESSION['formdata'])) {
        $formdata = $_SESSION['formdata'];
        return $formdata[$field] ?? null;
    }
}

// Function to check if email is already registered
function isEmailRegistered($email)
{
    global $db;
    $email = mysqli_real_escape_string($db, $email);
    $query = "SELECT COUNT(*) as row FROM users WHERE email='$email'";
    $result = mysqli_query($db, $query);
    $return_data = mysqli_fetch_assoc($result);
    return $return_data['row'];
}

// Function to check if username is already registered
function isUsernameRegistered($username)
{
    global $db;
    $username = mysqli_real_escape_string($db, $username);
    $query = "SELECT COUNT(*) as row FROM users WHERE username='$username'";
    $result = mysqli_query($db, $query);
    $return_data = mysqli_fetch_assoc($result);
    return $return_data['row'];
}

// Function to check if username is registered by another user
function isUsernameRegisteredByOther($username)
{
    global $db;
    $user_id = $_SESSION['userdata']['id'];
    $username = mysqli_real_escape_string($db, $username);
    $query = "SELECT COUNT(*) as row FROM users WHERE username='$username' AND id != $user_id";
    $result = mysqli_query($db, $query);
    $return_data = mysqli_fetch_assoc($result);
    return $return_data['row'];
}

// Function to validate signup form
function validateSignupForm($form_data)
{
    $response = array('status' => true, 'msg' => '');

    if (!isset($form_data['password']) || empty($form_data['password'])) {
        $response['msg'] = "Password is required";
        $response['status'] = false;
        $response['field'] = 'password';
    } elseif (strlen($form_data['password']) < 8) {
        $response['msg'] = "Password must be at least 8 characters long";
        $response['status'] = false;
        $response['field'] = 'password';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $form_data['password'])) {
        $response['msg'] = "Password must include at least one special character (!@#$%^&*(),.?\":{}|<>)";
        $response['status'] = false;
        $response['field'] = 'password';
    }

    if (!isset($form_data['username']) || empty($form_data['username'])) {
        $response['msg'] = "Username is required";
        $response['status'] = false;
        $response['field'] = 'username';
    }

    if (!isset($form_data['email']) || empty($form_data['email'])) {
        $response['msg'] = "Email is required";
        $response['status'] = false;
        $response['field'] = 'email';
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $response['msg'] = "Invalid email format";
        $response['status'] = false;
        $response['field'] = 'email';
    }

    if (isEmailRegistered($form_data['email'])) {
        $response['msg'] = "Email is already registered";
        $response['status'] = false;
        $response['field'] = 'email';
    }

    if (isUsernameRegistered($form_data['username'])) {
        $response['msg'] = "Username is already registered";
        $response['status'] = false;
        $response['field'] = 'username';
    }

    return $response;
}

// Function to create a new user
function createUser($data)
{
    global $db;
    $email = mysqli_real_escape_string($db, $data['email']);
    $username = mysqli_real_escape_string($db, $data['username']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $query = "INSERT INTO users (email, username, password) VALUES ('$email', '$username', '$password')";
    return mysqli_query($db, $query);
}

// Function to validate login form
function validateLoginForm($form_data)
{
    $response = array('status' => true, 'msg' => '');

    if (!isset($form_data['password']) || empty($form_data['password'])) {
        $response['msg'] = "Password is required";
        $response['status'] = false;
        $response['field'] = 'password';
    }

    if (!isset($form_data['username_email']) || empty($form_data['username_email'])) {
        $response['msg'] = "Username/email is required";
        $response['status'] = false;
        $response['field'] = 'username_email';
    }

    if ($response['status']) {
        $user = checkUser($form_data);
        if (!$user['status']) {
            $response['msg'] = "Invalid credentials";
            $response['status'] = false;
            $response['field'] = 'checkuser';
        } else {
            $response['user'] = $user['user'];
        }
    }

    return $response;
}

// Function to check user credentials
function checkUser($login_data)
{
    global $db;
    $username_email = mysqli_real_escape_string($db, $login_data['username_email']);
    $query = "SELECT * FROM users WHERE email='$username_email' OR username='$username_email'";
    $result = mysqli_query($db, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($login_data['password'], $user['password'])) {
        return array('status' => true, 'user' => $user);
    } else {
        return array('status' => false);
    }
}

// Function to logout user

    if(isset($_GET['logout'])){
    session_destroy();
    $root_path = dirname($_SERVER['PHP_SELF']);
    header("Location: $root_path/?home");
    exit();
    }


// Function to create necessary tables
function createTables()
{
    global $db;
    
    // Create categories table
    $query = "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) UNIQUE
    )";
    mysqli_query($db, $query);

    // Update jobs table
    $query = "CREATE TABLE IF NOT EXISTS jobs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        job_type VARCHAR(50),
        job_nature VARCHAR(50),
        company_name VARCHAR(255),
        company_location VARCHAR(255),
        job_location VARCHAR(255),
        salary VARCHAR(100),
        requirements TEXT,
        company_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($db, $query);

    // Create job_category table
    $query = "CREATE TABLE IF NOT EXISTS job_category (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT,
        category_id INT,
        FOREIGN KEY (job_id) REFERENCES jobs(id),
        FOREIGN KEY (category_id) REFERENCES categories(id)
    )";
    mysqli_query($db, $query);
}

// Function to get recent jobs
function getRecentJobs($limit = 6)
{
    global $db;
    $query = "SELECT j.*, GROUP_CONCAT(c.name) as categories 
              FROM jobs j 
              LEFT JOIN job_category jc ON j.id = jc.job_id 
              LEFT JOIN categories c ON jc.category_id = c.id 
              GROUP BY j.id 
              ORDER BY j.created_at DESC 
              LIMIT $limit";
    $result = mysqli_query($db, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get all categories with job count
function getCategories()
{
    global $db;
    $query = "SELECT c.id, c.name, COUNT(jc.job_id) as job_count 
              FROM categories c 
              LEFT JOIN job_category jc ON c.id = jc.category_id 
              GROUP BY c.id
              ORDER BY job_count DESC";
    $result = mysqli_query($db, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}


// Function to get an appropriate icon for each category
function getIconForCategory($categoryName)
{
    $icons = [
        'Design' => 'ri-pencil-ruler-2-fill',
        'Sales' => 'ri-bar-chart-box-fill',
        'Marketing' => 'ri-megaphone-fill',
        'Finance' => 'ri-wallet-3-fill',
        'Technology' => 'ri-computer-fill',
        'Engineering' => 'ri-tools-fill',
        'Human Resources' => 'ri-team-fill',
        'Legal' => 'ri-scales-3-fill',
        // Add more mappings as needed
    ];

    return $icons[$categoryName] ?? 'ri-briefcase-fill'; // Default icon
}

// Function to get all job categories
function getAllJobCategories()
{
    global $db;
    $query = "SELECT * FROM categories ORDER BY name ASC";
    $result = mysqli_query($db, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to add a new job
function addJob($data)
{
    global $db;
    
    $title = mysqli_real_escape_string($db, $data['title']);
    $description = mysqli_real_escape_string($db, $data['description']);
    $job_type = mysqli_real_escape_string($db, $data['job_type']);
    $job_nature = mysqli_real_escape_string($db, $data['job_nature']);
    $company_name = mysqli_real_escape_string($db, $data['company_name']);
    $company_location = mysqli_real_escape_string($db, $data['company_location']);
    $job_location = mysqli_real_escape_string($db, $data['job_location']);
    $salary = mysqli_real_escape_string($db, $data['salary']);
    $requirements = mysqli_real_escape_string($db, $data['requirements']);
    $category_id = (int)$data['category_id'];

    // Handle file upload
    $company_image = '';
    if (isset($_FILES['company_image']) && $_FILES['company_image']['error'] == 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["company_image"]["name"]);
        if (move_uploaded_file($_FILES["company_image"]["tmp_name"], $target_file)) {
            $company_image = $target_file;
        }
    }

    $query = "INSERT INTO jobs (title, description, job_type, job_nature, company_name, company_location, job_location, salary, requirements, company_image) 
              VALUES ('$title', '$description', '$job_type', '$job_nature', '$company_name', '$company_location', '$job_location', '$salary', '$requirements', '$company_image')";
    
    if (mysqli_query($db, $query)) {
        $job_id = mysqli_insert_id($db);
        $query = "INSERT INTO job_category (job_id, category_id) VALUES ($job_id, $category_id)";
        return mysqli_query($db, $query);
    }
    
    return false;
}

// Function to validate job posting form
function validateJobPostingForm($form_data)
{
    $response = array('status' => true, 'msg' => '');

    $required_fields = ['title', 'description', 'category_id', 'job_type', 'job_nature', 'company_name', 'company_location', 'job_location', 'salary', 'requirements'];

    foreach ($required_fields as $field) {
        if (!isset($form_data[$field]) || empty($form_data[$field])) {
            $response['msg'] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            $response['status'] = false;
            $response['field'] = $field;
            return $response;
        }
    }

    return $response;
}

// Function to get job details by ID
function getJobById($job_id)
{
    global $db;
    $job_id = (int)$job_id;
    $query = "SELECT j.*, GROUP_CONCAT(c.name) as categories 
              FROM jobs j 
              LEFT JOIN job_category jc ON j.id = jc.job_id 
              LEFT JOIN categories c ON jc.category_id = c.id 
              WHERE j.id = $job_id
              GROUP BY j.id";
    $result = mysqli_query($db, $query);
    return mysqli_fetch_assoc($result);
}

// Function to update a job
function updateJob($job_id, $data)
{
    global $db;
    
    $job_id = (int)$job_id;
    $title = mysqli_real_escape_string($db, $data['title']);
    $description = mysqli_real_escape_string($db, $data['description']);
    $category_id = (int)$data['category_id'];
    $job_type = mysqli_real_escape_string($db, $data['job_type']);
    $job_nature = mysqli_real_escape_string($db, $data['job_nature']);
    $company_name = mysqli_real_escape_string($db, $data['company_name']);
    $company_location = mysqli_real_escape_string($db, $data['company_location']);
    $job_location = mysqli_real_escape_string($db, $data['job_location']);
    $salary = mysqli_real_escape_string($db, $data['salary']);

    $query = "UPDATE jobs SET 
              title = '$title', 
              description = '$description', 
              job_type = '$job_type', 
              job_nature = '$job_nature', 
              company_name = '$company_name', 
              company_location = '$company_location', 
              job_location = '$job_location', 
              salary = '$salary'
              WHERE id = $job_id";
    
    if (mysqli_query($db, $query)) {
        $query = "UPDATE job_category SET category_id = $category_id WHERE job_id = $job_id";
        return mysqli_query($db, $query);
    }
    
    return false;
}

// Function to delete a job
function deleteJob($job_id)
{
    global $db;
    $job_id = (int)$job_id;
    
    // Delete from job_category first
    $query = "DELETE FROM job_category WHERE job_id = $job_id";
    mysqli_query($db, $query);
    
    // Then delete from jobs
    $query = "DELETE FROM jobs WHERE id = $job_id";
    return mysqli_query($db, $query);
}

// Function to search jobs
function searchJobs($keyword, $category_id = null, $location = null)
{
    global $db;
    
    $keyword = mysqli_real_escape_string($db, $keyword);
    $location = mysqli_real_escape_string($db, $location);
    
    $query = "SELECT j.*, GROUP_CONCAT(c.name) as categories 
              FROM jobs j 
              LEFT JOIN job_category jc ON j.id = jc.job_id 
              LEFT JOIN categories c ON jc.category_id = c.id 
              WHERE (j.title LIKE '%$keyword%' OR j.description LIKE '%$keyword%')";
    
    if ($category_id) {
        $category_id = (int)$category_id;
        $query .= " AND jc.category_id = $category_id";
    }
    
    if ($location) {
        $query .= " AND (j.job_location LIKE '%$location%' OR j.company_location LIKE '%$location%')";
    }
    
    $query .= " GROUP BY j.id ORDER BY j.created_at DESC";
    
    $result = mysqli_query($db, $query);
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

// Function to get job statistics
function getJobStatistics()
{
    global $db;
    
    $stats = array();
    
    // Total number of jobs
    $query = "SELECT COUNT(*) as total_jobs FROM jobs";
    $result = mysqli_query($db, $query);
    $stats['total_jobs'] = mysqli_fetch_assoc($result)['total_jobs'];
    
    // Jobs by type
    $query = "SELECT job_type, COUNT(*) as count FROM jobs GROUP BY job_type";
    $result = mysqli_query($db, $query);
    $stats['jobs_by_type'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Jobs by category
    $query = "SELECT c.name, COUNT(jc.job_id) as count 
              FROM categories c 
              LEFT JOIN job_category jc ON c.id = jc.category_id 
              GROUP BY c.id";
    $result = mysqli_query($db, $query);
    $stats['jobs_by_category'] = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    return $stats;
}
function getOrCreateCategory($category_name)
{
    global $db;
    $category_name = mysqli_real_escape_string($db, $category_name);
    
    // Check if the category already exists
    $query = "SELECT id FROM categories WHERE name = '$category_name'";
    $result = mysqli_query($db, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['id'];
    } else {
        // Create new category
        $query = "INSERT INTO categories (name) VALUES ('$category_name')";
        mysqli_query($db, $query);
        return mysqli_insert_id($db);
    }
}

// Initialize tables
createTables();

?>

