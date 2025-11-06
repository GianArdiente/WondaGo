<?php require_once 'includes/config.php'; ?>  

<?php


$transactions = $db->select('transactions', '*');
$users = $db->select('users', '*');
?>
<!DOCTYPE html>
    <html lang="en">

        <?php require_once 'includes/head.php'; ?>  
    
    <body class="loading" data-layout-color="light" data-leftbar-theme="light" data-layout-mode="fluid" data-rightbar-onstart="true">
        <!-- Begin page -->
        <div class="wrapper">
            <?php require_once 'includes/sidebar.php'; ?>  
            <?php require_once 'includes/topbar.php'; ?>  

            <!-- ============================================================== -->
            <!-- Starthage Content here -->
            <!-- ============================================================== -->

            <div class="content-page">
                <div class="content">
            
                    
                    <!-- Start Content-->
                    <div class="container-fluid">

                        <!-- start page title -->
                        <div class="row">
                            <div class="col-12">
                                <div class="page-title-box">
                                    <!-- <div class="page-title-right">
                                        <form class="d-flex">
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-light" id="dash-daterange">
                                                <span class="input-group-text bg-primary border-primary text-white">
                                                    <i class="mdi mdi-calendar-range font-13"></i>
                                                </span>
                                            </div>
                                            <a href="javascript: void(0);" class="btn btn-primary ms-2">
                                                <i class="mdi mdi-autorenew"></i>
                                            </a>
                                            <a href="javascript: void(0);" class="btn btn-primary ms-1">
                                                <i class="mdi mdi-filter-variant"></i>
                                            </a>
                                        </form>
                                    </div> -->
                                    <h4 class="page-title">Dashboard</h4>
                                </div>
                            </div>
                        </div>
                        <!-- end page title -->

                        <div class="row">
                            <div class="col-xl-5 col-lg-6">

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="card widget-flat">
                                            <div class="card-body">
                                                <div class="float-end">
                                                    <i class="mdi mdi-account-multiple widget-icon"></i>
                                                </div>
                                                <h5 class="text-muted fw-normal mt-0" title="Number of Customers">Customers</h5>
                                                <h3 class="mt-3 mb-3">
                                                    <?php
                                                        // Count users in $users['data']
                                                        echo isset($users['data']) ? count($users['data']) : 0;
                                                    ?>
                                                </h3>
                                                <?php
                                                // Calculate growth since last month
                                                $lastMonthCount = 0;
                                                $currentMonthCount = 0;
                                                if (isset($users['data']) && is_array($users['data'])) {
                                                    $now = new DateTime();
                                                    $currentMonth = $now->format('Y-m');
                                                    $lastMonth = $now->modify('-1 month')->format('Y-m');
                                                    foreach ($users['data'] as $user) {
                                                        if (!empty($user['created_at'])) {
                                                            $createdMonth = substr($user['created_at'], 0, 7);
                                                            if ($createdMonth === $currentMonth) $currentMonthCount++;
                                                            if ($createdMonth === $lastMonth) $lastMonthCount++;
                                                        }
                                                    }
                                                    // Calculate percentage growth
                                                    if ($lastMonthCount > 0) {
                                                        $growth = (($currentMonthCount - $lastMonthCount) / $lastMonthCount) * 100;
                                                    } else {
                                                        $growth = $currentMonthCount > 0 ? 100 : 0;
                                                    }
                                                    $growthClass = $growth >= 0 ? 'text-success' : 'text-danger';
                                                    $growthIcon = $growth >= 0 ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold';
                                                    $growthText = sprintf('%+.2f%%', $growth);
                                                } else {
                                                    $growthClass = 'text-muted';
                                                    $growthIcon = '';
                                                    $growthText = '0.00%';
                                                }
                                                ?>
                                                <p class="mb-0 text-muted">
                                                    <span class="<?php echo $growthClass; ?> me-2">
                                                        <i class="mdi <?php echo $growthIcon; ?>"></i> <?php echo $growthText; ?>
                                                    </span>
                                                    <span class="text-nowrap">Since last month</span>  
                                                </p>
                                            </div> <!-- end card-body-->
                                        </div> <!-- end card-->
                                    </div> <!-- end col-->

                                    <div class="col-sm-6">
                                        <div class="card widget-flat">
                                            <div class="card-body">
                                                <div class="float-end">
                                                    <i class="mdi mdi-cart-plus widget-icon"></i>
                                                </div>
                                                <h5 class="text-muted fw-normal mt-0" title="Number of Pending Transactions">Pending</h5>
                                                <?php
                                                    // Count pending transactions (status = 0)
                                                    $pendingCount = 0;
                                                    $lastMonthPending = 0;
                                                    $currentMonthPending = 0;
                                                    if (isset($transactions['data']) && is_array($transactions['data'])) {
                                                        $now = new DateTime();
                                                        $currentMonth = $now->format('Y-m');
                                                        $lastMonth = $now->modify('-1 month')->format('Y-m');
                                                        foreach ($transactions['data'] as $txn) {
                                                            if (isset($txn['status']) && $txn['status'] == 0) {
                                                                $pendingCount++;
                                                                if (!empty($txn['created_at'])) {
                                                                    $createdMonth = substr($txn['created_at'], 0, 7);
                                                                    if ($createdMonth === $currentMonth) $currentMonthPending++;
                                                                    if ($createdMonth === $lastMonth) $lastMonthPending++;
                                                                }
                                                            }
                                                        }
                                                        // Calculate percentage change since last month
                                                        if ($lastMonthPending > 0) {
                                                            $pendingGrowth = (($currentMonthPending - $lastMonthPending) / $lastMonthPending) * 100;
                                                        } else {
                                                            $pendingGrowth = $currentMonthPending > 0 ? 100 : 0;
                                                        }
                                                        $pendingGrowthClass = $pendingGrowth >= 0 ? 'text-success' : 'text-danger';
                                                        $pendingGrowthIcon = $pendingGrowth >= 0 ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold';
                                                        $pendingGrowthText = sprintf('%+.2f%%', $pendingGrowth);
                                                    } else {
                                                        $pendingGrowthClass = 'text-muted';
                                                        $pendingGrowthIcon = '';
                                                        $pendingGrowthText = '0.00%';
                                                    }
                                                ?>
                                                <h3 class="mt-3 mb-3"><?php echo $pendingCount; ?></h3>
                                                <p class="mb-0 text-muted">
                                                    <span class="<?php echo $pendingGrowthClass; ?> me-2">
                                                        <i class="mdi <?php echo $pendingGrowthIcon; ?>"></i> <?php echo $pendingGrowthText; ?>
                                                    </span>
                                                    <span class="text-nowrap">Since last month</span>
                                                </p>
                                            </div> <!-- end card-body-->
                                        </div> <!-- end card-->
                                    </div> <!-- end col-->
                                </div> <!-- end row -->

                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="card widget-flat">
                                            <div class="card-body">
                                                <div class="float-end">
                                                    <i class="mdi mdi-currency-usd widget-icon"></i>
                                                </div>
                                                <?php
                                                // Calculate total guests and percentage change since last month
                                                $totalGuests = 0;
                                                $lastMonthGuests = 0;
                                                $currentMonthGuests = 0;

                                                if (isset($transactions['data']) && is_array($transactions['data'])) {
                                                    $now = new DateTime();
                                                    $currentMonth = $now->format('Y-m');
                                                    $lastMonth = (new DateTime())->modify('-1 month')->format('Y-m');
                                                    foreach ($transactions['data'] as $txn) {
                                                        // Parse guest categories from payment_info string
                                                        if (!empty($txn['payment_info'])) {
                                                            $categories = ['Female', 'Male', 'PWD', 'Pregnant', 'Children', 'Senior'];
                                                            $txnGuests = 0;
                                                            foreach ($categories as $cat) {
                                                                if (preg_match('/' . $cat . ':(\d+)/', $txn['payment_info'], $matches)) {
                                                                    $txnGuests += (int)$matches[1];
                                                                }
                                                            }
                                                            $totalGuests += $txnGuests;
                                                            if (!empty($txn['created_at'])) {
                                                                $createdMonth = substr($txn['created_at'], 0, 7);
                                                                if ($createdMonth === $currentMonth) $currentMonthGuests += $txnGuests;
                                                                if ($createdMonth === $lastMonth) $lastMonthGuests += $txnGuests;
                                                            }
                                                        }
                                                    }
                                                    // Calculate percentage change
                                                    if ($lastMonthGuests > 0) {
                                                        $guestGrowth = (($currentMonthGuests - $lastMonthGuests) / $lastMonthGuests) * 100;
                                                    } else {
                                                        $guestGrowth = $currentMonthGuests > 0 ? 100 : 0;
                                                    }
                                                    $guestGrowthClass = $guestGrowth >= 0 ? 'text-success' : 'text-danger';
                                                    $guestGrowthIcon = $guestGrowth >= 0 ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold';
                                                    $guestGrowthText = sprintf('%+.2f%%', $guestGrowth);
                                                } else {
                                                    $guestGrowthClass = 'text-muted';
                                                    $guestGrowthIcon = '';
                                                    $guestGrowthText = '0.00%';
                                                }
                                                ?>
                                                <h5 class="text-muted fw-normal mt-0" title="Total Guests">Guests</h5>
                                                <h3 class="mt-3 mb-3"><?php echo $totalGuests; ?></h3>
                                                <p class="mb-0 text-muted">
                                                    <span class="<?php echo $guestGrowthClass; ?> me-2">
                                                        <i class="mdi <?php echo $guestGrowthIcon; ?>"></i> <?php echo $guestGrowthText; ?>
                                                    </span>
                                                    <span class="text-nowrap">Since last month</span>
                                                </p>
                                            </div> <!-- end card-body-->
                                        </div> <!-- end card-->
                                    </div> <!-- end col-->

                                    <div class="col-sm-6">
                                        <div class="card widget-flat">
                                            <div class="card-body">
                                                <div class="float-end">
                                                    <i class="mdi mdi-pulse widget-icon"></i>
                                                </div>
                                                <h5 class="text-muted fw-normal mt-0" title="Number of Approved Transactions">Approved</h5>
                                                <?php
                                                    // Count approved transactions (status = 1)
                                                    $approvedCount = 0;
                                                    $lastMonthApproved = 0;
                                                    $currentMonthApproved = 0;
                                                    if (isset($transactions['data']) && is_array($transactions['data'])) {
                                                        $now = new DateTime();
                                                        $currentMonth = $now->format('Y-m');
                                                        $lastMonth = $now->modify('-1 month')->format('Y-m');
                                                        foreach ($transactions['data'] as $txn) {
                                                            if (isset($txn['status']) && $txn['status'] == 1) {
                                                                $approvedCount++;
                                                                if (!empty($txn['created_at'])) {
                                                                    $createdMonth = substr($txn['created_at'], 0, 7);
                                                                    if ($createdMonth === $currentMonth) $currentMonthApproved++;
                                                                    if ($createdMonth === $lastMonth) $lastMonthApproved++;
                                                                }
                                                            }
                                                        }
                                                        // Calculate percentage change since last month
                                                        if ($lastMonthApproved > 0) {
                                                            $approvedGrowth = (($currentMonthApproved - $lastMonthApproved) / $lastMonthApproved) * 100;
                                                        } else {
                                                            $approvedGrowth = $currentMonthApproved > 0 ? 100 : 0;
                                                        }
                                                        $approvedGrowthClass = $approvedGrowth >= 0 ? 'text-success' : 'text-danger';
                                                        $approvedGrowthIcon = $approvedGrowth >= 0 ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold';
                                                        $approvedGrowthText = sprintf('%+.2f%%', $approvedGrowth);
                                                    } else {
                                                        $approvedGrowthClass = 'text-muted';
                                                        $approvedGrowthIcon = '';
                                                        $approvedGrowthText = '0.00%';
                                                    }
                                                ?>
                                                <h3 class="mt-3 mb-3"><?php echo $approvedCount; ?></h3>
                                                <p class="mb-0 text-muted">
                                                    <span class="<?php echo $approvedGrowthClass; ?> me-2">
                                                        <i class="mdi <?php echo $approvedGrowthIcon; ?>"></i> <?php echo $approvedGrowthText; ?>
                                                    </span>
                                                    <span class="text-nowrap">Since last month</span>
                                                </p>
                                            </div> <!-- end card-body-->
                                        </div> <!-- end card-->
                                    </div> <!-- end col-->
                                </div> <!-- end row -->

                            </div> <!-- end col -->

                            <div class="col-xl-7 col-lg-6">
                                <div class="card card-h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="header-title">Transactions Guests</h4>
                                        </div>

                                        <div dir="ltr">
                                            <div id="high-performing-product" class="apex-charts" data-colors="#30382f,#e3eaef"></div>
                                        </div>
                               
                                        <?php
                                        echo '<script>';
                                        echo 'var transactionsData = ' . json_encode($transactions['data'] ?? []) . ';';
                                        echo '</script>';
                                        ?>
                                       
                                    </div> <!-- end card-body-->
                                </div> <!-- end card-->

                            </div> <!-- end col -->
                        </div>
                        <!-- end row -->

                

                    </div>
                    <!-- container -->

                </div>
                <!-- content -->



            </div>

            <!-- ============================================================== -->
            <!-- End Page content -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

        <!-- Right Sidebar -->
        <div class="end-bar">

            <div class="rightbar-title">
                <a href="javascript:void(0);" class="end-bar-toggle float-end">
                    <i class="dripicons-cross noti-icon"></i>
                </a>
                <h5 class="m-0">Settings</h5>
            </div>

            <div class="rightbar-content h-100" data-simplebar>

                <div class="p-3">
                    <div class="alert alert-warning" role="alert">
                        <strong>Customize </strong> the overall color scheme, sidebar menu, etc.
                    </div>

                    <!-- Settings -->
                    <h5 class="mt-3">Color Scheme</h5>
                    <hr class="mt-1" />

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="color-scheme-mode" value="light" id="light-mode-check" checked>
                        <label class="form-check-label" for="light-mode-check">Light Mode</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="color-scheme-mode" value="dark" id="dark-mode-check">
                        <label class="form-check-label" for="dark-mode-check">Dark Mode</label>
                    </div>
       

                    <!-- Width -->
                    <h5 class="mt-4">Width</h5>
                    <hr class="mt-1" />
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="width" value="fluid" id="fluid-check" checked>
                        <label class="form-check-label" for="fluid-check">Fluid</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="width" value="boxed" id="boxed-check">
                        <label class="form-check-label" for="boxed-check">Boxed</label>
                    </div>
        

                    <!-- Left Sidebar-->
                    <h5 class="mt-4">Left Sidebar</h5>
                    <hr class="mt-1" />
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="theme" value="default" id="default-check">
                        <label class="form-check-label" for="default-check">Default</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="theme" value="light" id="light-check" checked>
                        <label class="form-check-label" for="light-check">Light</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="theme" value="dark" id="dark-check">
                        <label class="form-check-label" for="dark-check">Dark</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="fixed" id="fixed-check" checked>
                        <label class="form-check-label" for="fixed-check">Fixed</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="condensed" id="condensed-check">
                        <label class="form-check-label" for="condensed-check">Condensed</label>
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="compact" value="scrollable" id="scrollable-check">
                        <label class="form-check-label" for="scrollable-check">Scrollable</label>
                    </div>

                    <div class="d-grid mt-4">
                        <button class="btn btn-primary" id="resetBtn">Reset to Default</button>
            
                        
                    </div>
                </div> <!-- end padding-->

            </div>
        </div>

        <div class="rightbar-overlay"></div>
        <!-- /End-bar -->

        <!-- bundle -->
        <script src="assets/js/vendor.min.js"></script>
        <script src="assets/js/app.min.js"></script>

        <!-- third party js -->
        <script src="assets/js/vendor/apexcharts.min.js"></script>
        <script src="assets/js/vendor/jquery-jvectormap-1.2.2.min.js"></script>
        <script src="assets/js/vendor/jquery-jvectormap-world-mill-en.js"></script>
        <!-- third party js ends -->

        <!-- demo app -->
        <script src="assets/js/pages/demo.dashboard.js"></script>
        <!-- end demo js-->
    </body>

<!-- /hyper/saas/index.html [XR&CO'2014], Fri, 29 Jul 2022 10:20:07 GMT -->
</html>