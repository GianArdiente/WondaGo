<?php require_once 'includes/config.php'; ?>  
<?php 
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    echo 'shiot';
    $id = $_GET['id'];
    $tableName = $_GET['table'] ?? '';
    if (
        $tableName && $id &&
        strtolower($tableName) === 'transactions' &&
        isset($_SESSION['user']) && $_SESSION['user']['type'] == 0
    ) {
        // fetch the transaction to check its type and ownership
        $txnRowRes = $db->select('transactions', '*', [
            'id' => $id,
            'user_id' => $_SESSION['user']['id']
        ]);
        if ($txnRowRes['status'] === 'success' && !empty($txnRowRes['data'][0])) {
            $txn = $txnRowRes['data'][0];
            // Only allow cancel if transaction type is 1 (venue)
            if ((int)($txn['type'] ?? -1) !== 1) {
                echo '<div class="alert topalert alert-danger">Only venue reservations can be canceled.</div>';
            } else {
                $result = $db->update('transactions', ['status' => 2], [
                    'id' => $id,
                    'user_id' => $_SESSION['user']['id']
                ]);
                if ($result['status'] === 'success') {
                    header("Location: table.php?table=" . urlencode($tableName) . "&canceled=1");
                    exit;
                } else {
                    echo '<div class="alert topalert alert-danger">Cancel failed: ' . htmlspecialchars($result['message']) . '</div>';
                }
            }
        } else {
            echo '<div class="alert topalert alert-danger">Transaction not found.</div>';
        }
    }
}
?>

<?php if (isset($_GET['canceled']) && $_GET['canceled'] == '1' && strtolower($table) === 'transactions'): ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'info',
        title: 'Notice',
        html:
            'Please be informed that <strong>cancellations are not allowed</strong> for event bookings.<br><br>' +
            'Clients are only permitted to <strong>reschedule up to three (3) times</strong>, subject to availability.<br><br>' +
            'Kindly note that the <strong>down payment is non-refundable</strong> if you decide not to proceed with the booking.<br><br>' +
            'For rescheduling requests, please email us at <a href="mailto:enjoy@motherswonderland.com">enjoy@motherswonderland.com</a>' +
            ' or contact us at <strong>0949-879-4919</strong> for assistance.<br><br>' +
            'Thank you for your understanding.',
        confirmButtonText: 'OK'
    });
});
</script>
<?php endif; ?>
<?php


function cleanResultArray($array) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = cleanResultArray($value);
        } else {
            if (!mb_detect_encoding($value, 'UTF-8', true)) {
                $array[$key] = mb_convert_encoding($value, 'UTF-8');
            }
        }
    }
    return $array;
}

$table = isset($_GET['table']) ? $_GET['table'] : '';

$result = ['data' => [], 'columns' => []];

$usernames = $db->select('users', 'id, name');
$subscriptions = $db->select('subscriptions', 'id, name, price');
$discounts = $db->select('news', 'id, name, discount, start_date, end_date');

if ($table) {
    $selectResult = $db->select($table);
    if ($selectResult['status'] === 'success') {
        $result['data'] = cleanResultArray($selectResult['data'] ?? []);
        $result['columns'] = cleanResultArray($selectResult['columns'] ?? []);
    }
}


// Auto-expire transactions older than 3 days and not already expired (status != 3)
if (strtolower($table) === 'transactions') {
    $now = new DateTime();
    foreach ($result['data'] as $row) {
        if (isset($row['created_at'], $row['status'])) {
            $createdAt = DateTime::createFromFormat('Y-m-d H:i:s', $row['created_at']);
                if ($createdAt && !in_array((int)$row['status'], [1, 2, 3])) {
                $diffSeconds = $now->getTimestamp() - $createdAt->getTimestamp();
                if ($diffSeconds >= 259200) {
                    $db->update('transactions', ['status' => 3], ['id' => $row['id']]);
                }
            }
        }
    }
    // Refresh data after update
    $selectResult = $db->select($table);
    if ($selectResult['status'] === 'success') {
        $result['data'] = cleanResultArray($selectResult['data'] ?? []);
        $result['columns'] = cleanResultArray($selectResult['columns'] ?? []);
    }
}

if (strtolower($table) === 'transactions' && isset($_SESSION['user']) && $_SESSION['user']['type'] == 0) {
    $selectResult = $db->select($table, '*', ['user_id' => $_SESSION['user']['id']]);
    if ($selectResult['status'] === 'success') {
        $result['data'] = cleanResultArray($selectResult['data'] ?? []);
        $result['columns'] = cleanResultArray($selectResult['columns'] ?? []);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    if ($_GET['action'] === 'add') {
        $posted = $_POST;

        if (strtolower($table) === 'subscriptions' && isset($posted['price']) && is_array($posted['price'])) {
            $priceParts = [];
            foreach ($posted['price'] as $group => $amount) {
                if ($amount !== '' && $amount !== null) {
                    $priceParts[] = "{$group}:{$amount}";
                }
            }
            $posted['price'] = implode(',', $priceParts);
        }

        if (strtolower($table) === 'news' && isset($_FILES['photo'])) {
            $uploadedPhotos = [];
            $photoFiles = $_FILES['photo'];
            $targetDir = __DIR__ . '/assets/images/news/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            for ($i = 0; $i < count($photoFiles['name']); $i++) {
                if ($photoFiles['error'][$i] === UPLOAD_ERR_OK && !empty($photoFiles['name'][$i])) {
                    $ext = pathinfo($photoFiles['name'][$i], PATHINFO_EXTENSION);
                    $uniqueName = uniqid('news_', true) . '.' . $ext;
                    $targetFile = $targetDir . $uniqueName;
                    if (move_uploaded_file($photoFiles['tmp_name'][$i], $targetFile)) {
                        $uploadedPhotos[] = $uniqueName;
                    }
                }
            }
            if (!empty($uploadedPhotos)) {
                $posted['photo'] = implode(',', $uploadedPhotos);
            } else {
                unset($posted['photo']);
            }
        }

        if (strtolower($table) === 'news' && isset($posted['discount']) && is_array($posted['discount'])) {
            $discountParts = [];
            foreach ($posted['discount'] as $group => $amount) {
                if ($amount !== '' && $amount !== null) {
                    $discountParts[] = "{$group}:{$amount}";
                }
            }
            $posted['discount'] = implode(',', $discountParts);
        }

        if (strtolower($table) === 'news' && isset($posted['featured'])) {
            $posted['featured'] = $posted['featured'] == '1' ? 1 : 0;
        }

        foreach ($posted as $k => $v) {
            if (is_string($v) && trim($v) === '') unset($posted[$k]);
        }

        $addResult = $db->insert($table, $posted);
        if ($addResult['status'] === 'success') {
            header("Location: table.php?table=" . urlencode($table) . "&added=1");
            exit;
        } else {
            echo '<div class="alert topalert alert-danger">Add failed: ' . htmlspecialchars($addResult['message']) . '</div>';
        }
    } elseif ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $posted = $_POST;
        $id = $_GET['id'];

        // Merge price fields for 'subscriptions' table
        if (strtolower($table) === 'subscriptions' && isset($posted['price']) && is_array($posted['price'])) {
            $priceParts = [];
            foreach ($posted['price'] as $group => $amount) {
                if ($amount !== '' && $amount !== null) {
                    $priceParts[] = "{$group}:{$amount}";
                }
            }
            $posted['price'] = implode(',', $priceParts);
        }

        if (strtolower($table) === 'news' && isset($_FILES['photo'])) {
            $uploadedPhotos = [];
            $photoFiles = $_FILES['photo'];
            $targetDir = __DIR__ . '/assets/images/news/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            for ($i = 0; $i < count($photoFiles['name']); $i++) {
                if ($photoFiles['error'][$i] === UPLOAD_ERR_OK && !empty($photoFiles['name'][$i])) {
                    $ext = pathinfo($photoFiles['name'][$i], PATHINFO_EXTENSION);
                    $uniqueName = uniqid('news_', true) . '.' . $ext;
                    $targetFile = $targetDir . $uniqueName;
                    if (move_uploaded_file($photoFiles['tmp_name'][$i], $targetFile)) {
                        $uploadedPhotos[] = $uniqueName;
                    }
                }
            }
            if (!empty($uploadedPhotos)) {
                $currentRow = $db->select($table, '*', ['id' => $id]);
                $existingPhotos = [];
                if (!empty($currentRow['data'][0]['photo'])) {
                    $existingPhotos = array_map('trim', explode(',', $currentRow['data'][0]['photo']));
                }
                $allPhotos = array_merge($existingPhotos, $uploadedPhotos);
                $posted['photo'] = implode(',', $allPhotos);
            } else {
                unset($posted['photo']);
            }
        }

        if (strtolower($table) === 'news' && isset($posted['discount']) && is_array($posted['discount'])) {
            $discountParts = [];
            foreach ($posted['discount'] as $group => $amount) {
                if ($amount !== '' && $amount !== null) {
                    $discountParts[] = "{$group}:{$amount}";
                }
            }
            $posted['discount'] = implode(',', $discountParts);
        }

        if (strtolower($table) === 'news' && isset($posted['featured'])) {
            $posted['featured'] = $posted['featured'] == '1' ? 1 : 0;
        }

        foreach ($posted as $k => $v) {
            if (is_string($v) && trim($v) === '') unset($posted[$k]);
        }

        $editResult = $db->update($table, $posted, ['id' => $id]);
        if ($editResult['status'] === 'success') {
            header("Location: table.php?table=" . urlencode($table) . "&edited=1");
            exit;
        } else {
            echo '<div class="alert topalert alert-danger">Edit failed: ' . htmlspecialchars($editResult['message']) . '</div>';
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $tableName = $_GET['table'] ?? '';
    if ($tableName && $id) {
        $deleteResult = $db->delete($tableName, ['id' => $id]);
        if ($deleteResult['status'] === 'success') {
            header("Location: table.php?table=" . urlencode($tableName) . "&deleted=1");
            exit;
        } else {
            echo '<div class="alert topalert alert-danger">Delete failed: ' . htmlspecialchars($deleteResult['message']) . '</div>';
        }
    }
}

?>


<!DOCTYPE html>
    <html lang="en">
  
        <?php require_once 'includes/head.php'; ?>  
    
    <style>
        .receipt-modal {
            background: #f8f9fa;
        }
        
        .receipt-container {
            background: white;
            max-width: 400px;
            margin: 0 auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            font-family: 'Courier New', monospace;
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .receipt-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }
        
        .receipt-subtitle {
            font-size: 14px;
            color: #666;
            margin: 5px 0 0 0;
        }
        
        .receipt-body {
            margin-bottom: 20px;
        }
        
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .receipt-row.total {
            font-weight: bold;
            font-size: 16px;
            border-top: 1px solid #ddd;
            padding-top: 8px;
            margin-top: 15px;
        }
        
        .receipt-section {
            margin-bottom: 15px;
        }
        
        .receipt-section-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }
        
        .receipt-qr {
            text-align: center;
            padding: 20px 0;
            border-top: 2px dashed #ddd;
            margin-top: 20px;
        }
        
        .receipt-qr canvas,
        .receipt-qr img {
            border: 2px solid #ddd;
            border-radius: 8px;
            display: block;
            margin: 0 auto 15px auto;
            max-width: 200px;
            max-height: 200px;
            background: white;
        }
        
        #qrcode-container {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .receipt-footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-approved {
            background: #28a745;
            color: white;
        }
        
        .status-pending {
            background: #ffc107;
            color: #333;
        }
        
        .status-canceled {
            background: #dc3545;
            color: white;
        }
        
        .status-expired {
            background: #6c757d;
            color: white;
        }
        
        .print-button {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        @media print {
            .modal-header, .modal-footer, .print-button {
                display: none !important;
            }
            
            .modal-body {
                padding: 0 !important;
            }
            
            .receipt-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>  
    
    <body class="loading" data-layout-color="light" data-leftbar-theme="light" data-layout-mode="fluid" data-rightbar-onstart="true">
        <!-- Begin page -->
        <div class="wrapper">
            <?php require_once 'includes/sidebar.php'; ?>  
            <?php require_once 'includes/topbar.php'; ?>  


            <!-- ============================================================== -->
            <!-- Start Page Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">

                    <!-- Start Content-->
                    <div class="container-fluid">
                        
                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <div class="page-title-right">
                                        <ol class="breadcrumb m-0">
                                        </ol>
                                    </div>
                                    <?php
                                    $tableName = isset($_GET['table']) ? strtoupper($_GET['table']) : 'Data Tables';
                                    ?>
                                    <h4 class="page-title"><?php echo htmlspecialchars($tableName); ?></h4>
                                </div>
                            </div>
                        </div>
                        <!-- end page title --> 
 
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                               <?php if (strtolower($table) !== 'feedback' && strtolower($table) !== 'transactions'): ?>

                    <button class="btn btn-primary mb-3" id="addRowBtn">
                        <i class="mdi mdi-plus"></i> Add <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($_GET['table'] ?? ''))); ?>
                    </button>
                <?php endif; ?>
                <div class="tab-content">
                    <div class="tab-pane show active" id="buttons-table-preview">
                        <?php
                        $excluded = ['id', 'created_at', 'updated_at', 'password','pax', 'unique_id'];
                        $displayColumns = [];

                        if (empty($result['columns']) && !empty($result['data'][0])) {
                            $result['columns'] = array_keys($result['data'][0]);
                        }

                        // Build user id => name map
                        $userIdNameMap = [];
                        if (!empty($usernames['data'])) {
                            foreach ($usernames['data'] as $user) {
                                if (isset($user['id']) && isset($user['name'])) {
                                    $userIdNameMap[$user['id']] = $user['name'];
                                }
                            }
                        }

                        // Type mapping for subscriptions
                        $typeMap = [
                            0 => 'Tickets',
                            1 => 'Event Venues',
                            2 => 'Membership'
                        ];

                        if (!empty($result['columns'])) {
                            foreach ($result['columns'] as $col) {
                                if (strtolower($table) === 'transactions') {
                                    // Custom headers for transactions
                                    if (strtolower($col) === 'user_id') {
                                        $displayColumns[] = 'Name';
                                    } elseif (strtolower($col) === 'subscription') {
                                        $displayColumns[] = 'Subscription';
                                    } elseif (strtolower($col) === 'type') {
                                        $displayColumns[] = 'Type';
                                    } elseif (strtolower($col) === 'status') {
                                        $displayColumns[] = 'Status';
                                    } elseif (strtolower($col) === 'payment_info') {
                                        $displayColumns[] = 'Payment Info';
                                    }  elseif (!in_array(strtolower($col), $excluded)) {
                                        $displayColumns[] = ucwords(str_replace('_', ' ', $col));
                                    }
                                } else {
                                    if (strtolower($col) === 'user_id') {
                                        $displayColumns[] = 'Name';
                                    } elseif (!in_array(strtolower($col), $excluded)) {
                                        $displayColumns[] = ucwords(str_replace('_', ' ', $col));
                                    }
                                }
                            }
                        }
                        ?>
                        <?php if (!empty($displayColumns)): ?>
                            <table id="datatable-buttons" class=" table table-striped dt-responsive nowrap w-100">
                                <thead>
                                    <tr>
                                        <?php foreach ($displayColumns as $col): ?>
                                            <th><?php echo htmlspecialchars($col); ?></th>
                                        <?php endforeach; ?>
                                        <?php if (strtolower($table) !== 'feedback'): ?>
                                            <th>Action</th>
                                        <?php endif; ?>
                                                                                <tbody> 
                                                                                    <?php
                                                                                    // Helper for date formatting
                                                                                    function formatDisplayDate($dateStr) {
                                                                                        if (!$dateStr) return '';
                                                                                        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
                                                                                        if (!$dt) $dt = DateTime::createFromFormat('Y-m-d\TH:i', $dateStr); // fallback for datetime-local
                                                                                        if (!$dt) return htmlspecialchars($dateStr);
                                                                                        return $dt->format('F j, Y \a\t h:iA');
                                                                                    }
                                                                                    foreach ($result['data'] as $row): ?>
                                                                                        <tr>
                                                                                            <?php foreach ($result['columns'] as $col): ?>
                                                                                            
                                                                                                <?php if (strtolower($table) === 'transactions'): ?>
                                                                                                    <?php if (strtolower($col) === 'user_id'): ?>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            $uid = $row[$col] ?? '';
                                                                                                            echo htmlspecialchars($userIdNameMap[$uid] ?? $uid);
                                                                                                            ?>
                                                                                                        </td>
                                                                                                    <?php elseif (strtolower($col) === 'guest_names'): ?>
                                                                                                      <td>
    <?php
    $names = [];

    if (isset($row[$col]) && !empty($row[$col])) {
        $raw = $row[$col];

        // Try JSON decode first
        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $names = $decoded;
        } else {
            // Fallback: clean brackets and quotes, then explode
            $cleaned = trim($raw, "[]\"");
            $names = array_map('trim', explode(',', $cleaned));
        }
    }

    // Print formatted badges
    foreach ($names as $name) {
        $name = trim($name, " \t\n\r\0\x0B\"[]");
        if ($name !== '') {
            echo "<span class='badge bg-info me-1'>" . htmlspecialchars($name) . "</span>";
        }
    }
    ?>
</td>

                                                                                                    <?php elseif (strtolower($col) === 'subscription'): ?>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            $subId = $row[$col] ?? '';
                                                                                                            $subscriptionName = '';
                                                                                                            if (!empty($subscriptions['data'])) {
                                                                                                                foreach ($subscriptions['data'] as $sub) {
                                                                                                                    if (isset($sub['id']) && $sub['id'] == $subId) {
                                                                                                                        $subscriptionName = $sub['name'];
                                                                                                                        break;
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                            echo htmlspecialchars($subscriptionName ?: $subId);
                                                                                                            ?>
                                                                                                        </td>
                                                                                                    <?php elseif (strtolower($col) === 'type'): ?>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            echo isset($typeMap[$row[$col]]) ? $typeMap[$row[$col]] : htmlspecialchars($row[$col] ?? '');
                                                                                                            ?>
                                                                                                        </td>
                                                                                                    <?php elseif (strtolower($col) === 'status'): ?>
                                                                                                        <td>
                                                                                                               <?php
                                                                                                        $status = (int)($row[$col] ?? 0);
                                                                                                        if ($status === 1) {
                                                                                                            echo '<span class="badge bg-success">Approved</span>';
                                                                                                        } elseif ($status === 2) {
                                                                                                            echo '<span class="badge bg-danger">Canceled</span>';
                                                                                                        } elseif ($status === 3) {
                                                                                                            echo '<span class="badge bg-secondary">Expired</span>';
                                                                                                        } else {
                                                                                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                                                                                        }
                                                                                                            ?>
                                                                                                        </td>
                                                                                                    <?php elseif (strtolower($col) === 'payment_info'): ?>
                                                                                           <td>
                                        <?php
                                        $info = $row['payment_info'] ?? '';
                                        $parts = [];
                                        $proofImg = '';
                                        $amount = '';
                                        $payment = '';
                                        $categories = [];

                                        // Reservation dates from table
                                        $reservationDate = $row['reservation_date'] ?? '';
                                        $endDate = $row['end_date'] ?? '';
                                        $days = 0;

                                        if ($reservationDate && $endDate) {
                                        $start = DateTime::createFromFormat('Y-m-d H:i:s', $reservationDate);
                                        if (!$start) $start = DateTime::createFromFormat('Y-m-d\TH:i', $reservationDate);
                                        $end = DateTime::createFromFormat('Y-m-d H:i:s', $endDate);
                                        if (!$end) $end = DateTime::createFromFormat('Y-m-d\TH:i', $endDate);
                                        if ($start && $end) {
                                        $diff = $start->diff($end);
                                        $days = $diff->days;
                                        $parts[] = "<b>Reservation:</b> "
                                        . formatDisplayDate($reservationDate)
                                        . " → " . formatDisplayDate($endDate)
                                        . " (<b>{$days} days</b>)";
                                        } else {
                                        $parts[] = "<b>Reservation:</b> "
                                        . htmlspecialchars($reservationDate)
                                        . " → " . htmlspecialchars($endDate);
                                        }
                                        }

                                        // Parse payment_info string
                                        foreach (explode(',', $info) as $kv) {
                                        $kvParts = explode(':', $kv);
                                        if (count($kvParts) == 2) {
                                        $k = strtolower(trim($kvParts[0]));
                                        $v = trim($kvParts[1]);

                                        if ($k === 'proof' && $v) {
                                        $proofImg = $v;
                                        } elseif ($k === 'amount') {
                                        $amount = $v;
                                        } elseif ($k === 'payment') {
                                        $payment = $v;
                                        }
                                        } elseif (count($kvParts) == 5) {
                                        // category:count:originalprice:discount:subtotal
                                        $cat = htmlspecialchars($kvParts[0]);
                                        $count = htmlspecialchars($kvParts[1]);
                                        $orig = htmlspecialchars($kvParts[2]);
                                        $disc = htmlspecialchars($kvParts[3]);
                                        $subt = htmlspecialchars($kvParts[4]);

                                        // Formula with parentheses + days
                                        $formula = "({$count} × {$orig} - {$disc}%)";
                                        if ($days > 0) {
                                        $formula .= " × {$days} days";
                                        }

                                        $categories[] = "<span class='badge bg-secondary me-1'>{$cat}: {$formula} = <b>{$subt}</b></span>";
                                        }
                                        }

                                        if ($amount !== '') {
                                        $parts[] = "<b>Amount:</b> " . htmlspecialchars($amount);
                                        }
                                        if ($payment !== '') {
                                        $parts[] = "<b>Payment:</b> " . htmlspecialchars($payment);
                                        }
                                        if (!empty($categories)) {
                                        $parts[] = implode('<br>', $categories);
                                        }
                                        if ($proofImg) {
                                        $imgSrc = 'assets/images/payments/' . htmlspecialchars($proofImg);
                                        $parts[] = '<img src="' . $imgSrc . '" alt="Proof" style="height:40px;width:60px;object-fit:cover;border-radius:6px;cursor:pointer;" onclick="window.open(\'' . $imgSrc . '\', \'_blank\');">';
                                        }

                                        echo implode('<br>', $parts);
                                        ?>
                                        </td>

                                                                                                    <?php elseif (!in_array(strtolower($col), $excluded)): ?>
                                                                                                        <td>
                                                                                                            <?php echo htmlspecialchars($row[$col] ?? ''); ?>
                                                                                                        </td>
                                                                                                    <?php endif; ?>
                                                                                                <?php else: ?>
                                                                                                    <?php if (strtolower($col) === 'user_id'): ?>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            $uid = $row[$col] ?? '';
                                                                                                            echo htmlspecialchars($userIdNameMap[$uid] ?? $uid);
                                                                                                            ?>
                                                                                                        <?php elseif (strtolower($col) === 'guest_names'): ?>
                                                                                                  <td>
    <?php
    $names = [];

    if (isset($row[$col]) && !empty($row[$col])) {
        $raw = $row[$col];

        // Try JSON decode first
        $decoded = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $names = $decoded;
        } else {
            // Fallback: clean brackets and quotes, then explode
            $cleaned = trim($raw, "[]\"");
            $names = array_map('trim', explode(',', $cleaned));
        }
    }

    // Print formatted badges
    foreach ($names as $name) {
        $name = trim($name, " \t\n\r\0\x0B\"[]");
        if ($name !== '') {
            echo "<span class='badge bg-info me-1'>" . htmlspecialchars($name) . "</span>";
        }
    }
    ?>
</td>

                                                                                                    <?php elseif (!in_array(strtolower($col), $excluded)): ?>
                                                                                                        <td>
                                                                                                            <?php
                                                                                                            $lowerCol = strtolower($col);
                                                                                                  
                                                                                                            
                                                                                                            if (
                                                                                                                strtolower($table) === 'users' &&
                                                                                                                $lowerCol === 'profile' &&
                                                                                                                isset($row[$col]) && !empty($row[$col])
                                                                                                            ) {
                                                                                                                echo "<img src='assets/images/users/" . htmlspecialchars($row[$col]) . "' alt='Profile' style='height:40px;width:40px;border-radius:50%;object-fit:cover;'>";
                                                                                                            }
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'users' &&
                                                                                                                $lowerCol === 'id_card' &&
                                                                                                                isset($row[$col]) && !empty($row[$col])
                                                                                                            ) {
                                                                                                                echo "<img src='assets/images/users/" . htmlspecialchars($row[$col]) . "' alt='Profile' style='height:40px;width:40px;border-radius:50%;object-fit:cover;'>";
                                                                                                            }
                                                                                                            // For users table: Translate type 0 = User, 1 = Admin
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'users' &&
                                                                                                                $lowerCol === 'type'
                                                                                                            ) {
                                                                                                                echo $row[$col] == 1 ? 'Admin' : 'User';
                                                                                                            }
                                                                                                            // For news table: Featured column
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'news' &&
                                                                                                                $lowerCol === 'featured'
                                                                                                            ) {
                                                                                                                echo $row[$col] == 1 ? 'Yes' : 'No';
                                                                                                            }
                                                                                                            // For news table: Discount column, show as badges with percentage
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'news' &&
                                                                                                                $lowerCol === 'discount' &&
                                                                                                                isset($row[$col]) && !empty($row[$col])
                                                                                                            ) {
                                                                                                                $discountGroups = explode(',', $row[$col]);
                                                                                                                $formattedDiscounts = [];
                                                                                                                foreach ($discountGroups as $group) {
                                                                                                                    $parts = explode(':', $group);
                                                                                                                    if (count($parts) === 2) {
                                                                                                                        $label = trim($parts[0]);
                                                                                                                        $amount = trim($parts[1]);
                                                                                                                        $formattedDiscounts[] = "<span class='badge bg-success me-1'>{$label}: <b>{$amount}%</b></span>";
                                                                                                                    }
                                                                                                                }
                                                                                                                echo implode(' ', $formattedDiscounts);
                                                                                                            }
                                                                                                            // For news table: Photo column, show carousel if multiple, image if one
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'news' &&
                                                                                                                $lowerCol === 'photo' &&
                                                                                                                isset($row[$col]) && !empty($row[$col])
                                                                                                            ) {
                                                                                                                $photos = array_map('trim', explode(',', $row[$col]));
                                                                                                                if (count($photos) > 1) {
                                                                                                                    $carouselId = 'carousel_' . uniqid();
                                                                                                                    ?>
                                                                                                                    <div id="<?php echo $carouselId; ?>" class="carousel slide" data-bs-ride="carousel" style="max-width:120px;">
                                                                                                                        <div class="carousel-inner">
                                                                                                                            <?php foreach ($photos as $idx => $img): ?>
                                                                                                                                <div class="carousel-item <?php echo $idx === 0 ? 'active' : ''; ?>">
                                                                                                                                    <img src="assets/images/news/<?php echo htmlspecialchars($img); ?>" class="d-block w-100" style="height:60px;object-fit:cover;border-radius:6px;" alt="News Photo">
                                                                                                                                </div>
                                                                                                                            <?php endforeach; ?>
                                                                                                                        </div>
                                                                                                                        <button class="carousel-control-prev" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="prev" style="width:24px;">
                                                                                                                            <span class="carousel-control-prev-icon" aria-hidden="true" style="background-color:rgba(0,0,0,0.5);border-radius:50%;"></span>
                                                                                                                            <span class="visually-hidden">Previous</span>
                                                                                                                        </button>
                                                                                                                        <button class="carousel-control-next" type="button" data-bs-target="#<?php echo $carouselId; ?>" data-bs-slide="next" style="width:24px;">
                                                                                                                            <span class="carousel-control-next-icon" aria-hidden="true" style="background-color:rgba(0,0,0,0.5);border-radius:50%;"></span>
                                                                                                                            <span class="visually-hidden">Next</span>
                                                                                                                        </button>
                                                                                                                    </div>
                                                                                                                    <?php
                                                                                                                } elseif (count($photos) === 1 && !empty($photos[0])) {
                                                                                                                    echo "<img src='assets/images/news/" . htmlspecialchars($photos[0]) . "' style='height:60px;width:100px;object-fit:cover;border-radius:6px;' alt='News Photo'>";
                                                                                                                }
                                                                                                            }
                                                                                                            // For news table: Type column, show label
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'news' &&
                                                                                                                $lowerCol === 'type'
                                                                                                            ) {
                                                                                                                $typeLabel = '';
                                                                                                                switch ((int)$row[$col]) {
                                                                                                                    case 0: $typeLabel = 'Ticket'; break;
                                                                                                                    case 1: $typeLabel = 'Event Venue'; break;
                                                                                                                    case 2: $typeLabel = 'Membership'; break;
                                                                                                                    default: $typeLabel = $row[$col] ?? '';
                                                                                                                }
                                                                                                                echo htmlspecialchars($typeLabel);
                                                                                                            }
                                                                                                            // Other custom logic (existing cases)
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'subscriptions' &&
                                                                                                                $lowerCol === 'type' &&
                                                                                                                isset($row[$col])
                                                                                                            ) {
                                                                                                                echo htmlspecialchars($typeMap[$row[$col]] ?? $row[$col]);
                                                                                                            }
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'subscriptions' &&
                                                                                                                $lowerCol === 'price' &&
                                                                                                                isset($row[$col])
                                                                                                            ) {
                                                                                                                $priceGroups = explode(',', $row[$col]);
                                                                                                                $formattedPrices = [];
                                                                                                                foreach ($priceGroups as $group) {
                                                                                                                    $parts = explode(':', $group);
                                                                                                                    if (count($parts) === 2) {
                                                                                                                        $label = trim($parts[0]);
                                                                                                                        $amount = trim($parts[1]);
                                                                                                                        $formattedPrices[] = "<span class='badge bg-primary me-1'>{$label}: <b>{$amount}</b></span>";
                                                                                                                    }
                                                                                                                }
                                                                                                                echo implode(' ', $formattedPrices);
                                                                                                            }
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'subscriptions' &&
                                                                                                                in_array($lowerCol, ['capacity' ]) &&
                                                                                                                (isset($row['type']) && $row['type'] != 1)
                                                                                                            ) {
                                                                                                                echo 'N/A';
                                                                                                            }
                                                                                                            elseif (
                                                                                                                strtolower($table) === 'feedback' &&
                                                                                                                $lowerCol === 'stars' &&
                                                                                                                isset($row[$col])
                                                                                                            ) {
                                                                                                                $rating = (int)$row[$col];
                                                                                                                for ($i = 1; $i <= 5; $i++) {
                                                                                                                    echo $i <= $rating
                                                                                                                        ? '<i class="mdi mdi-star" style="color: #FFD700; font-size: 1.2em;"></i>'
                                                                                                                        : '<i class="mdi mdi-star-outline" style="color: #FFD700; font-size: 1.2em;"></i>';
                                                                                                                }
                                                                                                            }
                                                                                                            // Format date columns
                                                                                                            elseif (stripos($col, 'date') !== false && isset($row[$col]) && !empty($row[$col])) {
                                                                                                                echo formatDisplayDate($row[$col]);
                                                                                                            }
                                                                                                            else {
                                                                                                                echo htmlspecialchars($row[$col] ?? '');
                                                                                                            }
                                                                                                            ?>
                                                                                                        </td>
                                                                                                    <?php endif; ?>
                                                                                                <?php endif; ?>
                                                                                            <?php endforeach; ?>
                                                                               <?php if (strtolower($table) !== 'feedback'): ?>
<td>
    <?php
    $encodedRow = json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    ?>
    <?php if (!isset($_SESSION['user']) || $_SESSION['user']['type'] != 0): ?>
        <button class="btn btn-sm btn-info editRowBtn"
            data-row='<?php echo htmlspecialchars($encodedRow, ENT_QUOTES, 'UTF-8'); ?>'>
            <i class="mdi mdi-pencil"></i>
        </button>
        <a href="table.php?table=<?php echo urlencode($table); ?>&action=delete&id=<?php echo urlencode($row['id'] ?? ''); ?>" 
            class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($_GET['table'] ?? ''))); ?>?');">
            <i class="mdi mdi-delete"></i>
        </a>
    <?php endif; ?>
    <button class="btn btn-sm btn-secondary viewRowBtn"
        data-row='<?php echo htmlspecialchars($encodedRow, ENT_QUOTES, 'UTF-8'); ?>'>
        <i class="mdi mdi-eye"></i>
    </button>
    <?php
    if (
        isset($_SESSION['user']) &&
        $_SESSION['user']['type'] == 0 &&
        strtolower($table) === 'transactions' &&
        (int)($row['status'] ?? 0) === 0 // Pending only
    ): ?>
        <a href="table.php?table=<?php echo urlencode($table); ?>&action=cancel&id=<?php echo urlencode($row['id'] ?? ''); ?>"
           class="btn btn-sm btn-warning"
           onclick="return confirm('Are you sure you want to cancel this transaction?');">
            <i class="mdi mdi-cancel"></i> 
        </a>
    <?php endif; ?>
</td>
<?php endif; ?>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-warning">No data available for this table.</div>
                        <?php endif; ?>
                    </div> <!-- end preview-->
                </div> <!-- end tab-content-->
            </div> <!-- end card body-->
        </div> <!-- end card -->
    </div><!-- end col-->
</div> <!-- end row-->

 
<!-- Default Add Modal (other tables) -->
<div class="modal fade" id="addRowModal" tabindex="-1" aria-labelledby="addRowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="addRowForm" method="POST" enctype="multipart/form-data" action="table.php?action=add&table=<?php echo urlencode($table); ?>">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addRowModalLabel">Add <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($_GET['table'] ?? ''))); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php foreach ($result['columns'] as $col): ?>
                                          <?php if (!in_array(strtolower($col), $excluded) && strtolower($col) !== 'id_card'): ?> 
                            <?php if (strtolower($table) === 'subscriptions' && strtolower($col) === 'price'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?> (Enter prices for each group)</label>
                                    <?php
                                    $groups = ['Female', 'Male', 'PWD', 'Pregnant', 'Children', 'Senior'];
                                    foreach ($groups as $group): ?>
                                        <div class="input-group mb-1">
                                            <span class="input-group-text"><?php echo $group; ?></span>
                                            <input type="number" class="form-control" name="price[<?php echo $group; ?>]" min="0" step="any">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (strtolower($table) === 'news' && strtolower($col) === 'discount'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?> (Enter discount % for each group)</label>
                                    <?php
                                    $groups = ['Female', 'Male', 'PWD', 'Pregnant', 'Children', 'Senior'];
                                    foreach ($groups as $group): ?>
                                        <div class="input-group mb-1">
                                            <span class="input-group-text"><?php echo $group; ?></span>
                                            <input type="number" class="form-control" name="discount[<?php echo $group; ?>]" min="0" max="100" step="any">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (strtolower($table) === 'news' && strtolower($col) === 'photo'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?> (Upload Images)</label>
                                    <input type="file" class="form-control" name="photo[]" accept="image/*" multiple>
                                </div>
                            <?php elseif (strtolower($table) === 'news' && strtolower($col) === 'featured'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <select class="form-control" name="<?php echo htmlspecialchars($col); ?>">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                            <?php elseif (strtolower($table) === 'subscriptions' && strtolower($col) === 'type'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <select class="form-control" name="<?php echo htmlspecialchars($col); ?>" id="addTypeSelect">
                                        <?php foreach ($typeMap as $key => $label): ?>
                                            <option value="<?php echo $key; ?>"><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php elseif (strtolower($col) === 'capacity'): ?>
                                <div class="mb-3" id="add_capacity_field">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <input type="text" class="form-control" name="<?php echo htmlspecialchars($col); ?>">
                                </div>
                            <?php elseif (strtolower($col) === 'pax'): ?>
                                <div class="mb-3" id="add_pax_field">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <input type="text" class="form-control" name="<?php echo htmlspecialchars($col); ?>">
                                </div>
                            <?php elseif (strtolower($table) === 'users' && strtolower($col) === 'number'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <input
                                        type="tel"
                                        class="form-control"
                                        name="<?php echo htmlspecialchars($col); ?>"
                                        value="63"
                                        pattern="\639\d{9}"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 12); if (!this.value.startsWith('63')) this.value = '63' + this.value.slice(2);"
                                        maxlength="12"
                                        placeholder="639XXXXXXXXX"
                                    >
                                </div>
                            <?php elseif (strtolower($table) === 'users' && strtolower($col) === 'birthdate'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <input
                                        type="date"
                                        class="form-control"
                                        name="<?php echo htmlspecialchars($col); ?>">
                                </div>
                            <?php elseif (strtolower($table) === 'users' && strtolower($col) === 'type'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <select class="form-control" name="<?php echo htmlspecialchars($col); ?>">
                                        <option value="0">User</option>
                                        <option value="1">Admin</option>
                                    </select>
                                </div>
                            <?php elseif (strtolower($table) === 'users' && strtolower($col) === 'gender'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <select class="form-control" name="<?php echo htmlspecialchars($col); ?>">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                </div>
                            <?php elseif (strtolower($table) === 'users' && strtolower($col) === 'club_500'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <input type="hidden" name="club_500" value="0">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="add_club_500" name="club_500" value="1">
                                        <label class="form-check-label" for="add_club_500">Enable Club 500</label>
                                    </div>
                                </div>
                            <?php elseif (strtolower($table) === 'users' && strtolower($col) === 'profile'): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?> (Upload Image)</label>
                                    <input type="file" class="form-control" name="<?php echo htmlspecialchars($col); ?>" accept="image/*">
                                </div>
                            <?php elseif (stripos($col, 'date') !== false): ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <input type="datetime-local" class="form-control" name="<?php echo htmlspecialchars($col); ?>">
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo ucwords(str_replace('_', ' ', $col)); ?></label>
                                    <input type="text" class="form-control" name="<?php echo htmlspecialchars($col); ?>">
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (strtolower($table) === 'users'): ?>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Add</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
 
<script>
document.addEventListener('DOMContentLoaded', function() {
    var typeSelect = document.getElementById('addTypeSelect');
    if (typeSelect) {
        function toggleCapacityPaxFields() {
            // 1 is Event Venues
            var show = typeSelect.value == '1';
            var capacityField = document.getElementById('add_capacity_field');
            var paxField = document.getElementById('add_pax_field');
            if (capacityField) capacityField.style.display = show ? '' : 'none';
            if (paxField) paxField.style.display = show ? '' : 'none';
        }
        typeSelect.addEventListener('change', toggleCapacityPaxFields);
        // Initial state
        toggleCapacityPaxFields();
    }

    // Convert club_500 table column values to readable badges for faster UI rendering
    try {
        var ths = document.querySelectorAll('#datatable-buttons thead th');
        var clubIndex = -1;
        ths.forEach(function(th, i) {
            var t = th.textContent.trim().toLowerCase();
            if (t === 'club 500' || t === 'club_500' || t === 'club500') clubIndex = i;
        });
        if (clubIndex > -1) {
            document.querySelectorAll('#datatable-buttons tbody tr').forEach(function(tr) {
                var td = tr.children[clubIndex];
                if (!td) return;
                var v = td.textContent.trim();
                var html = (v === '1' || v.toLowerCase() === '1' || v.toLowerCase() === 'yes') 
                    ? '<span class="badge bg-success">Yes</span>' 
                    : '<span class="badge bg-secondary">No</span>';
                td.innerHTML = html;
            });
        }
    } catch (e) {
        // silently ignore
    }
});
</script>

<?php if (strtolower($table) === 'transactions'): ?>
<!-- Edit Modal for Transactions -->
<div class="modal fade" id="editRowModal" tabindex="-1" aria-labelledby="editRowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editRowForm" method="POST" enctype="multipart/form-data" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRowModalLabel">Edit Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editRowFields">
                    <!-- Fields will be populated by JS -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- View Modal for Transactions -->
<div class="modal fade receipt-modal" id="viewRowModal" tabindex="-1" aria-labelledby="viewRowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewRowModalLabel">Transaction Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body position-relative">
                <button class="print-button" onclick="window.print()">
                    <i class="mdi mdi-printer"></i> Print
                </button>
                <div class="receipt-container" id="viewRowFields">
                    <!-- Receipt content will be generated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
var typeMap = {
    0: 'Tickets',
    1: 'Event Venues',
    2: 'Membership'
};
var subscriptions = <?php echo json_encode($subscriptions['data'] ?? []); ?>;
var discounts = <?php echo json_encode($discounts['data'] ?? []); ?>;
var users = <?php echo json_encode($usernames['data'] ?? []); ?>;
var userMap = {};

function getSubscriptionName(id) {
    var sub = subscriptions.find(function(s){ return s.id == id; });
    return sub ? sub.name : id;
}
function getDiscountName(id) {
    var d = discounts.find(function(s){ return s.id == id; });
    return d ? d.name : id;
}
function getSubscriptionPriceObj(id) {
    var sub = subscriptions.find(function(s){ return s.id == id; });
    if (!sub) return {};
    var obj = {};
    sub.price.split(',').forEach(function(g){
        var p = g.split(':');
        if (p.length === 2) obj[p[0].trim()] = parseFloat(p[1]);
    });
    return obj;
}
function getDiscountObj(id) {
    var d = discounts.find(function(s){ return s.id == id; });
    if (!d) return {};
    var obj = {};
    if (d.discount) d.discount.split(',').forEach(function(g){
        var p = g.split(':');
        if (p.length === 2) obj[p[0].trim()] = parseFloat(p[1]);
    });
    return obj;
}
function calcAmount(subId, discId, cats) {
    var subPrices = getSubscriptionPriceObj(subId);
    var discPerc = getDiscountObj(discId);
    var total = 0;
    Object.keys(cats).forEach(function(cat){
        var qty = parseInt(cats[cat]) || 0;
        var price = subPrices[cat] || 0;
        var disc = discPerc[cat] || 0;
        var discounted = price - (price * (disc/100));
        total += qty * discounted;
    });
    return Math.round(total*100)/100;
}
 // ...existing code...
// Replace the transaction edit/view bindings with delegated handlers
document.addEventListener('click', function(event) {
    var editBtn = event.target.closest('.editRowBtn');
    if (editBtn) {
        event.preventDefault();
        var data = editBtn.getAttribute('data-row');
        var row;
        try { row = JSON.parse(data); } catch (e) { return; }

        var cats = ['Female','Male','PWD','Pregnant','Children','Senior'];
        var paymentInfo = {};
        if (row.payment_info) {
            row.payment_info.split(',').forEach(function(kv){
                var p = kv.split(':');
                if (p.length === 2) paymentInfo[p[0].trim()] = p[1].trim();
            });
        }
        var fieldsHtml = '';
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">User</label>
            <input type="text" class="form-control" value="${(users.find(function(u){ return u.id == row.user_id; }) || {}).name || row.user_id}" readonly>
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Subscription</label>
            <select class="form-control" name="subscription" id="edit_subscription_select">
                ${subscriptions.map(function(s){
                    return `<option value="${s.id}" ${row.subscription==s.id?'selected':''}>${s.name}</option>`;
                }).join('')}
            </select>
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Type</label>
            <input type="text" class="form-control" value="${typeMap[row.type]||row.type}" readonly>
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Reservation Date</label>
            <input type="datetime-local" class="form-control" name="reservation_date" value="${row.reservation_date||''}">
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">End Date</label>
            <input type="datetime-local" class="form-control" name="end_date" value="${row.end_date||''}">
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select class="form-control" name="payment_method" id="edit_payment_method">
                <option value="Gcash" ${paymentInfo.Payment=='Gcash'?'selected':''}>Gcash</option>
                <option value="Maya" ${paymentInfo.Payment=='Maya'?'selected':''}>Maya</option>
                <option value="Bank" ${paymentInfo.Payment=='Bank'?'selected':''}>Bank</option>
            </select>
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Discount</label>
            <select class="form-control" name="discount_id" id="edit_discount_select">
                <option value="">None</option>
                ${discounts.map(function(d){
                    return `<option value="${d.id}" ${row.discount_id==d.id?'selected':''}>${d.name}</option>`;
                }).join('')}
            </select>
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Payment Proof</label>
            <input type="file" class="form-control" name="proof" accept="image/*">
            ${paymentInfo.Proof?`<img src="assets/images/payments/${paymentInfo.Proof}" style="height:40px;width:60px;object-fit:cover;border-radius:6px;margin-top:4px;">`:''}
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Pax</label>`;
        cats.forEach(function(cat){
            fieldsHtml += `<div class="input-group mb-1">
                <span class="input-group-text">${cat}</span>
                <input type="number" class="form-control edit-cat-qty" name="cat[${cat}]" min="0" value="${paymentInfo[cat]||'0'}" data-cat="${cat}">
            </div>`;
        });
        fieldsHtml += `</div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Amount</label>
            <input type="number" class="form-control" name="amount" id="edit_amount_field" value="${paymentInfo.Amount||''}" readonly>
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-control" name="status">
                <option value="0" ${row.status==0?'selected':''}>Pending</option>
                <option value="1" ${row.status==1?'selected':''}>Approved</option>
                <option value="2" ${row.status==2?'selected':''}>Canceled</option>
                <option value="3" ${row.status==3?'selected':''}>Expired</option>
            </select>
        </div>`;
        fieldsHtml += `<div class="mb-3">
            <label class="form-label">Tracking ID</label>
            <input type="text" class="form-control" value="${row.unique_id||''}" readonly>
        </div>`;

        document.getElementById('editRowFields').innerHTML = fieldsHtml;

        function updateAmount() {
            var subId = document.getElementById('edit_subscription_select').value;
            var discId = document.getElementById('edit_discount_select').value;
            var catsObj = {};
            document.querySelectorAll('.edit-cat-qty').forEach(function(inp){
                catsObj[inp.dataset.cat] = inp.value;
            });
            var amt = calcAmount(subId, discId, catsObj);
            var fld = document.getElementById('edit_amount_field');
            if (fld) fld.value = amt;
        }
        var subSel = document.getElementById('edit_subscription_select');
        var discSel = document.getElementById('edit_discount_select');
        if (subSel) subSel.addEventListener('change', updateAmount);
        if (discSel) discSel.addEventListener('change', updateAmount);
        document.querySelectorAll('.edit-cat-qty').forEach(function(inp){
            inp.addEventListener('input', updateAmount);
        });
        updateAmount();
        var editForm = document.getElementById('editRowForm');
        if (editForm) {
            editForm.setAttribute('data-id', row['id'] ?? '');
            editForm.setAttribute('action', 'table.php?action=edit&table=transactions&id=' + encodeURIComponent(row['id'] ?? ''));
        }
        var editModal = new bootstrap.Modal(document.getElementById('editRowModal'));
        editModal.show();
        return;
    }

    var viewBtn = event.target.closest('.viewRowBtn');
    if (viewBtn) {
        event.preventDefault();
        var data = viewBtn.getAttribute('data-row');
        var row;
        try { row = JSON.parse(data); } catch (e) { return; }

        var cats = ['Female','Male','PWD','Pregnant','Children','Senior'];
        var paymentInfo = {};
        var paymentInfoRaw = row.payment_info || '';
        var amount = '';
        var payment = '';
        var proof = '';
        var categories = [];

        paymentInfoRaw.split(',').forEach(function(kv){
            var p = kv.split(':');
            if (p.length === 2) {
                var k = p[0].trim();
                var v = p[1].trim();
                if (k.toLowerCase() === 'amount') amount = v;
                else if (k.toLowerCase() === 'payment') payment = v;
                else if (k.toLowerCase() === 'proof') proof = v;
                else paymentInfo[k] = v;
            } else if (p.length === 5) {
                var cat = p[0];
                var count = p[1];
                var orig = p[2];
                var disc = p[3];
                var subt = p[4];
                categories.push({
                    category: cat,
                    count: count,
                    price: orig,
                    discount: disc,
                    subtotal: subt
                });
            }
        });

        var user = users.find(function(u){ return u.id == row.user_id; });
        var userName = user ? user.name : 'Unknown User';
        var reservationDate = row.reservation_date ? new Date(row.reservation_date).toLocaleDateString() : '';
        var endDate = row.end_date ? new Date(row.end_date).toLocaleDateString() : '';
        var createdDate = row.created_at ? new Date(row.created_at).toLocaleDateString() : '';
        var days = 0;
        if (row.reservation_date && row.end_date) {
            var start = new Date(row.reservation_date);
            var end = new Date(row.end_date);
            days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        }
        var statusText = '';
        var statusClass = '';
        switch(parseInt(row.status)) {
            case 1:
                statusText = 'Approved'; statusClass = 'status-approved'; break;
            case 2:
                statusText = 'Canceled'; statusClass = 'status-canceled'; break;
            case 3:
                statusText = 'Expired'; statusClass = 'status-expired'; break;
            default:
                statusText = 'Pending'; statusClass = 'status-pending';
        }

        var receiptHtml = `
            <div class="receipt-header">
                <img src="assets/images/logo.jpg" alt="Logo" class="receipt-logo">
                <h2 class="receipt-title">WONDA AR</h2>
                <p class="receipt-subtitle">Transaction Receipt</p>
            </div>

            <div class="receipt-body">
                <div class="receipt-section">
                    <div class="receipt-section-title">Transaction Details</div>
                    <div class="receipt-row">
                        <span>Receipt #:</span>
                        <span><strong>${row.unique_id || ''}</strong></span>
                    </div>
                    <div class="receipt-row">
                        <span>Date:</span>
                        <span>${createdDate}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Customer:</span>
                        <span>${userName}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Status:</span>
                        <span><span class="status-badge ${statusClass}">${statusText}</span></span>
                    </div>
                </div>

                <div class="receipt-section">
                    <div class="receipt-section-title">Reservation Details</div>
                    <div class="receipt-row">
                        <span>Subscription:</span>
                        <span>${getSubscriptionName(row.subscription)}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Type:</span>
                        <span>${typeMap[row.type] || row.type}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Check-in:</span>
                        <span>${reservationDate}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Check-out:</span>
                        <span>${endDate}</span>
                    </div>
                    <div class="receipt-row">
                        <span>Duration:</span>
                        <span>${days} day(s)</span>
                    </div>
                </div>

                <div class="receipt-section">
                    <div class="receipt-section-title">Guest Information</div>`;
        if (categories.length > 0) {
            categories.forEach(function(cat) {
                receiptHtml += `
                    <div class="receipt-row">
                        <span>${cat.category}:</span>
                        <span>${cat.count} × ₱${cat.price} ${cat.discount > 0 ? '(-' + cat.discount + '%)' : ''}</span>
                    </div>`;
            });
        } else {
            cats.forEach(function(cat) {
                var count = paymentInfo[cat] || '0';
                if (count > 0) {
                    receiptHtml += `
                        <div class="receipt-row">
                            <span>${cat}:</span>
                            <span>${count} guest(s)</span>
                        </div>`;
                }
            });
        }
        receiptHtml += `
                </div>

                <div class="receipt-section">
                    <div class="receipt-section-title">Payment Information</div>
                    <div class="receipt-row">
                        <span>Payment Method:</span>
                        <span>${payment || 'N/A'}</span>
                    </div>
                    <div class="receipt-row total">
                        <span>Total Amount:</span>
                        <span><strong>₱${amount || '0.00'}</strong></span>
                    </div>
                </div>
            </div>

            <div class="receipt-qr">
                <div id="qrcode-container">
                    <canvas id="qrcode-canvas"></canvas>
                </div>
                <div class="receipt-footer">
                    <p>Scan QR code for verification</p>
                    <p>Thank you for your business!</p>
                </div>
            </div>
        `;
        document.getElementById('viewRowFields').innerHTML = receiptHtml;
        var viewModal = new bootstrap.Modal(document.getElementById('viewRowModal'));
        viewModal.show();
        document.getElementById('viewRowModal').addEventListener('shown.bs.modal', function() {
            generateQRCode(row.unique_id);
        }, { once: true });
        return;
    }
});
// ...existing code...

// Function to generate QR code
function generateQRCode(uniqueId) {
    var qrContainer = document.getElementById('qrcode-container');
    
    if (!qrContainer) {
        console.error('QR code container not found');
        return;
    }
    
    if (!uniqueId) {
        console.error('No unique ID provided for QR code');
        qrContainer.innerHTML = '<p style="text-align: center; color: #999;">No tracking ID available</p>';
        return;
    }
    
    console.log('Generating QR code for:', uniqueId);
    
    // Clear any existing content
    qrContainer.innerHTML = '';
    
    // Try using QRious library first
    if (window.QRCodeAvailable && typeof QRious !== 'undefined') {
        try {
            // Create canvas element
            var canvas = document.createElement('canvas');
            canvas.id = 'qrcode-canvas';
            canvas.style.maxWidth = '200px';
            canvas.style.maxHeight = '200px';
            canvas.style.background = 'white';
            canvas.style.border = '2px solid #ddd';
            canvas.style.borderRadius = '8px';
            canvas.style.display = 'block';
            canvas.style.margin = '0 auto 15px auto';
            
            qrContainer.appendChild(canvas);
            
            var qr = new QRious({
                element: canvas,
                value: uniqueId,
                size: 200,
                background: 'white',
                foreground: 'black',
                level: 'M'
            });
            
            console.log('QR code generated successfully with QRious');
            
            // Add logo overlay after a short delay
            setTimeout(function() {
                addLogoToCanvas(canvas);
            }, 100);
            
        } catch (error) {
            console.error('QRious generation failed:', error);
            fallbackQRGeneration(uniqueId, qrContainer);
        }
    } else {
        console.log('QRious not available, using fallback');
        fallbackQRGeneration(uniqueId, qrContainer);
    }
}

// Fallback QR generation using online service
function fallbackQRGeneration(uniqueId, container) {
    var img = document.createElement('img');
    img.src = generateSimpleQR(uniqueId, 200);
    img.style.maxWidth = '200px';
    img.style.maxHeight = '200px';
    img.style.border = '2px solid #ddd';
    img.style.borderRadius = '8px';
    img.style.display = 'block';
    img.style.margin = '0 auto 15px auto';
    img.alt = 'QR Code';
    
    img.onload = function() {
        console.log('Fallback QR code loaded successfully');
        // Convert image to canvas for logo overlay
        var canvas = document.createElement('canvas');
        canvas.width = 200;
        canvas.height = 200;
        canvas.style.border = '2px solid #ddd';
        canvas.style.borderRadius = '8px';
        canvas.style.display = 'block';
        canvas.style.margin = '0 auto 15px auto';
        
        var ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, 200, 200);
        
        container.appendChild(canvas);
        
        // Add logo overlay
        setTimeout(function() {
            addLogoToCanvas(canvas);
        }, 100);
    };
    
    img.onerror = function() {
        console.error('Fallback QR generation failed');
        container.innerHTML = '<p style="text-align: center; color: #999;">Failed to generate QR code</p>';
    };
}

// Function to add logo overlay to canvas
function addLogoToCanvas(canvas) {
    var ctx = canvas.getContext('2d');
    var logo = new Image();
    
    logo.onload = function() {
        var logoSize = 40;
        var x = (canvas.width - logoSize) / 2;
        var y = (canvas.height - logoSize) / 2;
        
        // Draw white background for logo
        ctx.fillStyle = 'white';
        ctx.fillRect(x - 5, y - 5, logoSize + 10, logoSize + 10);
        
        // Draw logo
        ctx.drawImage(logo, x, y, logoSize, logoSize);
        console.log('Logo added to QR code');
    };
    
    logo.onerror = function() {
        console.warn('Logo failed to load, QR code will display without logo');
    };
    
    // Try different logo paths
    logo.src = 'assets/images/logo.jpg';
}
</script>
<?php else: ?>
<!-- Default Edit/View Modal (other tables) -->
<!-- Edit Modal -->
<div class="modal fade" id="editRowModal" tabindex="-1" aria-labelledby="editRowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editRowForm" method="POST" enctype="multipart/form-data" action="">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRowModalLabel">Edit <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($_GET['table'] ?? ''))); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editRowFields">
                    <!-- Fields will be populated by JS -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- View Modal -->
<div class="modal fade" id="viewRowModal" tabindex="-1" aria-labelledby="viewRowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewRowModalLabel"><?php echo ucwords(str_replace('_', ' ', htmlspecialchars($_GET['table'] ?? ''))); ?> Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewRowFields">
                <!-- Fields will be populated by JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script>
var typeMap = {
    0: 'Tickets',
    1: 'Event Venues',
    2: 'Membership'
};
var addRowBtn = document.getElementById('addRowBtn');
if (addRowBtn) {
    addRowBtn.addEventListener('click', function() {
        var addModal = new bootstrap.Modal(document.getElementById('addRowModal'));
        addModal.show();
    });
}

document.addEventListener('click', function(event) {
    var btn = event.target.closest('.editRowBtn');
    if (!btn) return;
    event.preventDefault();

    var data = btn.getAttribute('data-row');
    var row;
    try { row = JSON.parse(data); } catch (e) { return; }

    var excluded = ['id', 'created_at', 'updated_at', 'password'];
    var fieldsHtml = '';
    fieldsHtml += '<input type="hidden" name="id" value="' + (row['id'] || '') + '">';

    Object.keys(row).forEach(function(k) {
        if (excluded.indexOf(k.toLowerCase()) !== -1) return;
        var val = row[k] !== null && row[k] !== undefined ? row[k] : '';

        if (k.toLowerCase() === 'name' || k.toLowerCase() === 'email' || k.toLowerCase() === 'username') {
            fieldsHtml += '<div class="mb-3"><label class="form-label">' + k + '</label>';
            fieldsHtml += '<input type="text" class="form-control" name="' + k + '" value="' + (String(val)).replace(/"/g,'&quot;') + '"></div>';
        }
        else if (k.toLowerCase() === 'number') {
            fieldsHtml += '<div class="mb-3"><label class="form-label">Number</label>';
            fieldsHtml += '<input type="tel" class="form-control" name="number" value="' + (String(val)).replace(/"/g,'&quot;') + '" maxlength="12"></div>';
        }
        else if (k.toLowerCase() === 'birthdate') {
            fieldsHtml += '<div class="mb-3"><label class="form-label">Birthdate</label>';
            fieldsHtml += '<input type="date" class="form-control" name="birthdate" value="' + (String(val)).replace(/"/g,'&quot;') + '"></div>';
        }
        else if (k.toLowerCase() === 'gender') {
            fieldsHtml += '<div class="mb-3"><label class="form-label">Gender</label>';
            fieldsHtml += '<select class="form-control" name="gender"><option value="Male"' + ((String(val) === 'Male') ? ' selected' : '') + '>Male</option>';
            fieldsHtml += '<option value="Female"' + ((String(val) === 'Female') ? ' selected' : '') + '>Female</option></select></div>';
        }
        else if (k.toLowerCase() === 'type') {
            fieldsHtml += '<div class="mb-3"><label class="form-label">Type</label>';
            fieldsHtml += '<select class="form-control" name="type"><option value="0"' + (String(val) == '0' ? ' selected' : '') + '>User</option>';
            fieldsHtml += '<option value="1"' + (String(val) == '1' ? ' selected' : '') + '>Admin</option></select></div>';
        }
        else if (k.toLowerCase() === 'club_500') {
            fieldsHtml += '<div class="mb-3"><label class="form-label">Club 500</label>';
            fieldsHtml += '<input type="hidden" name="club_500" value="0">';
            fieldsHtml += '<div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="edit_club_500" name="club_500" value="1"' + (String(val) == '1' ? ' checked' : '') + '>';
            fieldsHtml += '<label class="form-check-label" for="edit_club_500">Enable Club 500</label></div></div>';
        }
        else if (k.toLowerCase() === 'profile' || k.toLowerCase() === 'id_card') {
            fieldsHtml += '<div class="mb-3"><label class="form-label">' + k + ' (Upload)</label>';
            fieldsHtml += '<input type="file" class="form-control" name="' + k + '" accept="image/*">';
            if (val) {
                var safe = String(val).replace(/"/g,'&quot;');
                fieldsHtml += '<div style="margin-top:6px;"><img src="assets/images/users/' + safe + '" style="height:40px;width:40px;border-radius:50%;object-fit:cover;"></div>';
            }
            fieldsHtml += '</div>';
        }
        else if (k.toLowerCase().indexOf('date') !== -1) {
            fieldsHtml += '<div class="mb-3"><label class="form-label">' + k + '</label>';
            fieldsHtml += '<input type="datetime-local" class="form-control" name="' + k + '" value="' + (String(val)).replace(/"/g,'&quot;') + '"></div>';
        }
        else {
            fieldsHtml += '<div class="mb-3"><label class="form-label">' + k + '</label>';
            fieldsHtml += '<input type="text" class="form-control" name="' + k + '" value="' + (String(val)).replace(/"/g,'&quot;') + '"></div>';
        }
    });

    fieldsHtml += '<div class="mb-3"><label class="form-label">Password (leave blank to keep current)</label>';
    fieldsHtml += '<input type="password" class="form-control" name="password" value=""></div>';

    var editFields = document.getElementById('editRowFields');
    if (!editFields) return;
    editFields.innerHTML = fieldsHtml;

    var editForm = document.getElementById('editRowForm');
    if (editForm) {
        editForm.setAttribute('action', 'table.php?action=edit&table=users&id=' + encodeURIComponent(row['id'] || ''));
    }

    var editModal = new bootstrap.Modal(document.getElementById('editRowModal'));
    editModal.show();
});
 </script>
<?php endif; ?>

                    </div> <!-- container -->

                </div> <!-- content -->

                <!-- Footer Start -->
                <footer class="footer">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-6">
                                <script>document.write(new Date().getFullYear())</script> © WondaGo
                            </div>
                            <div class="col-md-6">
                                <div class="text-md-end footer-links d-none d-md-block">
                                    <a href="javascript: void(0);">About</a>
                                    <a href="javascript: void(0);">Support</a>
                                    <a href="javascript: void(0);">Contact Us</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </footer>
                <!-- end Footer -->

            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->


<?php require 'includes/right-sidebar.php'; ?>   
 

        <div class="rightbar-overlay"></div>
        <!-- /End-bar -->


        <!-- QR Code Library -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
        <script>
            // Fallback QR generation function using a simple approach
            function generateSimpleQR(text, size) {
                return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(text)}`;
            }
            
            // Check if QRious library loaded
            if (typeof QRious === 'undefined') {
                console.log('QRious library not loaded, using fallback');
                window.QRCodeAvailable = false;
            } else {
                window.QRCodeAvailable = true;
            }
        </script>
        
        <!-- bundle -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>

        <!-- third party js -->
        <script src="assets/js/vendor/jquery.dataTables.min.js"></script>
        <script src="assets/js/vendor/dataTables.bootstrap5.js"></script>
        <script src="assets/js/vendor/dataTables.responsive.min.js"></script>
        <script src="assets/js/vendor/responsive.bootstrap5.min.js"></script>
        <script src="assets/js/vendor/dataTables.buttons.min.js"></script>
        <script src="assets/js/vendor/buttons.bootstrap5.min.js"></script>
        <script src="assets/js/vendor/buttons.html5.min.js"></script>
        <script src="assets/js/vendor/buttons.flash.min.js"></script>
        <script src="assets/js/vendor/buttons.print.min.js"></script>
        <script src="assets/js/vendor/jszip.min.js"></script>
        <script src="assets/js/vendor/pdfmake.min.js"></script>
        <script src="assets/js/vendor/vfs_fonts.js"></script>
        <script src="assets/js/vendor/dataTables.keyTable.min.js"></script>
        <script src="assets/js/vendor/dataTables.select.min.js"></script>
        <script src="assets/js/vendor/fixedColumns.bootstrap5.min.js"></script>
        <script src="assets/js/vendor/fixedHeader.bootstrap5.min.js"></script>
        <!-- third party js ends -->

        <!-- demo app -->
        <script src="assets/js/pages/demo.datatable-init.js"></script>
        <!-- end demo js-->

    </body>

</html>
