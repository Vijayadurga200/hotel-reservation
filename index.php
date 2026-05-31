<?php
require 'db.php';
require 'booking_logic.php';

$message = '';
$availableRoomsCount = 0;
$messageType = '';

// Handle booking request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_rooms'])) {
    $roomCount = (int)$_POST['room_count'];
    $guestName = $_POST['guest_name'] ?? 'Guest';
    
    if ($roomCount < 1 || $roomCount > 5) {
        $message = "Error: You can book between 1 and 5 rooms.";
    } else {
        $result = bookRooms($pdo, $roomCount, $guestName);
        $message = $result['message'];
    }
}

// Reset all bookings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_booking'])) {
    $stmt = $pdo->query("UPDATE rooms SET is_occupied = FALSE, guest_name = NULL");
    $stmt = $pdo->query("DELETE FROM bookings");
    $message = "All bookings have been reset!";
}

// Generate random occupancy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['random_occupancy'])) {

    $pdo->query("UPDATE rooms SET is_occupied = 0, guest_name = NULL");

    $stmt = $pdo->query("SELECT id FROM rooms");
    $roomIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    shuffle($roomIds);

    $occupiedCount = rand(20, 30);
    $selectedRooms = array_slice($roomIds, 0, $occupiedCount);

    $updateStmt = $pdo->prepare(
        "UPDATE rooms SET is_occupied = 1, guest_name = ? WHERE id = ?"
    );

    foreach ($selectedRooms as $roomId) {
        $guestName = 'Guest_' . rand(100, 999);
        $updateStmt->execute([$guestName, $roomId]);
    }

    $message = "Random occupancy generated! ($occupiedCount rooms occupied)";
    $messageType = "success";
}

// Get all rooms for display
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY floor_number, room_position");
$allRooms = $stmt->fetchAll();
$totalRooms = count($allRooms);

$occupiedRooms = count(array_filter($allRooms, function ($room) {
    return $room['is_occupied'] == 1;
}));

$availableRoomsCount = $totalRooms - $occupiedRooms;

// Group rooms by floor
$floors = [];
foreach ($allRooms as $room) {
    $floors[$room['floor_number']][] = $room;
}
$totalRooms = count($allRooms);

$occupiedRooms = count(
    array_filter($allRooms, function ($room) {
        return $room['is_occupied'];
    })
);

$availableRoomsCount = $totalRooms - $occupiedRooms;

if (!empty($message)) {
    $messageType = stripos($message, 'error') !== false
        ? 'error'
        : 'success';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Room Reservation System</title>
    <style>
        /* ================================================================= */
        /* CSS STYLES - COMPLETE */
        /* ================================================================= */
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        
        header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        header p { opacity: 0.9; font-size: 1.1rem; }
        
        /* Stats Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stat-card h3 {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
        }
        
        .stat-card.available .value { color: #4ade80; }
        .stat-card.occupied .value { color: #f87171; }
        
        /* Controls Section */
        .controls {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        
        .controls h2 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group { display: flex; flex-direction: column; }
        
        .form-group label {
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input {
            padding: 15px 20px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            font-size: 1.1rem;
            background: rgba(255,255,255,0.1);
            color: white;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255,255,255,0.15);
        }
        
        .form-group input::placeholder { color: rgba(255,255,255,0.5); }
        
        /* Buttons */
        .buttons { display: flex; gap: 15px; flex-wrap: wrap; }
        
        .btn {
            padding: 15px 35px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(56, 239, 125, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
        }
        
        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(235, 51, 73, 0.4);
        }
        
        /* Message Box */
        .message {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .message.success {
            background: rgba(74, 222, 128, 0.2);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.5);
        }
        
        .message.error {
            background: rgba(248, 113, 113, 0.2);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.5);
        }
        
        /* Building Visualization */
        .building {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 30px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .building h2 {
            color: white;
            margin-bottom: 25px;
            font-size: 1.5rem;
        }
        
        .building-structure {
            display: flex;
            gap: 20px;
        }
        
        .stairs-lift {
            width: 80px;
            background: linear-gradient(180deg, #4b5563 0%, #1f2937 100%);
            color: white;
            padding: 15px 10px;
            text-align: center;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 10px;
        }
        
        .stairs-lift-icon {
            font-size: 1.8rem;
        }
        
        .stairs-lift span {
            font-size: 0.75rem;
            opacity: 0.8;
        }
        
        .floors-container {
            flex: 1;
            display: flex;
            flex-direction: column-reverse;
            gap: 8px;
        }
        
        .floor {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 12px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .floor-label {
            width: 50px;
            color: rgba(255,255,255,0.7);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .floor-rooms {
            flex: 1;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        .room {
            width: 60px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-size: 0.75rem;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .room:hover {
            transform: scale(1.15);
            z-index: 10;
        }
        
        .room.available {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.3);
        }
        
        .room.occupied {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 2px 10px rgba(239, 68, 68, 0.3);
        }
        
        .room-number {
            font-weight: bold;
            font-size: 0.85rem;
        }
        
        .room-guest {
            font-size: 0.6rem;
            opacity: 0.8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 50px;
        }
        
        .floor-info {
            margin-left: auto;
            color: rgba(255,255,255,0.6);
            font-size: 0.85rem;
            white-space: nowrap;
        }
        
        /* Legend */
        .legend {
            display: flex;
            gap: 30px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.8);
        }
        
        .legend-box {
            width: 25px;
            height: 20px;
            border-radius: 5px;
        }
        
        .legend-box.available {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .legend-box.occupied {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        /* Instructions */
        .instructions {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
            margin-top: 25px;
        }
        
        .instructions h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .instructions ul {
            color: rgba(255,255,255,0.7);
            padding-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 10px;
            line-height: 1.6;
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
            
            .building-structure {
                flex-direction: column;
            }
            
            .stairs-lift {
                width: 100%;
                flex-direction: row;
                justify-content: center;
                gap: 30px;
            }
            
            .room {
                width: 50px;
                height: 45px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header>
            <h1>🏨 Hotel Room Reservation System</h1>
            <p>97 Rooms • 10 Floors • Smart Booking Algorithm</p>
        </header>
        
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total Rooms</h3>
                <div class="value"><?php echo $totalRooms; ?></div>
            </div>
            <div class="stat-card available">
                <h3>Available</h3>
                <div class="value"><?php echo $availableRoomsCount; ?></div>
            </div>
            <div class="stat-card occupied">
                <h3>Occupied</h3>
                <div class="value"><?php echo $occupiedRooms; ?></div>
            </div>
            <div class="stat-card">
                <h3>Occupancy %</h3>
                <div class="value"><?php echo $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0; ?>%</div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="controls">
            <h2>📋 Book New Rooms</h2>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Guest Name</label>
                        <input type="text" name="guest_name" placeholder="Enter guest name" required>
                    </div>
                    <div class="form-group">
                        <label>Number of Rooms (1-5)</label>
                        <input type="number" name="room_count" min="1" max="5" placeholder="1-5 rooms" required>
                    </div>
                    <div class="form-group" style="justify-content: flex-end;">
                        <label>&nbsp;</label>
                        <button type="submit" name="book_rooms" class="btn btn-primary">
                            📅 Book Rooms
                        </button>
                    </div>
                </div>
                
                <div class="buttons">
                    <button type="submit" name="random_occupancy" class="btn btn-success">
                        🎲 Generate Random Occupancy
                    </button>
                    <button type="submit" name="reset_booking" class="btn btn-danger">
                        🔄 Reset All Bookings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Building Visualization -->
        <div class="building">
            <h2>🏢 Building Layout</h2>
            
            <div class="building-structure">
                <!-- Stairs and Lift -->
                <div class="stairs-lift">
                    <div class="stairs-lift-icon">🪜</div>
                    <span>Stairs</span>
                    <div class="stairs-lift-icon">🛗</div>
                    <span>Lift</span>
                </div>
                
                <!-- Floors -->
                <div class="floors-container">
                    <?php foreach ($floors as $floorNum => $rooms): ?>
                        <?php 
                        $occupied = count(array_filter($rooms, fn($r) => $r['is_occupied']));
                        $available = count($rooms) - $occupied;
                        ?>
                        <div class="floor">
                            <div class="floor-label">Floor <?php echo $floorNum; ?></div>
                            <div class="floor-rooms">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="room <?php echo $room['is_occupied'] ? 'occupied' : 'available'; ?>" 
                                         title="<?php echo $room['is_occupied'] ? 'Occupied by: ' . $room['guest_name'] : 'Available'; ?>">
                                        <div class="room-number"><?php echo $room['room_number']; ?></div>
                                        <?php if ($room['is_occupied'] && $room['guest_name']): ?>
                                            <div class="room-guest"><?php echo htmlspecialchars($room['guest_name']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="floor-info">
                                <?php echo $available; ?>/<?php echo count($rooms); ?> available
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Legend -->
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-box available"></div>
                    <span>Available</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box occupied"></div>
                    <span>Occupied</span>
                </div>
                <div class="legend-item">
                    <span>🪜 Left side = Stairs/Lift (nearest rooms)</span>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="instructions">
            <h3>📖 Booking Rules & Logic</h3>
            <ul>
                <li><strong>Max 5 rooms:</strong> A single guest can book up to 5 rooms at a time</li>
                <li><strong>Same floor priority:</strong> System tries to book all rooms on the same floor first</li>
                <li><strong>Travel time minimization:</strong> If spanning floors, minimizes total travel time between first and last room</li>
                <li><strong>Horizontal travel:</strong> 1 minute per adjacent room (left to right)</li>
                <li><strong>Vertical travel:</strong> 2 minutes per floor (using stairs/lift)</li>
                <li><strong>Floor preference:</strong> Lower floors are preferred when travel time is equal</li>
                <li><strong>Room proximity:</strong> Rooms closest to stairs/lift are selected first</li>
            </ul>
        </div>
    </div>
</body>
</html>