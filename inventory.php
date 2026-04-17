<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];


if(isset($_POST['add_item'])){
    $item_id = !empty($_POST['item_id']) ? $_POST['item_id'] : 'IV' . str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
    $item_name = $_POST['item_name'];
    $medicine_type = $_POST['medicine_type'];
    $quantity = (int)$_POST['quantity'];
    $supplier = $_POST['supplier'];
    $batch_number = $_POST['batch_number'];
    $expiration_date = $_POST['expiration_date'];

    $stmt = $conn->prepare("INSERT INTO inventory (item_id,item_name,medicine_type,quantity,supplier,batch_number,expiration_date) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("sssisss",$item_id,$item_name,$medicine_type,$quantity,$supplier,$batch_number,$expiration_date);
    $stmt->execute();
    header("Location: inventory.php");
    exit();
}


if(isset($_POST['stock_action'])){
    $item_id = $_POST['item_id'];
    $qty = (int)$_POST['qty'];
    $type = $_POST['type'];
    $notes = $_POST['notes'];
    $supplier = $_POST['supplier'] ?? '';

    $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE item_id=?");
    $stmt->bind_param("s",$item_id);
    $stmt->execute();
    $current_qty = $stmt->get_result()->fetch_assoc()['quantity'] ?? 0;

    if($type === "IN"){
        $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity + ?, supplier=? WHERE item_id=?");
        $stmt->bind_param("iss",$qty,$supplier,$item_id);
        $stmt->execute();
    } else {
        if($current_qty >= $qty){
            $stmt = $conn->prepare("UPDATE inventory SET quantity = quantity - ? WHERE item_id=?");
            $stmt->bind_param("is",$qty,$item_id);
            $stmt->execute();
        } else {
            $_SESSION['error'] = "Cannot remove more than current stock!";
        }
    }

if($type === "IN"){
    $stmt = $conn->prepare("INSERT INTO stock_in_transactions (item_id,quantity,notes,performed_by,supplier) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sisss",$item_id,$qty,$notes,$username,$supplier);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("INSERT INTO stock_out_transactions (item_id,quantity,notes,performed_by) VALUES (?,?,?,?)");
    $stmt->bind_param("siss",$item_id,$qty,$notes,$username);
    $stmt->execute();
}

    header("Location: inventory.php");
    exit();
}


$search = $_GET['search'] ?? "";
$filter_type = $_GET['medicine_type'] ?? "";

$query = "SELECT * FROM inventory WHERE 1=1";
$params = [];
$types = "";

if($search !== ""){
    $query .= " AND (item_name LIKE CONCAT('%',?,'%') OR item_id LIKE CONCAT('%',?,'%'))";
    $types .= "ss";
    $params[] = $search;
    $params[] = $search;
}

if($filter_type !== ""){
    $query .= " AND medicine_type=?";
    $types .= "s";
    $params[] = $filter_type;
}

$query .= " ORDER BY item_name ASC";
$stmt = $conn->prepare($query);
if(count($params) > 0){
    $stmt->bind_param($types,...$params);
}
$stmt->execute();
$items = $stmt->get_result();


$total_items = $conn->query("SELECT COUNT(*) as total FROM inventory")->fetch_assoc()['total'] ?? 0;
$total_stock = $conn->query("SELECT SUM(quantity) as total FROM inventory")->fetch_assoc()['total'] ?? 0;
$low_stock = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE quantity < 20")->fetch_assoc()['total'] ?? 0;
$expiring_soon = $conn->query("SELECT COUNT(*) as total FROM inventory WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch_assoc()['total'] ?? 0;
$stock_in = $conn->query("SELECT * FROM stock_in_transactions ORDER BY transaction_date DESC");
$stock_out = $conn->query("SELECT * FROM stock_out_transactions ORDER BY transaction_date DESC");

$currentPage = basename($_SERVER['PHP_SELF']);
function navClass($page){
    global $currentPage;
    return $currentPage === $page ? 'active' : '';
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Inventory - Family Planning System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<style>

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f7fa;
}

.header {
    background: #0f8f5f;
    color: white;
    padding: 20px;
    position: relative;
}

.header h1 {
    margin: 0;
}

.header h2,
.header p {
    margin: 5px 0 0;
    font-size: 14px;
}

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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 100;
}

.dropdown a {
    color: black;
    text-decoration: none;
    display: block;
    padding: 10px 15px;
}

.dropdown a:hover {
    background: #f0f0f0;
}

.nav {
    background: white;
    padding: 12px 20px;
    display: flex;
    gap: 25px;
    border-bottom: 1px solid #ddd;
}

.nav a {
    text-decoration: none;
    color: #555;
    border-bottom: 2px solid transparent;
    padding-bottom: 5px;
    transition: all 0.2s;
}

.nav a.active {
    color: #0f8f5f;
    border-bottom: 2px solid #0f8f5f;
    background: transparent;
}

.custom-tabs .nav-link {
    color: #555;
    font-weight: 600;
    border: 2px solid transparent;
    border-radius: 8px 8px 0 0;
    margin-right: 5px;
    transition: all 0.2s;
}

.custom-tabs .nav-link:hover {
    color: #0f8f5f;
    background: #f0fdfa;
}

.custom-tabs .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg,#0f8f5f,#0c6e49);
    border-color: #0f8f5f #0f8f5f transparent;
    box-shadow: 0 4px 12px rgba(15,143,95,0.3);
}

.custom-tabs .nav-link i {
    font-size: 14px;
}

.container {
    padding: 20px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.search {
    padding: 8px;
    width: 250px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

button {
    background: #0f8f5f;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
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
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
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

.dashboard-cards {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.dashboard-cards .card {
    flex: 1 1 20%;
    min-width: 120px;
}

.modal-header-custom {
    background-color: #0f8f5f;
    color: #fff;
    padding: 20px 30px;
    border-top-left-radius: 7px;
    border-top-right-radius: 7px;
    text-align: left;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 18px;
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.modal-header-custom h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-body-custom {
    padding: 20px;
    background: #f9fafb;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 12px 14px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.2s;
    width: 100%;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #0f8f5f;
    outline: none;
    box-shadow: 0 0 6px rgba(15,143,95,0.2);
}

.modal-footer-custom {
    padding: 20px 30px;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    background: #fff;
    border-top: 1px solid #eee;
}

.btn-primary {
    background: linear-gradient(135deg,#0f8f5f,#0c6e49);
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 8px 20px rgba(15,143,95,0.3);
    transition: all 0.2s;
}

.btn-primary:hover {
    background: #0c6d4a;
}

.btn-secondary {
    background: #e5e7eb;
    color: #333;
    border: none;
    padding: 10px 18px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
}
</style>
</head>
<body>

<div class="header" style="display:flex; align-items:center; gap:15px;">
    <img class="logo" src="logo.png" alt="Logo">
    <div>
    <h1 style="margin:0; font-size:32px; font-weight:bold;">Family Planning Monitoring System</h1>
    <p style="margin:5px 0 0; font-size:14px; font-weight:normal;">Rural Health Unit - Dumingag, Zamboanga del Sur</p>
</div>
<div class="menu" onclick="toggleDropdown()" style="margin-left:auto; font-size:24px; cursor:pointer;">☰</div>
<div class="dropdown" id="dropdownMenu"><a href="logout.php">Logout</a></div>
</div>

<div class="nav">
<a href="index.php" class="<?= navClass('index.php'); ?>"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
<a href="patients.php" class="<?= navClass('patients.php'); ?>"><i class="fas fa-users"></i>Patients</a>
<a href="appointments.php" class="<?= navClass('appointments.php'); ?>"><i class="fas fa-calendar-check"></i>Appointments</a>
<a href="services.php" class="<?= navClass('services.php'); ?>"><i class="fas fa-concierge-bell"></i>Services</a>
<a href="inventory.php" class="<?= navClass('inventory.php'); ?>"><i class="fas fa-capsules"></i>Inventory</a>
<a href="reports.php" class="<?= navClass('reports.php'); ?>"><i class="fas fa-file-alt"></i>Reports</a>
</div>

<div class="d-flex mb-4" style="gap:15px;">
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center" style="flex:1;">
        <div class="icon"><i class="fas fa-boxes"></i></div>
        <h4>Total Items</h4>
        <h2><?= $total_items ?></h2>
    </div>
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center" style="flex:1;">
        <div class="icon"><i class="fas fa-layer-group"></i></div>
        <h4>Total Stock</h4>
        <h2><?= $total_stock ?></h2>
    </div>
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center text-danger" style="flex:1;">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h4>Low Stock</h4>
        <h2><?= $low_stock ?></h2>
    </div>
    <div class="card shadow-sm p-3 text-center d-flex flex-column align-items-center text-warning" style="flex:1;">
        <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        <h4>Expiring Soon</h4>
        <h2><?= $expiring_soon ?></h2>
    </div>
</div>

<ul class="nav nav-tabs mb-3 custom-tabs">
  <li class="nav-item">
    <a class="nav-link active" data-bs-toggle="tab" href="#inventoryTab">
      <i class="fas fa-capsules me-1"></i> Current Inventory</a></li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#transactionsTab">
      <i class="fas fa-arrow-up me-1"></i> Stock In Transactions</a></li>
  <li class="nav-item">
    <a class="nav-link" data-bs-toggle="tab" href="#stockOutTab">
      <i class="fas fa-arrow-down me-1"></i> Stock Out Transactions</a></li>
</ul>

<div class="tab-content bg-white p-3 shadow-sm">
<div class="tab-pane fade show active" id="inventoryTab">
<div class="top-bar mb-3">
<input type="text" id="searchInput" class="search" placeholder="Search inventory...">
<button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">Add New Item</button>
</div>

<table class="table table-bordered table-striped" id="dataTable">
<thead class="table-dark">
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Type</th>
    <th>Qty</th>
    <th>Expiration Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php while($row = $items->fetch_assoc()):
    $today = date('Y-m-d');
    $exp_date = $row['expiration_date'];

    if($row['quantity'] < 20){ 
        $badge="<span class='badge bg-danger'>Low Stock</span>"; 
    } elseif($exp_date < $today){ 
        $badge="<span class='badge bg-danger'>Expired</span>"; 
    } elseif(strtotime($exp_date) <= strtotime("+30 days")){ 
        $badge="<span class='badge bg-warning'>Expiring Soon</span>"; 
    } else { 
        $badge="<span class='text-muted'>OK</span>"; 
    }

    $exp_display = date('m/d/Y', strtotime($exp_date));
?>
<tr>
    <td><?= htmlspecialchars($row['item_id']) ?></td>
    <td><?= htmlspecialchars($row['item_name']) ?></td>
    <td><?= htmlspecialchars($row['medicine_type']) ?></td>
    <td><?= $row['quantity'] ?></td>
    <td><?= $exp_display ?></td>
    <td><?= $badge ?></td>
    <td>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal<?= $row['item_id'] ?>">Stock</button>
    </td>
</tr>

<div class="modal fade" id="stockModal<?= $row['item_id'] ?>">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST"> <!-- Move form to wrap entire modal -->
        <input type="hidden" name="item_id" value="<?= $row['item_id'] ?>">
        <div class="modal-header modal-header-custom">
          <h3><i class="fas fa-exchange-alt"></i> Stock Action</h3>
        </div>
        <div class="modal-body modal-body-custom">
          <div class="form-grid">
            <div class="form-group">
              <label>Action Type</label>
              <select name="type">
                <option value="IN">Stock In</option>
                <option value="OUT">Stock Out</option>
              </select>
            </div>

            <div class="form-group">
              <label>Quantity</label>
              <input type="number" name="qty" placeholder="Enter quantity" required>
            </div>

            <div class="form-group full">
              <label>Supplier (if Stock In)</label>
              <input type="text" name="supplier" placeholder="Enter supplier">
            </div>

            <div class="form-group full">
              <label>Notes</label>
              <textarea name="notes" placeholder="Add any notes"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="submit" name="stock_action" class="btn-primary">Submit</button>
          <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="tab-pane fade" id="transactionsTab">
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Item ID</th>
<th>Qty</th>
<th>Date</th>
<th>Supplier</th>
<th>Notes</th>
<th>Performed By</th>
</tr>
</thead>
<tbody>
<?php while($t=$stock_in->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($t['item_id']) ?></td>
<td><?= $t['quantity'] ?></td>
<td><?= date('m/d/Y', strtotime($t['transaction_date'])) ?></td>
<td><?= htmlspecialchars($t['supplier']) ?></td>
<td><?= htmlspecialchars($t['notes']) ?></td>
<td><?= htmlspecialchars($t['performed_by']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<div class="tab-pane fade" id="stockOutTab">
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Item ID</th>
<th>Qty</th>
<th>Date</th>
<th>Notes</th>
<th>Performed By</th>
</tr>
</thead>
<tbody>
<?php while($d=$stock_out->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($d['item_id']) ?></td>
<td><?= $d['quantity'] ?></td>
<td><?= date('m/d/Y', strtotime($d['transaction_date'])) ?></td>
<td><?= htmlspecialchars($d['notes']) ?></td>
<td><?= htmlspecialchars($d['performed_by']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

</div>


<div class="modal fade" id="addModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST"> <!-- Wrap entire modal -->
        <div class="modal-header modal-header-custom">
          <h3><i class="fas fa-boxes"></i> Add New Inventory Item</h3>
        </div>
        <div class="modal-body modal-body-custom">
          <div class="form-grid">
            <div class="form-group">
              <label>Item ID</label>
              <input type="text" name="item_id" placeholder="Leave blank to auto-generate">
            </div>

            <div class="form-group">
              <label>Item Name</label>
              <input type="text" name="item_name" required>
            </div>

            <div class="form-group full">
              <label>Medicine Type</label>
              <select name="medicine_type" required>
                <option value="condom">Condom</option>
                <option value="pill">Pill</option>
                <option value="injection">Injection</option>
                <option value="implant">Implant</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div class="form-group">
              <label>Supplier</label>
              <input type="text" name="supplier">
            </div>

            <div class="form-group">
              <label>Batch Number</label>
              <input type="text" name="batch_number">
            </div>

            <div class="form-group">
              <label>Quantity</label>
              <input type="number" name="quantity" required>
            </div>

            <div class="form-group">
              <label>Expiration Date</label>
              <input type="date" name="expiration_date" required>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-custom">
          <button type="submit" name="add_item" class="btn-primary">Add Item</button>
          <button type="button" class="btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDropdown(){
    var menu = document.getElementById('dropdownMenu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

document.getElementById('searchInput').addEventListener('input', function(){
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#dataTable tbody tr');
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

var addModal = document.getElementById('addModal');
addModal.addEventListener('hidden.bs.modal', function () {
    addModal.querySelector('form').reset();
});
</script>

</body>
</html>