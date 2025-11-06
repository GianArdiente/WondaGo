<?php
require_once 'includes/config.php';

// Get user from session
session_start();
$user = $_SESSION['user'] ?? null;

$paymentJsonFile = 'assets/payment_methods.json';
$imagesDir = 'assets/images/';

// Handle add/edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $methodName = trim($_POST['method_name'] ?? '');
    $imageFileName = '';
    $editIndex = isset($_POST['edit_index']) ? intval($_POST['edit_index']) : null;

    // Handle image upload (optional for edit)
    if (isset($_FILES['method_image']) && $_FILES['method_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['method_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newName = uniqid('paymethod_', true) . '.' . $ext;
            $target = $imagesDir . $newName;
            if (move_uploaded_file($_FILES['method_image']['tmp_name'], $target)) {
                $imageFileName = $newName;
            }
        }
    }

    // Load existing methods
    if (!file_exists($paymentJsonFile)) {
        file_put_contents($paymentJsonFile, json_encode([]));
    }
    $methods = json_decode(file_get_contents($paymentJsonFile), true) ?? [];

    if ($methodName && ($imageFileName || isset($editIndex))) {
        if ($editIndex !== null && isset($methods[$editIndex])) {
            // Edit existing method
            $methods[$editIndex]['name'] = $methodName;
            if ($imageFileName) {
                $methods[$editIndex]['image'] = $imageFileName;
            }
            file_put_contents($paymentJsonFile, json_encode($methods, JSON_PRETTY_PRINT));
            echo '<div class="alert alert-success mt-3">Payment method updated!</div>';
        } elseif ($imageFileName) {
            // Add new method
            $methods[] = [
                'name' => $methodName,
                'image' => $imageFileName
            ];
            file_put_contents($paymentJsonFile, json_encode($methods, JSON_PRETTY_PRINT));
            echo '<div class="alert alert-success mt-3">Payment method added!</div>';
        } else {
            echo '<div class="alert alert-danger mt-3">Please provide a name and valid image.</div>';
        }
    } else {
        echo '<div class="alert alert-danger mt-3">Please provide a name and valid image.</div>';
    }
}

// Load payment methods
$methods = [];
if (file_exists($paymentJsonFile)) {
    $methods = json_decode(file_get_contents($paymentJsonFile), true) ?? [];
}

// For edit form
$editMode = false;
$editIndex = null;
$editName = '';
$editImage = '';
if (isset($_GET['edit']) && isset($methods[$_GET['edit']])) {
    $editMode = true;
    $editIndex = intval($_GET['edit']);
    $editName = htmlspecialchars($methods[$editIndex]['name']);
    $editImage = $imagesDir . htmlspecialchars($methods[$editIndex]['image']);
}
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once 'includes/head.php'; ?>
<body class="loading" data-layout-color="light" data-leftbar-theme="light" data-layout-mode="fluid" data-rightbar-onstart="true">
    <div class="wrapper">
        <?php require_once 'includes/sidebar.php'; ?>
        <?php require_once 'includes/topbar.php'; ?>
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Payment Methods</h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <?php if ($user && $user['type'] != 0): ?>
                        <div class="col-xl-4 col-lg-5">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h5 class="mb-4 text-uppercase"><i class="mdi mdi-credit-card me-1"></i>
                                        <?php echo $editMode ? 'Edit Payment Method' : 'Add Payment Method'; ?>
                                    </h5>
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="mb-3 text-start">
                                            <label for="method_name" class="form-label">Method Name</label>
                                            <input type="text" class="form-control" id="method_name" name="method_name" required value="<?php echo $editMode ? $editName : ''; ?>">
                                        </div>
                                        <div class="mb-3 text-start">
                                            <label for="method_image" class="form-label">Method Image <?php if ($editMode && $editImage): ?><br><img src="<?php echo $editImage; ?>" style="max-width:100px;"><?php endif; ?></label>
                                            <input type="file" class="form-control" id="method_image" name="method_image" accept="image/*" <?php echo $editMode ? '' : 'required'; ?>>
                                        </div>
                                        <?php if ($editMode): ?>
                                            <input type="hidden" name="edit_index" value="<?php echo $editIndex; ?>">
                                        <?php endif; ?>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-success mt-2">
                                                <i class="mdi mdi-content-save"></i>
                                                <?php echo $editMode ? 'Update Method' : 'Add Method'; ?>
                                            </button>
                                            <?php if ($editMode): ?>
                                                <a href="payment.php" class="btn btn-secondary mt-2">Cancel</a>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-xl-8 col-lg-7">
                            <div class="card">
                                <div class="card-body">
                                    <h4>Payment Methods</h4>
                                    <div class="row">
                                        <?php
                                        $count = 0;
                                        foreach ($methods as $idx => $method) {
                                            $imgSrc = $imagesDir . htmlspecialchars($method['image']);
                                            $name = htmlspecialchars($method['name']);
                                            echo '<div class="col-md-4 mb-4 text-center">';
                                            echo '<img src="' . $imgSrc . '" alt="' . $name . '" style="max-width:100%;height:150px;object-fit:cover;border-radius:8px;"><br>';
                                            echo '<strong>' . $name . '</strong><br>';
                                            if ($user && $user['type'] != 0) {
                                                echo '<a href="payment.php?edit=' . $idx . '" class="btn btn-sm btn-primary mt-2">Edit</a>';
                                            }
                                            echo '</div>';
                                            $count++;
                                        }
                                        for ($i = $count; $i < 9; $i++) {
                                            echo '<div class="col-md-4 mb-4"></div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php require 'includes/right-sidebar.php'; ?>
        <div class="rightbar-overlay"></div>
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>
</body>
</html>
