<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'Bdatabase.php';

$user_id = $_SESSION['user_id'];

// Query to fetch upcoming appointments (including service name)
$upcoming_appointments_query = "
    SELECT a.appointment_id, a.appointment_date, a.start_time, a.end_time, s.service_name 
    FROM Appointments a
    JOIN Services s ON a.service_id = s.service_id
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC";
$upcoming_stmt = $conn->prepare($upcoming_appointments_query);
$upcoming_stmt->bind_param("i", $user_id);
$upcoming_stmt->execute();
$upcoming_appointments = $upcoming_stmt->get_result();

// Query to fetch past appointments (including service name)
$past_appointments_query = "
    SELECT a.appointment_id, a.appointment_date, a.start_time, a.end_time, s.service_name 
    FROM Appointments a
    JOIN Services s ON a.service_id = s.service_id
    WHERE a.user_id = ? AND a.appointment_date < CURDATE()
    ORDER BY a.appointment_date DESC";
$past_stmt = $conn->prepare($past_appointments_query);
$past_stmt->bind_param("i", $user_id);
$past_stmt->execute();
$past_appointments = $past_stmt->get_result();

// Query to fetch user details
$user_details_query = "SELECT * FROM Users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_details_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_details = $user_stmt->get_result()->fetch_assoc();

// Query to fetch all available services
$services_query = "SELECT service_id, service_name FROM Services";
$servicesResult = $conn->query($services_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bookAppointment'])) {
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $start_time = $_POST['start_time'];
    $end_time = date('H:i', strtotime($start_time . ' + 1 hour')); // Assume 1-hour duration

    $bookAppointmentQuery = "INSERT INTO Appointments (user_id, service_id, appointment_date, start_time, end_time) VALUES (?, ?, ?, ?, ?)";
    $bookStmt = $conn->prepare($bookAppointmentQuery);
    $bookStmt->bind_param("iisss", $user_id, $service_id, $appointment_date, $start_time, $end_time);

    if ($bookStmt->execute()) {
        header("Location: dashboard.php");
        exit();
    } else {
        echo "<p>Error booking appointment. Please try again later.</p>";
    }
}

$promotions = "10% off on your next appointment!";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?= htmlspecialchars($user_details['full_name']); ?></h1>
            <nav>
                <ul>
                    <li><a href="logout.php" class="logout-button">Logout</a></li>
                </ul>
            </nav>
        </header>

        <div class="dashboard-content">

            <div class="section">
                <h2>Upcoming Appointments</h2>
                <?php if ($upcoming_appointments->num_rows > 0): ?>
                    <ul class="appointment-list">
                        <?php while ($appointment = $upcoming_appointments->fetch_assoc()): ?>
                            <li>
                                <p>Service: <?= htmlspecialchars($appointment['service_name']); ?></p>
                                <p>Date: <?= htmlspecialchars($appointment['appointment_date']); ?></p>
                                <p>Time: <?= htmlspecialchars($appointment['start_time']); ?> - <?= htmlspecialchars($appointment['end_time']); ?></p>
                                <button>Cancel</button> <button>Reschedule</button>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>You have no upcoming appointments.</p>
                <?php endif; ?>
            </div>

            <!-- Past Appointments -->
            <div class="section">
                <h2>Past Appointments</h2>
                <?php if ($past_appointments->num_rows > 0): ?>
                    <ul class="appointment-list">
                        <?php while ($appointment = $past_appointments->fetch_assoc()): ?>
                            <li>
                                <p>Service: <?= htmlspecialchars($appointment['service_name']); ?></p>
                                <p>Date: <?= htmlspecialchars($appointment['appointment_date']); ?></p>
                                <p>Time: <?= htmlspecialchars($appointment['start_time']); ?> - <?= htmlspecialchars($appointment['end_time']); ?></p>
                                <a href="leave_review.php?appointment_id=<?= $appointment['appointment_id']; ?>">Leave a Review</a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>You have no past appointments.</p>
                <?php endif; ?>
            </div>

            <!-- Book Appointment Section -->
            <div class="section">
                <h2>Book an Appointment</h2>
                <a href="booking.php">Book Appointment</a>
            </div>

            <!-- Promotions and Rewards -->
            <div class="section">
                <h2>Promotions and Rewards</h2>
                <p><?= htmlspecialchars($promotions); ?></p>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>