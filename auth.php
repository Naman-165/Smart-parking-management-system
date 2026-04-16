<?php
// This file handles LOGIN, SIGNUP, LOGOUT, and SESSION CHECKS

require_once 'config.php';

header('Content-Type: application/json');

 $action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'login':
        handleLogin();
        break;
    case 'signup':
        handleSignup();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'check_session':
        checkSession();
        break;
    default:
        // If no action, just check if they are logged in
        checkSession();
        break;
}

// --- Functions ---
function handleLogin() {
    global $mysqli;
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
        return;
    }

    $sql = "SELECT id, name, email, password FROM users WHERE email = ?";
    
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $email);
        
        if($stmt->execute()){
            $stmt->store_result();
            
            if($stmt->num_rows == 1){
                // FIX: Initialize variables as empty strings/0 before binding
                // This prevents the "Found null" error message in the editor
                $id = 0;
                $name = '';
                $db_email = '';
                $hashed_password = ''; // Defined as string now!

                $stmt->bind_result($id, $name, $db_email, $hashed_password);
                
                if($stmt->fetch()){
                    // Verify password
                    if(password_verify($password, $hashed_password)){
                        $_SESSION['loggedin'] = true;
                        $_SESSION['id'] = $id;
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $db_email;
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Login successful.',
                            'user' => ['name' => $name, 'email' => $db_email]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Invalid password.']);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No account found.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
        }
        $stmt->close();
    }
}


function handleSignup() {
    global $mysqli;

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm'] ?? '');

    if(empty($name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
        return;
    }
    
    if($password !== $confirm) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        return;
    }

    $sql_check = "SELECT id FROM users WHERE email = ?";
    if($stmt = $mysqli->prepare($sql_check)){
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0){
            echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            $stmt->close();
            return;
        }
        $stmt->close();
    }

    $sql_insert = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    if($stmt = $mysqli->prepare($sql_insert)){
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("sss", $name, $email, $hashed_password);
        
        if($stmt->execute()){
            echo json_encode(['success' => true, 'message' => 'Account created!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Signup failed.']);
        }
        $stmt->close();
    }
}

function handleLogout() {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out.']);
}

function checkSession() {
    if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true){
        echo json_encode([
            'logged_in' => true, 
            'user' => [
                'name' => $_SESSION['name'] ?? 'User', 
                'email' => $_SESSION['email'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
}
?>