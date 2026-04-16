<?php
require_once 'config.php';

header('Content-Type: application/json');

// Helper: Check if logged in
function checkAuth() {
    if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true){
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
        exit;
    }
}

// Helper: Log Activity
function logActivity($spot_id, $plate, $type, $cost = 0.00) {
    global $mysqli;
    $sql = "INSERT INTO activity_log (spot_id, vehicle_plate, action_type, cost) VALUES (?, ?, ?, ?)";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("sssd", $spot_id, $plate, $type, $cost);
        $stmt->execute();
        $stmt->close();
    }
}

 $action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'get_status':
        getStatus();
        break;
    case 'get_stats':
        getStats();
        break;
    case 'park':
        checkAuth(); parkVehicle(); 
        break;
    // ADD THIS NEW CASE
    case 'calculate_cost':
        checkAuth(); calculateCost(); 
        break;
    case 'exit':
        checkAuth(); exitVehicle(); 
        break;
    case 'reserve':
        checkAuth(); reserveSpot(); 
        break;
    case 'cancel_reservation':
        checkAuth(); cancelReservation(); 
        break;
    case 'find':
        checkAuth(); findVehicle(); 
        break;
    case 'get_logs':
        getLogs(); 
        break;
    case 'export_logs':
        checkAuth(); exportLogsCSV(); 
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid Action']);
}
// --- Functions ---

function getStatus() {
    global $mysqli;
    $zone = $_GET['zone'] ?? 'A';
    
    $sql = "SELECT spot_id, zone, status, vehicle_plate, entry_time FROM parking_spots WHERE zone = ?";
    $spots = [];
    
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $zone);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()){
            $spots[] = $row;
        }
        $stmt->close();
    }
    echo json_encode(['success' => true, 'data' => $spots]);
}

function getStats() {
    global $mysqli;
    $zone = $_GET['zone'] ?? 'A';
    
    $stats = ['total' => 0, 'occupied' => 0, 'available' => 0, 'revenue' => 0];

    // Spot counts
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available
            FROM parking_spots WHERE zone = ?";
            
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $zone);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stats['total'] = (int)$result['total'];
        $stats['occupied'] = (int)$result['occupied'];
        $stats['available'] = (int)$result['available'];
        $stmt->close();
    }

    // Revenue
    $resSql = "SELECT total_revenue FROM revenue WHERE id = 1";
    $resRes = $mysqli->query($resSql);
    if($row = $resRes->fetch_assoc()){
        $stats['revenue'] = (float)$row['total_revenue'];
    }

    echo json_encode(['success' => true, 'stats' => $stats]);
}

function parkVehicle() {
    global $mysqli;
    
    $spot_id = $_POST['spot_id'] ?? '';
    $plate = strtoupper(trim($_POST['plate'] ?? ''));
    $entry_time = date('Y-m-d H:i:s');

    if(empty($spot_id) || empty($plate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        return;
    }

    // Update Spot
    $sql = "UPDATE parking_spots SET status = 'occupied', vehicle_plate = ?, entry_time = ? WHERE spot_id = ? AND status = 'available'";
    
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("sss", $plate, $entry_time, $spot_id);
        
        if($stmt->execute() && $stmt->affected_rows > 0){
            logActivity($spot_id, $plate, 'IN', 0.00);
            echo json_encode(['success' => true, 'message' => "Vehicle $plate parked in $spot_id"]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Spot is not available or does not exist.']);
        }
        $stmt->close();
    }
}
function calculateCost() {
    global $mysqli;
    $spot_id = $_POST['spot_id'] ?? '';
    $rate_per_hour = 50.00;

    // Get current spot info
    $sql = "SELECT vehicle_plate, entry_time FROM parking_spots WHERE spot_id = ? AND status = 'occupied'";
    $spot_info = null;
    
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $spot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $spot_info = $result->fetch_assoc();
        $stmt->close();
    }

    if(!$spot_info) {
        echo json_encode(['success' => false, 'message' => 'Spot not found or not occupied.']);
        return;
    }

    // Calculate Cost (Read-only)
    $entry = new DateTime($spot_info['entry_time']);
    $exit = new DateTime();
    $diff = $entry->diff($exit);
    
    $total_seconds = $exit->getTimestamp() - $entry->getTimestamp();
    $hours = $total_seconds / 3600;
    
    $cost = max($rate_per_hour, ceil($hours * $rate_per_hour)); 
    $duration_str = $diff->format('%h H %i M');

    // Return info, but DO NOT update database yet
    echo json_encode([
        'success' => true, 
        'cost' => $cost, 
        'duration' => $duration_str,
        'plate' => $spot_info['vehicle_plate'],
        'message' => 'Calculated.'
    ]);
}
function exitVehicle() {
    global $mysqli;
    $spot_id = $_POST['spot_id'] ?? '';
    
    // CHANGE: Updated rate to 167.00 (Equivalent to approx $2.00 USD)
    $rate_per_hour = 50.00; 

    // Get current spot info
    $sql = "SELECT vehicle_plate, entry_time FROM parking_spots WHERE spot_id = ? AND status = 'occupied'";
    $spot_info = null;
    
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $spot_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $spot_info = $result->fetch_assoc();
        $stmt->close();
    }

    if(!$spot_info) {
        echo json_encode(['success' => false, 'message' => 'Spot not found or not occupied.']);
        return;
    }

    // Calculate Cost
    $entry = new DateTime($spot_info['entry_time']);
    $exit = new DateTime();
    $diff = $entry->diff($exit);
    
    // Calculate total hours (including days)
    $total_seconds = $exit->getTimestamp() - $entry->getTimestamp();
    $hours = $total_seconds / 3600;
    
    // Logic: Rate/hour, rounded up, minimum 1 hour charge
    $cost = max($rate_per_hour, ceil($hours * $rate_per_hour)); 

    // Update Spot to Available
    $updateSql = "UPDATE parking_spots SET status = 'available', vehicle_plate = NULL, entry_time = NULL WHERE spot_id = ?";
    if($stmt = $mysqli->prepare($updateSql)){
        $stmt->bind_param("s", $spot_id);
        $stmt->execute();
        $stmt->close();
    }

    // Update Revenue
    $mysqli->query("UPDATE revenue SET total_revenue = total_revenue + $cost WHERE id = 1");

    // Log Activity
    logActivity($spot_id, $spot_info['vehicle_plate'], 'OUT', $cost);

    // Format duration for frontend
    $duration_str = $diff->format('%h H %i M');

    echo json_encode([
        'success' => true, 
        'cost' => $cost, 
        'duration' => $duration_str,
        'message' => 'Payment processed.'
    ]);
}

function reserveSpot() {
    global $mysqli;
    $spot_id = $_POST['spot_id'] ?? '';

    $sql = "UPDATE parking_spots SET status = 'reserved' WHERE spot_id = ? AND status = 'available'";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $spot_id);
        if($stmt->execute() && $stmt->affected_rows > 0){
            logActivity($spot_id, NULL, 'RESERVE', 0.00);
            echo json_encode(['success' => true, 'message' => "Spot $spot_id reserved."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cannot reserve this spot.']);
        }
        $stmt->close();
    }
}

function cancelReservation() {
    global $mysqli;
    $spot_id = $_POST['spot_id'] ?? '';

    $sql = "UPDATE parking_spots SET status = 'available' WHERE spot_id = ? AND status = 'reserved'";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $spot_id);
        if($stmt->execute() && $stmt->affected_rows > 0){
            logActivity($spot_id, NULL, 'CANCEL', 0.00);
            echo json_encode(['success' => true, 'message' => 'Reservation cancelled.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error cancelling.']);
        }
        $stmt->close();
    }
}

function findVehicle() {
    global $mysqli;
    $plate = strtoupper(trim($_GET['plate'] ?? ''));

    $sql = "SELECT spot_id, zone, entry_time FROM parking_spots WHERE vehicle_plate = ?";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("s", $plate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()){
            echo json_encode(['success' => true, 'spot' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Vehicle not found.']);
        }
        $stmt->close();
    }
}

function getLogs() {
    global $mysqli;
    // Get last 10 logs

    $sql = "SELECT * FROM activity_log ORDER BY id DESC LIMIT 10";
    $result = $mysqli->query($sql);
    
    $logs = [];
    while($row = $result->fetch_assoc()){
        // Format data to match frontend expectations
        $type = strtolower($row['action_type']);
        $msg = "";
        
        if($type === 'in') $msg = "IN: {$row['vehicle_plate']} at {$row['spot_id']}";
        // CHANGE: Updated currency symbol from $ to ₹
        else if($type === 'out') $msg = "OUT: {$row['vehicle_plate']} (₹ {$row['cost']})";
        else if($type === 'reserve') $msg = "Reserved: {$row['spot_id']}";
        else $msg = "Cancelled: {$row['spot_id']}";

        $logs[] = [
            'msg' => $msg,
            'type' => $type,
            'time' => date('h:i A', strtotime($row['timestamp']))
        ];
    }
    
    echo json_encode(['success' => true, 'logs' => $logs]);
}
function exportLogsCSV() {
    global $mysqli;
    
    // Filename with current date
    $filename = 'smartpark_logs_' . date('Y-m-d') . '.csv';
    
    // Headers to force download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV Header Row
    fputcsv($output, ['ID', 'Spot ID', 'Vehicle Plate', 'Action', 'Cost (₹)', 'Timestamp']);
    
    // Get all logs
    $sql = "SELECT * FROM activity_log ORDER BY id DESC";
    $result = $mysqli->query($sql);
    
    while($row = $result->fetch_assoc()){
        // Write data row
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}
?>