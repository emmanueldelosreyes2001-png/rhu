<?php include 'config.php'; ?>

<?php
if(isset($_POST['save_service'])){
    if(!empty($_POST['patient_id']) && !empty($_POST['service_date']) && !empty($_POST['service_time'])){

        $service_datetime = $_POST['service_date'] . ' ' . $_POST['service_time'];

        $check = $conn->prepare("SELECT COUNT(*) as count FROM service_records 
                                 WHERE patient_id=? AND DATE(service_date)=? AND service_type=? AND method=?");
        $check->bind_param("ssss", $_POST['patient_id'], $_POST['service_date'], $_POST['service_type'], $_POST['method']);
        $check->execute();
        $checkResult = $check->get_result()->fetch_assoc();

        if($checkResult['count'] > 0){
            echo "<script>alert('A similar service record for this patient on this date already exists. Cannot proceed.');</script>";
        } else {
            $stmt = $conn->prepare("INSERT INTO service_records 
                (patient_id, service_date, service_type, method, provider, notes) 
                VALUES (?,?,?,?,?,?)");

            $stmt->bind_param("ssssss",
                $_POST['patient_id'],
                $service_datetime,
                $_POST['service_type'],
                $_POST['method'],
                $_POST['provider'],
                $_POST['notes']
            );

            $stmt->execute();

            echo "<script>alert('Service record added successfully!'); window.location='services.php';</script>";
        }

    } else {
        echo "<script>alert('Patient, Service Date, and Service Time are required.');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Service Records - Family Planning System</title>

<style>
body { margin:0; font-family:Arial; background:#f5f7fa; }

.header { 
    background:#0f8f5f; 
    color:white; 
    padding:20px; 
    position: relative; 
}
.header h1 { margin:0; }
.header h2, .header p { margin:5px 0 0; font-size:14px; }

.menu {
    position: absolute;
    right: 20px;
    top: 25px;
    font-size: 24px;
    cursor: pointer;
    user-select: none;
}
.dropdown {
    display: none;
    position: absolute;
    right: 20px;
    top: 55px;
    background: white;
    color: black;
    min-width: 120px;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    z-index: 100;
}
.dropdown a {
    color: black;
    text-decoration: none;
    display: block;
    padding: 10px 15px;
}
.dropdown a:hover { background:#f0f0f0; }

.nav {
    background:white;
    padding:12px 20px;
    display:flex;
    gap:25px;
    border-bottom:1px solid #ddd;
}
.nav a {
    text-decoration:none;
    color:#555;
    padding-bottom:5px;
    border-bottom:2px solid transparent;
    transition: all 0.2s;
}
.nav a.active { color:#0f8f5f; border-bottom:2px solid #0f8f5f; }

.container { padding:20px; }
.top-bar { display:flex; justify-content:flex-end; margin-bottom:15px; }
button { background:#0f8f5f; color:white; border:none; padding:8px 15px; border-radius:6px; cursor:pointer; }

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
  margin-top: 20px;
}

.card {
  background: white;
  padding: 15px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.card h4 {
  margin: 0;
  font-size: 14px;
  color: #777;
}

.card h2 {
  margin: 5px 0 0;
  color: #0f8f5f;
  font-size: 28px;
}

.card .icon {
  font-size: 32px;
  color: #0f8f5f;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #e0f7f1;
}

.search { padding:8px; width:250px; border:1px solid #ccc; border-radius:6px; margin-bottom:15px; }


.table-box { background:white; border-radius:10px; padding:10px; margin-top:25px; }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; border-bottom:1px solid #eee; text-align:left; }
th { background:#f9fafb; }
.badge { padding:5px 10px; border-radius:6px; font-size:12px; color:white; }
.scheduled { background:#3b82f6; }
.completed { background:#22c55e; }
.cancelled { background:#ef4444; }

.modal { 
    display:none; 
    position:fixed; 
    inset:0;
    background:rgba(0,0,0,0.55);
    backdrop-filter: blur(6px);
    justify-content:center; 
    align-items:center; 
    z-index:1000;
}

.modal-content { 
    background:#ffffff;
    width:650px;
    max-width:95%;
    border-radius:18px;
    box-shadow:0 25px 60px rgba(0,0,0,0.25);
    animation: fadeSlide 0.3s ease;
    overflow:hidden;
}

.modal-header {
    background:linear-gradient(135deg,#0f8f5f,#0c6e49);
    padding:20px 30px;
    color:white;
}

.modal-header h3 {
    margin:0;
    font-size:20px;
    font-weight:600;
    display:flex;
    align-items:center;
    gap:10px;
}

.modal-body {
    padding:30px;
    background:#f9fafb;
}

.modal-footer {
    padding:20px 30px;
    display:flex;
    justify-content:flex-end;
    gap:15px;
    background:white;
    border-top:1px solid #eee;
}

.form-grid {
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:20px;
}

.form-group {
    display:flex;
    flex-direction:column;
    gap:6px;
}

.form-group.full {
    grid-column: span 2;
}

label {
    font-size:13px;
    font-weight:600;
    color:#374151;
}

input, select, textarea {
    padding:12px 14px;
    border:1px solid #d1d5db;
    border-radius:10px;
    font-size:14px;
    background:white;
    transition:0.2s ease;
}

input:focus, select:focus, textarea:focus {
    border-color:#0f8f5f;
    box-shadow:0 0 0 3px rgba(15,143,95,0.15);
    outline:none;
}

.btn-secondary {
    background:#e5e7eb;
    color:#333;
    border:none;
    padding:10px 18px;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
}

.btn-primary {
    background:linear-gradient(135deg,#0f8f5f,#0c6e49);
    color:white;
    border:none;
    padding:10px 20px;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
    box-shadow:0 8px 20px rgba(15,143,95,0.3);
    transition:0.2s;
}

.btn-primary:hover {
    transform:translateY(-2px);
    box-shadow:0 12px 25px rgba(15,143,95,0.4);
}

.logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
}

@keyframes fadeSlide {
    from {opacity:0; transform:translateY(-15px);}
    to {opacity:1; transform:translateY(0);}
}
</style>
</head>
<body>

<div class="header" style="display:flex; align-items:center; gap:15px;">
    <img class="logo" src="logo.png" alt="Logo">
    <div>
        <h1 style="margin:0;">Family Planning Monitoring System</h1>
        <p style="margin:5px 0 0; font-size:14px;">Rural Health Unit - Dumingag, Zamboanga del Sur</p>
    </div>
    
    <div class="menu" onclick="toggleDropdown()" style="margin-left:auto; font-size:24px; cursor:pointer;">☰</div>
    <div class="dropdown" id="dropdownMenu">
        <a href="logout.php">Logout</a>
    </div>
</div>

<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="nav">
    <a href="index.php" class="<?= $currentPage == 'index.php' ? 'active' : '' ?>">
        <i class="fas fa-tachometer-alt"></i>Dashboard
    </a>
    <a href="patients.php" class="<?= $currentPage == 'patients.php' ? 'active' : '' ?>">
        <i class="fas fa-users"></i>Patients
    </a>
    <a href="appointments.php" class="<?= $currentPage == 'appointments.php' ? 'active' : '' ?>">
        <i class="fas fa-calendar-check"></i>Appointments
    </a>
    <a href="services.php" class="<?= $currentPage == 'services.php' ? 'active' : '' ?>">
        <i class="fas fa-concierge-bell"></i>Services
    </a>
    <a href="inventory.php" class="<?= $currentPage == 'inventory.php' ? 'active' : '' ?>">
        <i class="fas fa-capsules"></i>Inventory
    </a>
    <a href="reports.php" class="<?= $currentPage == 'reports.php' ? 'active' : '' ?>">
        <i class="fas fa-file-alt"></i>Reports
    </a>
</div>
<div class="container">

<h2>Service Records</h2>
<p>Track family planning services rendered</p>

<div class="top-bar">
<button onclick="openModal()">+ Add Service Record</button>
</div>

<?php
$total = $conn->query("SELECT COUNT(*) as t FROM service_records")->fetch_assoc()['t'];
$thisMonth = $conn->query("SELECT COUNT(*) as t FROM service_records WHERE MONTH(service_date)=MONTH(CURDATE()) AND YEAR(service_date)=YEAR(CURDATE())")->fetch_assoc()['t'];
$thisWeek = $conn->query("SELECT COUNT(*) as t FROM service_records WHERE YEARWEEK(service_date,1)=YEARWEEK(CURDATE(),1)")->fetch_assoc()['t'];
?>


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

<div class="cards">
  <div class="card">
    <div class="icon"><i class="fas fa-layer-group"></i></div>
    <div>
      <h4>Total Services</h4>
      <h2><?php echo $total; ?></h2>
    </div>
  </div>
  <div class="card">
    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
    <div>
      <h4>This Month</h4>
      <h2><?php echo $thisMonth; ?></h2>
    </div>
  </div>
  <div class="card">
    <div class="icon"><i class="fas fa-calendar-week"></i></div>
    <div>
      <h4>This Week</h4>
      <h2><?php echo $thisWeek; ?></h2>
    </div>
  </div>
</div>

<input id="searchInput" class="search" placeholder="Search service records...">

<div class="table-box">
<table id="dataTable">
<tr>
<th>ID</th>
<th>Patient</th>
<th>Date</th>
<th>Service Type</th>
<th>Method</th>
<th>Provider</th>
<th>Notes</th>
</tr>

<?php
$result = $conn->query("
SELECT sr.*, p.name 
FROM service_records sr
LEFT JOIN patients p ON sr.patient_id=p.patient_id
ORDER BY service_date DESC
");

while($row = $result->fetch_assoc()):
?>
<tr>
<td><?php echo $row['service_record_id']; ?></td>
<td><?php echo $row['name']; ?></td>
<td><?php echo date("m/d/Y", strtotime($row['service_date'])); ?></td>
<td><?php echo $row['service_type']; ?></td>
<td><?php echo $row['method']; ?></td>
<td><?php echo $row['provider']; ?></td>
<td><?php echo $row['notes']; ?></td>
</tr>
<?php endwhile; ?>

</table>
</div>

</div>


<div class="modal" id="serviceModal">
<div class="modal-content">

<div class="modal-header">
    <h3>
        <i class="fas fa-concierge-bell"></i>
        Add Service Record
    </h3>
</div>

<form method="POST">
<div class="modal-body">

<div class="form-grid">
    <div class="form-group full">
        <label>Patient</label>
        <select name="patient_id" required>
            <option value="">Select Patient</option>
            <?php
            $p = $conn->query("SELECT patient_id,name FROM patients");
            while($row=$p->fetch_assoc()){
                echo "<option value='{$row['patient_id']}'>{$row['name']}</option>";
            }
            ?>
        </select>
    </div>

    <div class="form-group">
        <label>Service Date</label>
        <input type="date" name="service_date" required>
    </div>

    <div class="form-group">
        <label>Service Time</label>
        <input type="time" name="service_time" required>
    </div>

    <div class="form-group">
        <label>Service Type</label>
        <input name="service_type" placeholder="Enter service type" required>
    </div>

    <div class="form-group">
        <label>Method</label>
        <input name="method" placeholder="Enter method" required>
    </div>

    <div class="form-group">
        <label>Provider</label>
        <input name="provider" placeholder="Enter provider">
    </div>

    <div class="form-group full">
        <label>Notes / Observations</label>
        <textarea name="notes" placeholder="Enter notes"></textarea>
    </div>
</div>

</div>

<div class="modal-footer">
    <button type="button" onclick="closeModal()" class="btn-secondary">Cancel</button>
    <button name="save_service" class="btn-primary">Save Record</button>
</div>

</form>
</div>
</div>

<script>
function openModal(){ document.getElementById("serviceModal").style.display="flex"; }
function closeModal(){ document.getElementById("serviceModal").style.display="none"; }


function toggleDropdown() {
    var dropdown = document.getElementById("dropdownMenu");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
}
window.onclick = function(event) {
  if (!event.target.matches('.menu')) {
    var dropdown = document.getElementById("dropdownMenu");
    if (dropdown.style.display === "block") { dropdown.style.display = "none"; }
  }
}


document.getElementById('searchInput').addEventListener('keyup', function() {
    var filter = this.value.toLowerCase();
    var table = document.getElementById('dataTable');
    var tr = table.getElementsByTagName('tr');
    for (var i = 1; i < tr.length; i++) {
        var rowText = tr[i].textContent.toLowerCase();
        tr[i].style.display = rowText.indexOf(filter) > -1 ? '' : 'none';
    }
});
</script>

</body>
</html>