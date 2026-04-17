<?php
include 'config.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Personal Info
    $firstname   = trim($_POST['firstname']);
    $middlename  = trim($_POST['middlename']);
    $lastname    = trim($_POST['lastname']);
    $suffix      = trim($_POST['suffix']);
    $age         = intval($_POST['age']);
    $gender      = trim($_POST['gender']);
    $address     = trim($_POST['address']);
    $status      = trim($_POST['status']);

    // Account Info
    $username = trim($_POST['username']);
    $role     = trim($_POST['role']);
    $password = trim($_POST['password']);

    if ($firstname && $lastname && $age && $gender && $address && $username && $role && $password) {

        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {

            $message = "Username already exists.";

        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users 
                (firstname, middlename, lastname, suffix, age, gender, address, status, username, role, password) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param(
                "ssssissssss",
                $firstname,
                $middlename,
                $lastname,
                $suffix,
                $age,
                $gender,
                $address,
                $status,
                $username,
                $role,
                $hash
            );

            if ($stmt->execute()) {
                $success = true;
                $message = "Registration successful! Redirecting to login...";
                header("refresh:2;url=login.php");
            } else {
                $message = "Registration failed.";
            }
        }

        $stmt->close();

    } else {
        $message = "All required fields must be filled.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Family Planning System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .register-container {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 750px; 
            max-height: 95vh;
            overflow-y: auto;
            box-sizing: border-box;
        }

        .register-container img.logo {
            width: 100px;
            display: block;
            margin: 0 auto 10px;
        }

        .register-container h1 {
            text-align: center;
            color: #0f8f5f;
            margin-bottom: 5px;
            font-size: 24px;
        }

        .register-container h2 {
            text-align: center;
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }

        form .form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        form input,
        form select,
        textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            font-weight: 500;
        }

        .form-row input,
        .form-row select {
            flex: 1;
            height: 40px;
        }

        /* Add arrow for dropdowns */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg fill="gray" height="12" viewBox="0 0 24 24" width="12" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 12px 12px;
            cursor: pointer;
        }

        /* Address & Status Row */
        .address-status-row {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .address-status-row textarea {
            flex: 2; 
            height: 40px; 
            resize: none;
        }

        .address-status-row select {
            flex: 1; 
            height: 40px; 
        }

        button {
            width: 100%;
            padding: 12px;
            background: #0f8f5f;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 5px;
        }

        button:hover {
            background: #0d7a4a;
        }

        .message {
            text-align: center;
            margin-top: 12px;
            font-size: 14px;
            color: <?php echo $success ? '#22c55e' : '#ef4444'; ?>;
        }

        a {
            display: block;
            text-align: center;
            margin-top: 10px;
            color: #0f8f5f;
            text-decoration: none;
            font-size: 14px;
        }

        a:hover {
            text-decoration: underline;
        }

        @media (max-width: 780px) {
            .form-row,
            .address-status-row {
                flex-direction: column;
            }

            .address-status-row textarea,
            .address-status-row select {
                flex: 1;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <img class="logo" src="logo.png" alt="Logo">
        <h1>Family Planning System</h1>
        <h2>Register</h2>

        <form method="POST">
            <!-- Name Row -->
            <div class="form-row">
                <input type="text" name="firstname" placeholder="First Name" required>
                <input type="text" name="middlename" placeholder="Middle Name">
                <input type="text" name="lastname" placeholder="Last Name" required>
            </div>

            <!-- Suffix, Age, Gender Row -->
            <div class="form-row">
                <input type="text" name="suffix" placeholder="Suffix (Optional)">
                <input type="number" name="age" placeholder="Age" required>
                <select name="gender" required>
                    <option value="">Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <!-- Address and Status Row -->
            <div class="address-status-row">
                <textarea name="address" placeholder="Complete Address" required></textarea>
                <select name="status" required>
                    <option value="">Marital Status</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widow">Widow</option>
                    <option value="Separated">Separated</option>
                </select>
            </div>

        <div class="form-row">
            <input type="text" name="username" placeholder="Username" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="Doctor">Doctor</option>
                <option value="Nurse">Nurse</option>
                <option value="Admin">Admin</option>
                <option value="Staff">Staff</option>
            </select>
            <input type="password" name="password" placeholder="Password" required>
        </div>

            <button type="submit">Register</button>
        </form>

        <p class="message"><?php echo $message; ?></p>
        <a href="login.php">Already have an account? Login</a>
    </div>
</body>
</html>