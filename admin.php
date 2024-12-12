<?php
include 'Bdatabase.php';
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}


$appointmentQuery = "
    SELECT 
        Appointments.appointment_id, 
        Users.full_name AS user_name, 
        Appointments.therapist_id, 
        Appointments.service_id, 
        Appointments.appointment_date, 
        Appointments.start_time, 
        Appointments.end_time, 
        Appointments.status 
    FROM Appointments 
    JOIN Users ON Appointments.user_id = Users.user_id
";
$appointmentResult = $conn->query($appointmentQuery);

$serviceQuery = "SELECT * FROM Services";
$serviceResult = $conn->query($serviceQuery);

$paymentQuery = "SELECT * FROM Payments";
$paymentResult = $conn->query($paymentQuery);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new service
    if (isset($_POST['addService'])) {
        $service_name = $_POST['service_name'];
        $description = $_POST['description'];
        $duration = $_POST['duration'];
        $price = $_POST['price'];
        $image_path = NULL;

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_name = $_FILES['image']['name'];
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
            $image_path = 'uploads/' . uniqid('service_', true) . '.' . $image_ext;

            // Upload image to server
            if (!move_uploaded_file($image_tmp, $image_path)) {
                echo "Error uploading image.<br>";
            }
        }

        // Insert new service into database
        $insertQuery = "INSERT INTO Services (service_name, description, duration, price, image_path, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ssdis', $service_name, $description, $duration, $price, $image_path);
        $stmt->execute();

        // Redirect after inserting
        header('Location: admin.php');
        exit();
    }

    // Update service
    if (isset($_POST['updateService'])) {
        $service_id = $_POST['service_id'];
        $service_name = $_POST['service_name'];
        $description = $_POST['description'];
        $duration = $_POST['duration'];
        $price = $_POST['price'];
        $image_path = $_POST['current_image']; 

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image_name = $_FILES['image']['name'];
            $image_tmp = $_FILES['image']['tmp_name'];
            $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
            $image_path = 'uploads/' . uniqid('service_', true) . '.' . $image_ext;

            // Upload image to server
            if (!move_uploaded_file($image_tmp, $image_path)) {
                echo "Error uploading image.<br>";
            }
        }

        // Update service in the database
        $updateQuery = "UPDATE Services SET service_name = ?, description = ?, duration = ?, price = ?, image_path = ?, updated_at = NOW() WHERE service_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('ssdisi', $service_name, $description, $duration, $price, $image_path, $service_id);
        $stmt->execute();

        // Redirect after updating
        header('Location: admin.php');
        exit();
    }

    // Delete service
    if (isset($_POST['deleteService'])) {
        $service_id = $_POST['service_id'];

        // Delete service from database
        $deleteQuery = "DELETE FROM Services WHERE service_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param('i', $service_id);
        $stmt->execute();

        // Redirect after deletion
        header('Location: admin.php');
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Other existing form handling (add, update, approve, cancel)

        // Delete appointment
        if (isset($_POST['deleteAppointment'])) {
            $appointment_id = $_POST['appointment_id'];

            // Delete appointment from the database
            $deleteQuery = "DELETE FROM Appointments WHERE appointment_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param('i', $appointment_id);
            $stmt->execute();

            // Redirect after deletion
            header('Location: admin.php');
            exit();
        }
    }
    if (isset($_POST['approveBooking'])) {
        $appointmentId = $_POST['appointment_id'];
        $updateQuery = "UPDATE Appointments SET status = 'confirmed' WHERE appointment_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('i', $appointmentId);
        $stmt->execute();
        header('Location: admin.php');
        exit();
    } elseif (isset($_POST['cancelBooking'])) {
        $appointmentId = $_POST['appointment_id'];
        $updateQuery = "UPDATE Appointments SET status = 'canceled' WHERE appointment_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('i', $appointmentId);
        $stmt->execute();
        header('Location: admin.php');
        exit();
    } elseif (isset($_POST['logout'])) {
        session_destroy();
        header('Location: homepage.html');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #2c3e50;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        header h1 {
            font-size: 36px;
        }

        form {
            display: inline-block;
        }

        button {
            padding: 10px 20px;
            background-color: #3498db;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        button:focus {
            outline: none;
        }

        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #2980b9;
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table tr:hover {
            background-color: #ecf0f1;
        }

        section {
            margin: 20px;
        }

        section h2 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        section h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #34495e;
        }

        /* Add/Edit Service Form Styles */
        form input[type="text"], 
        form input[type="number"], 
        form textarea, 
        form input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #ecf0f1;
            font-size: 16px;
        }

        form textarea {
            height: 100px;
        }

        form input[type="file"] {
            padding: 8px;
        }

        form button {
            width: 100%;
            background-color: #27ae60;
            font-size: 18px;
            padding: 15px;
        }

        form button:hover {
            background-color: #2ecc71;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header h1 {
                font-size: 28px;
            }

            table th, table td {
                padding: 8px;
            }

            section h2 {
                font-size: 24px;
            }

            form input[type="text"], 
            form input[type="number"], 
            form textarea, 
            form input[type="file"] {
                font-size: 14px;
            }

            form button {
                font-size: 16px;
            }
        }

    </style>
    <header>
        <h1>Admin Dashboard</h1>
        <form action="admin.php" method="POST" style="float:right;">
            <button type="submit" name="logout">Logout</button>
        </form>
    </header>

    <main>
        <!-- Bookings Section -->
        <section id="manageBookings">
            <h2>Manage Appointments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Appointment ID</th>
                        <th>Client</th>
                        <th>Therapist ID</th>
                        <th>Service ID</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $appointmentResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['appointment_id'] ?></td>
                            <td><?= $row['user_name'] ?></td>
                            <td><?= $row['therapist_id'] ?></td>
                            <td><?= $row['service_id'] ?></td>
                            <td><?= $row['appointment_date'] ?></td>
                            <td><?= $row['start_time'] ?></td>
                            <td><?= $row['end_time'] ?></td>
                            <td><?= $row['status'] ?></td>
                            <td>
                                <form action="admin.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <button type="submit" name="approveBooking">Approve</button>
                                </form>
                                <form action="admin.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <button type="submit" name="cancelBooking">Cancel</button>
                                </form>
                                <!-- Delete Button -->
                                <form action="admin.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <button type="submit" name="deleteAppointment">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>


        <!-- Services Section -->
        <section id="manageServices">
            <h2>Manage Services</h2>
            <table>
                <thead>
                    <tr>
                        <th>Service ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($service = $serviceResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= $service['service_id'] ?></td>
                            <td><?= $service['service_name'] ?></td>
                            <td><?= $service['description'] ?></td>
                            <td><?= $service['price'] ?></td>
                            <td><?= $service['duration'] ?></td>
                            <td>
                                <!-- Update Button -->
                                <button type="button" onclick="editService(<?= $service['service_id'] ?>, '<?= $service['service_name'] ?>', '<?= $service['description'] ?>', <?= $service['duration'] ?>, <?= $service['price'] ?>, '<?= $service['image_path'] ?>')">Edit</button>
                                
                                <!-- Delete Function -->
                                <form action="admin.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
                                    <button type="submit" name="deleteService">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <form action="admin.php" method="POST" enctype="multipart/form-data">
                <h3>Add New Service</h3>
                <label for="service_name">Service Name:</label>
                <input type="text" name="service_name" id="service_name" required><br>

                <label for="description">Description:</label>
                <textarea name="description" id="description" required></textarea><br>

                <label for="duration">Duration (in minutes):</label>
                <input type="number" name="duration" id="duration" required><br>

                <label for="price">Price:</label>
                <input type="text" name="price" id="price" required><br>

                <label for="image">Image (optional):</label>
                <input type="file" name="image" id="image_path"><br>

                <button type="submit" name="addService">Add Service</button>
            </form>

            
            <div id="updateServiceForm" style="display:none;">
                <h3>Update Service</h3>
                <form action="admin.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="service_id" id="update_service_id">
                    <input type="hidden" name="current_image" id="current_image">

                    <label for="update_service_name">Service Name:</label>
                    <input type="text" name="service_name" id="update_service_name" required><br>

                    <label for="update_description">Description:</label>
                    <textarea name="description" id="update_description" required></textarea><br>

                    <label for="update_duration">Duration (in minutes):</label>
                    <input type="number" name="duration" id="update_duration" required><br>

                    <label for="update_price">Price:</label>
                    <input type="text" name="price" id="update_price" required><br>

                    <label for="update_image">Image (optional):</label>
                    <input type="file" name="image" id="update_image"><br>

                    <button type="submit" name="updateService">Update Service</button>
                </form>
            </div>
        </section>

        <section id="paymentReports">
            <h2>Payments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>User</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $paymentResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= $payment['payment_id'] ?></td>
                            <td><?= $payment['user_id'] ?></td>
                            <td><?= $payment['amount'] ?></td>
                            <td><?= $payment['status'] ?></td>
                            <td><?= $payment['date'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>

    <script>
        function editService(service_id, service_name, description, duration, price, image_path) {
            document.getElementById('updateServiceForm').style.display = 'block';
            document.getElementById('update_service_id').value = service_id;
            document.getElementById('update_service_name').value = service_name;
            document.getElementById('update_description').value = description;
            document.getElementById('update_duration').value = duration;
            document.getElementById('update_price').value = price;
            document.getElementById('current_image').value = image_path;
        }
    </script>
</body>
</html>