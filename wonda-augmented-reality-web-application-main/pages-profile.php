<?php require_once 'includes/config.php'; ?>  
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
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle profile image upload
    $profileFileName = $_SESSION['user']['profile'] ?? '';
    if (isset($_FILES['profile']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['profile']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newName = uniqid('profile_', true) . '.' . $ext;
            $target = 'assets/images/users/' . $newName;
            if (move_uploaded_file($_FILES['profile']['tmp_name'], $target)) {
                $profileFileName = $newName;
                $_SESSION['user']['profile'] = $profileFileName;
            }
        }
    }

    // Handle id_card upload
    $idCardFileName = $_SESSION['user']['id_card'] ?? '';
if (isset($_FILES['id_card']) && $_FILES['id_card']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $ext = strtolower(pathinfo($_FILES['id_card']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $allowed)) {
        $newName = uniqid('idcard_', true) . '.' . $ext;
        $target = 'assets/images/users/' . $newName;
        if (move_uploaded_file($_FILES['id_card']['tmp_name'], $target)) {
            $idCardFileName = $newName;
            $_SESSION['user']['id_card'] = $idCardFileName; // This line updates session
        }
    }
}

    

    // Update session user data
    $_SESSION['user']['name'] = $_POST['name'] ?? $_SESSION['user']['name'];
    $_SESSION['user']['birthdate'] = $_POST['birthdate'] ?? $_SESSION['user']['birthdate'];
    $_SESSION['user']['address'] = $_POST['address'] ?? $_SESSION['user']['address'];
    $_SESSION['user']['email'] = $_POST['email'] ?? $_SESSION['user']['email'];
    $_SESSION['user']['number'] = $_POST['number'] ?? $_SESSION['user']['number'];

    // Handle password update
    $password = $_POST['password'] ?? '';
    $updateData = [
        'name' => $_SESSION['user']['name'],
        'birthdate' => $_SESSION['user']['birthdate'],
        'address' => $_SESSION['user']['address'],
        'email' => $_SESSION['user']['email'],
        'number' => $_SESSION['user']['number'],
    ];
    if ($profileFileName) {
        $updateData['profile'] = $profileFileName;
    }
    if ($idCardFileName) {
        $updateData['id_card'] = $idCardFileName;
    }
    if ($password !== '') {
        $updateData['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    // Remove empty values
    foreach ($updateData as $k => $v) {
        if (is_string($v) && trim($v) === '') unset($updateData[$k]);
    }

    if (isset($_SESSION['user']['id'])) {
        $userId = $_SESSION['user']['id'];
        $result = $db->update('users', $updateData, ['id' => $userId]);
        if ($result['status'] === 'success') {
            echo '<div class="alert alert-success mt-3">Profile updated!</div>';
        } else {
            echo '<div class="alert alert-danger mt-3">Update failed: ' . htmlspecialchars($result['message']) . '</div>';
        }
    } else {
        echo '<div class="alert alert-danger mt-3">User not found in session.</div>';
    }
}
?>
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title">Profile</h4>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-4 col-lg-5">
                            <div class="card text-center">
                                <div class="card-body">
                                    <?php
                                        $profileImg = 'assets/images/user.png';
                                        if (!empty($_SESSION['user']['profile'])) {
                                            $profileImg = 'assets/images/users/' . htmlspecialchars($_SESSION['user']['profile']);
                                        }
                                    ?>
                                    <img src="<?php echo $profileImg; ?>" class="rounded-circle avatar-lg img-thumbnail"
                                    alt="profile-image">
                                    <h4 class="mb-0 mt-2 d-flex justify-content-center align-items-center">
                                        <?php echo htmlspecialchars($_SESSION['user']['name'] ?? 'Dominic Keller'); ?>
                                        <?php if (!empty($_SESSION['user']['club_500']) && $_SESSION['user']['club_500'] == 1): ?>
                                            <span class="ms-2" title="Club 500 Premium">
                                                <i class="mdi mdi-crown text-warning" style="font-size: 1.5em;"></i>
                                            </span>
                                        <?php endif; ?>
                                    </h4>
                                    <?php
                                    if (!empty($_SESSION['user']['club_500']) && $_SESSION['user']['club_500'] == 1) {
                                        echo '<span class="badge bg-warning text-dark mb-2"><i class="mdi mdi-crown"></i> Club 500 Premium</span>';
                                    }
                                    ?>
                                    <?php
                                    $exclude = ['id', 'password', 'created_at', 'updated_at', 'profile', 'name', 'type'];
                                    if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
                                        echo '<div class="text-start mt-3">';
                                        foreach ($_SESSION['user'] as $key => $value) {
                                            if (in_array($key, $exclude)) continue;
                                            $label = ucwords(str_replace('_', ' ', $key));
                                            $display = htmlspecialchars($value ?? 'N/A');
                                            // For id_card, show download link if exists
                                            if ($key === 'id_card' && $display !== '') {
                                                $fileUrl = 'assets/images/id_cards/' . $display;
                                                echo '<p class="text-muted mb-2 font-13"><strong>' . $label . ' :</strong> <span class="ms-2"><a href="' . $fileUrl . '" target="_blank">Download/View</a></span></p>';
                                            } else {
                                                echo '<p class="text-muted mb-2 font-13"><strong>' . $label . ' :</strong> <span class="ms-2">' . ($display !== '' ? $display : 'N/A') . '</span></p>';
                                            }
                                        }
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 col-lg-7">
                            <div class="card">
                                <div class="card-body">
                                    <ul class="nav nav-pills bg-nav-pills nav-justified mb-3">
                                        <li class="nav-item" hidden> 
                                            <a href="#aboutme" data-bs-toggle="tab" aria-expanded="false" class="nav-link rounded-0">
                                                About
                                            </a>
                                        </li>
                                        <li class="nav-item" hidden> 
                                            <a href="#timeline" data-bs-toggle="tab" aria-expanded="false" class="nav-link rounded-0 active">
                                                Timeline
                                            </a>
                                        </li>
                                        <li class="nav-item" hidden>
                                            <a href="#settings" data-bs-toggle="tab" aria-expanded="true" class="nav-link rounded-0 active">
                                                Profile Edit
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane show active" id="settings">
                                            <?php
                                                $user = $_SESSION['user'] ?? [];
                                                $firstName = htmlspecialchars($user['name'] ?? '');
                                                $email = htmlspecialchars($user['email'] ?? '');
                                                $number = htmlspecialchars($user['number'] ?? '');
                                                $address = htmlspecialchars($user['address'] ?? '');
                                                $birthdate = htmlspecialchars($user['birthdate'] ?? '');
                                            ?>
                                            <form method="post" action="" enctype="multipart/form-data">
                                                <h5 class="mb-4 text-uppercase"><i class="mdi mdi-account-circle me-1"></i> Personal Info</h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="firstname" class="form-label">Name</label>
                                                            <input type="text" class="form-control" id="firstname" name="name" value="<?php echo $firstName; ?>" placeholder="Enter name">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="birthdate" class="form-label">Birthdate</label>
                                                            <input type="date" class="form-control" id="birthdate" name="birthdate" value="<?php echo $birthdate; ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="mb-3">
                                                            <label for="userbio" class="form-label">Address</label>
                                                            <input type="text" class="form-control" id="userbio" name="address" value="<?php echo $address; ?>" placeholder="address">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="useremail" class="form-label">Email Address</label>
                                                            <input type="email" class="form-control" id="useremail" name="email" value="<?php echo $email; ?>" placeholder="Enter email">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label for="usernumber" class="form-label">Phone Number</label>
                                                            <input type="text" class="form-control" id="usernumber" name="number" value="<?php echo $number; ?>" placeholder="Enter phone number">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="profile" class="form-label">Profile Image</label>
                                                            <input type="file" class="form-control" id="profile" name="profile" accept="image/*">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="id_card" class="form-label">Valid ID</label>
                                                            <input type="file" class="form-control" id="id_card" name="id_card" accept="image/*,.pdf">
                                                            <?php
                                                            if (!empty($_SESSION['user']['id_card'])) {
                                                                $idCardUrl = 'assets/images/users/' . htmlspecialchars($_SESSION['user']['id_card']);
                                                                echo '<small><a href="' . $idCardUrl . '" target="_blank">Current file</a></small>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="club_500" class="form-label">Club 500</label>
                                                            <select id="club_500" name="club_500" class="form-control">
                                                                <option value="0" <?php echo (empty($_SESSION['user']['club_500']) || $_SESSION['user']['club_500']==0) ? 'selected' : ''; ?>>No</option>
                                                                <option value="1" <?php echo (!empty($_SESSION['user']['club_500']) && $_SESSION['user']['club_500']==1) ? 'selected' : ''; ?>>Yes</option>
                                                            </select>
                                                            <small class="text-muted">Set Club 500 membership</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <label for="password" class="form-label">Password</label>
                                                            <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end"> 
                                                    <button type="submit" class="btn btn-success mt-2"><i class="mdi mdi-content-save"></i> Save</button>
                                                </div>
                                            </form>
                                        </div>
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
