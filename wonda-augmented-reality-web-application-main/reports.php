<?php require_once 'includes/config.php'; ?>  

<?php
// Get filter parameters
$timeFilter = $_GET['filter'] ?? 'all';
$monthFilter = $_GET['month'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$currentDate = date('Y-m-d H:i:s');

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $exportType = $_GET['export_type'] ?? 'users';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $exportType . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($exportType) {
        case 'users':
            // Export users data
            fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Gender', 'Birthdate', 'Age', 'Address', 'Type', 'Created At']);
            $users = $db->select('users', '*');
            if ($users['status'] === 'success') {
                foreach ($users['data'] as $user) {
                    $age = !empty($user['birthdate']) ? date_diff(date_create($user['birthdate']), date_create('today'))->y : '';
                    $type = $user['type'] == 1 ? 'Admin' : 'Regular';
                    fputcsv($output, [
                        $user['id'],
                        $user['name'],
                        $user['email'],
                        $user['number'],
                        $user['gender'],
                        $user['birthdate'],
                        $age,
                        $user['address'],
                        $type,
                        $user['created_at']
                    ]);
                }
            }
            break;
            
        case 'transactions':
            // Export transactions data
            fputcsv($output, ['ID', 'Unique ID', 'User ID', 'Type', 'Subscription', 'Reservation Date', 'End Date', 'Payment Info', 'Guest Names', 'Status', 'Created At']);
            $transactions = $db->select('transactions', '*');
            if ($transactions['status'] === 'success') {
                foreach ($transactions['data'] as $transaction) {
                    $status = $transaction['status'] == 1 ? 'Completed' : ($transaction['status'] == 0 ? 'Pending' : 'Failed');
                    fputcsv($output, [
                        $transaction['id'],
                        $transaction['unique_id'],
                        $transaction['user_id'],
                        $transaction['type'],
                        $transaction['subscription'],
                        $transaction['reservation_date'],
                        $transaction['end_date'],
                        $transaction['payment_info'],
                        $transaction['guest_names'],
                        $status,
                        $transaction['created_at']
                    ]);
                }
            }
            break;
            
        case 'subscriptions':
            // Export subscriptions data
            fputcsv($output, ['ID', 'Name', 'Description', 'Type', 'Price', 'Status', 'Created At']);
            $subscriptions = $db->select('subscriptions', '*');
            if ($subscriptions['status'] === 'success') {
                foreach ($subscriptions['data'] as $subscription) {
                    $type = $subscription['type'] == 0 ? 'Ticket' : ($subscription['type'] == 1 ? 'Event Venue' : 'Membership');
                    $status = $subscription['status'] == 1 ? 'Active' : 'Inactive';
                    fputcsv($output, [
                        $subscription['id'],
                        $subscription['name'],
                        $subscription['description'],
                        $type,
                        $subscription['price'],
                        $status,
                        $subscription['created_at']
                    ]);
                }
            }
            break;
            
        case 'feedback':
            // Export feedback data
            fputcsv($output, ['ID', 'Name', 'Email', 'Subject', 'Message', 'Stars', 'Created At']);
            $feedback = $db->select('feedback', '*');
            if ($feedback['status'] === 'success') {
                foreach ($feedback['data'] as $fb) {
                    fputcsv($output, [
                        $fb['id'],
                        $fb['name'],
                        $fb['email'],
                        $fb['subject'],
                        $fb['message'],
                        $fb['stars'],
                        $fb['created_at']
                    ]);
                }
            }
            break;
    }
    
    fclose($output);
    exit;
}

// Build time condition for filtering
$timeCondition = '';
switch($timeFilter) {
    case 'day':
        $timeCondition = "DATE(created_at) = CURDATE()";
        break;
    case 'week':
        $timeCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        if (!empty($monthFilter)) {
            $timeCondition = "DATE_FORMAT(created_at, '%Y-%m') = '$monthFilter'";
        } else {
            $timeCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        }
        break;
    case 'year':
        $timeCondition = "YEAR(created_at) = YEAR(CURDATE())";
        break;
    case 'custom':
        if (!empty($startDate) && !empty($endDate)) {
            $timeCondition = "DATE(created_at) BETWEEN '$startDate' AND '$endDate'";
        }
        break;
    default:
        $timeCondition = '';
}

// Fetch data for reports with time filtering
if ($timeCondition) {
    $feedback = $db->custom('feedback', '*', [], $timeCondition);
    $subscriptions = $db->custom('subscriptions', '*', [], $timeCondition);
    $transactions = $db->custom('transactions', '*', [], $timeCondition);
    $users = $db->custom('users', '*', [], $timeCondition);
} else {
    $feedback = $db->select('feedback', '*');
    $subscriptions = $db->select('subscriptions', '*');
    $transactions = $db->select('transactions', '*');
    $users = $db->select('users', '*');
}

// Process data for charts
$feedbackData = $feedback['status'] === 'success' ? $feedback['data'] : [];
$subscriptionsData = $subscriptions['status'] === 'success' ? $subscriptions['data'] : [];
$transactionsData = $transactions['status'] === 'success' ? $transactions['data'] : [];
$usersData = $users['status'] === 'success' ? $users['data'] : [];

// Process address data for Luzon, Visayas, Mindanao
$regionData = ['Luzon' => 0, 'Visayas' => 0, 'Mindanao' => 0];
$cityData = [];
$ageGroups = ['18-25' => 0, '26-35' => 0, '36-45' => 0, '46-55' => 0, '55+' => 0];
$genderData = ['Male' => 0, 'Female' => 0, 'Other' => 0];

// Function to extract city from address
function extractCity($address) {
    // Split by comma to get address parts
    $parts = array_map('trim', explode(',', $address));
    
    // Based on the registration form format: street, city, state, postal, country
    // The city should be the second part (index 1)
    if (count($parts) >= 2) {
        $city = trim($parts[1]);
        
        // Clean up the city name
        $city = preg_replace('/\d+/', '', $city); // Remove numbers
        $city = trim($city);
        
        // If it doesn't already end with "City", add it for consistency
        if (!empty($city) && !stripos($city, 'city')) {
            return ucwords(strtolower($city)) . ' City';
        } else {
            return ucwords(strtolower($city));
        }
    }
    
    // Fallback: if comma separation doesn't work, try to find city keywords
    $address = strtolower($address);
    
    // Common Philippine cities for fallback detection
    $philippineCities = [
        'manila', 'quezon city', 'makati', 'taguig', 'pasig', 'antipolo', 'paranaque', 
        'las pinas', 'muntinlupa', 'marikina', 'pasay', 'valenzuela', 'caloocan', 
        'malabon', 'navotas', 'san juan', 'mandaluyong', 'pateros', 'baguio',
        'cebu city', 'davao city', 'cagayan de oro', 'zamboanga', 'iloilo city',
        'bacolod', 'tacloban', 'butuan', 'dumaguete', 'general santos'
    ];
    
    foreach ($philippineCities as $city) {
        if (strpos($address, $city) !== false) {
            return ucwords($city);
        }
    }
    
    return 'Unknown City';
}

foreach ($usersData as $user) {
    // Process address/region data
    $address = strtolower($user['address'] ?? '');
    
    // Define Luzon cities/provinces
    $luzonKeywords = ['manila', 'quezon', 'makati', 'taguig', 'pasig', 'antipolo', 'paranaque', 'las pinas', 'muntinlupa', 'marikina', 'pasay', 'valenzuela', 'caloocan', 'malabon', 'navotas', 'san juan', 'mandaluyong', 'pateros', 'bulacan', 'cavite', 'laguna', 'rizal', 'batangas', 'pampanga', 'nueva ecija', 'tarlac', 'zambales', 'bataan', 'aurora', 'benguet', 'ifugao', 'kalinga', 'mountain province', 'apayao', 'abra', 'ilocos norte', 'ilocos sur', 'la union', 'pangasinan', 'nueva vizcaya', 'quirino', 'isabela', 'cagayan', 'baguio'];
    
    // Define Visayas cities/provinces
    $visayasKeywords = ['cebu', 'bohol', 'siquijor', 'negros oriental', 'negros occidental', 'leyte', 'samar', 'biliran', 'eastern samar', 'northern samar', 'southern leyte', 'capiz', 'aklan', 'antique', 'iloilo', 'guimaras'];
    
    // Define Mindanao cities/provinces
    $mindanaoKeywords = ['davao', 'cagayan de oro', 'general santos', 'zamboanga', 'iloilo', 'butuan', 'surigao', 'agusan', 'bukidnon', 'lanao del norte', 'lanao del sur', 'north cotabato', 'south cotabato', 'sultan kudarat', 'maguidanao', 'tawi-tawi', 'sulu', 'basilan'];
    
    $isLuzon = false;
    $isVisayas = false;
    $isMindanao = false;
    
    foreach ($luzonKeywords as $keyword) {
        if (strpos($address, $keyword) !== false) {
            $regionData['Luzon']++;
            $isLuzon = true;
            break;
        }
    }
    
    if (!$isLuzon) {
        foreach ($visayasKeywords as $keyword) {
            if (strpos($address, $keyword) !== false) {
                $regionData['Visayas']++;
                $isVisayas = true;
                break;
            }
        }
    }
    
    if (!$isLuzon && !$isVisayas) {
        foreach ($mindanaoKeywords as $keyword) {
            if (strpos($address, $keyword) !== false) {
                $regionData['Mindanao']++;
                $isMindanao = true;
                break;
            }
        }
    }
    
    // Count cities with improved extraction
    if (!empty($user['address'])) {
        $city = extractCity($user['address']);
        $cityData[$city] = ($cityData[$city] ?? 0) + 1;
    }
    
    // Process age groups
    if (!empty($user['birthdate'])) {
        $age = date_diff(date_create($user['birthdate']), date_create('today'))->y;
        if ($age >= 18 && $age <= 25) $ageGroups['18-25']++;
        elseif ($age >= 26 && $age <= 35) $ageGroups['26-35']++;
        elseif ($age >= 36 && $age <= 45) $ageGroups['36-45']++;
        elseif ($age >= 46 && $age <= 55) $ageGroups['46-55']++;
        elseif ($age > 55) $ageGroups['55+']++;
    }
    
    // Process gender data
    $gender = ucfirst(strtolower($user['gender'] ?? 'Other'));
    if (in_array($gender, ['Male', 'Female'])) {
        $genderData[$gender]++;
    } else {
        $genderData['Other']++;
    }
}

// Sort cities by count and get top 10
arsort($cityData);
$topCities = array_slice($cityData, 0, 10, true);

// Visitor type data (based on user type)
$visitorTypes = ['Regular Users' => 0, 'Admin Users' => 0];
foreach ($usersData as $user) {
    if ($user['type'] == 1) {
        $visitorTypes['Admin Users']++;
    } else {
        $visitorTypes['Regular Users']++;
    }
}

// Monthly registration data for line chart
$monthlyData = [];
foreach ($usersData as $user) {
    $month = date('Y-m', strtotime($user['created_at']));
    $monthlyData[$month] = ($monthlyData[$month] ?? 0) + 1;
}
ksort($monthlyData);

// Subscription status data
$subscriptionStatus = ['Active' => 0, 'Inactive' => 0, 'Pending' => 0];
foreach ($subscriptionsData as $subscription) {
    switch ($subscription['status']) {
        case 1:
            $subscriptionStatus['Active']++;
            break;
        case 0:
            $subscriptionStatus['Inactive']++;
            break;
        default:
            $subscriptionStatus['Pending']++;
            break;
    }
}

// Transaction status data
$transactionStatus = ['Completed' => 0, 'Pending' => 0, 'Failed' => 0];
foreach ($transactionsData as $transaction) {
    switch ($transaction['status']) {
        case 1:
            $transactionStatus['Completed']++;
            break;
        case 0:
            $transactionStatus['Pending']++;
            break;
        default:
            $transactionStatus['Failed']++;
            break;
    }
}

$feedbackRatings = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
foreach ($feedbackData as $feedback) {
$rating = $feedback['stars'] ?? 0;
    // Convert to integer if it's a string
    $rating = (int)$rating;
    
    if ($rating >= 1 && $rating <= 5) {
        $feedbackRatings[(string)$rating]++;
    }
}
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
                                        <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                                        <li class="breadcrumb-item active">Reports</li>
                                    </ol>
                                </div>
                                <h4 class="page-title">Analytics Reports</h4>
                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <!-- Filter Controls -->
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <select class="form-control" id="timeFilter">
                                <option value="all" <?php echo $timeFilter == 'all' ? 'selected' : ''; ?>>All Time</option>
                                <option value="year" <?php echo $timeFilter == 'year' ? 'selected' : ''; ?>>This Year</option>
                                <option value="month" <?php echo $timeFilter == 'month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="week" <?php echo $timeFilter == 'week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="day" <?php echo $timeFilter == 'day' ? 'selected' : ''; ?>>Today</option>
                                <option value="custom" <?php echo $timeFilter == 'custom' ? 'selected' : ''; ?>>Custom Date Range</option>
                            </select>
                        </div>
                        <div class="col-md-2" id="monthFilterDiv" style="display: <?php echo $timeFilter == 'month' ? 'block' : 'none'; ?>;">
                            <input type="month" class="form-control" id="monthFilter" value="<?php echo $monthFilter; ?>">
                        </div>
                        <div class="col-md-4" id="dateRangeDiv" style="display: <?php echo $timeFilter == 'custom' ? 'flex' : 'none'; ?>;">
                            <input type="date" class="form-control me-2" id="startDate" value="<?php echo $startDate; ?>" placeholder="Start Date">
                            <input type="date" class="form-control" id="endDate" value="<?php echo $endDate; ?>" placeholder="End Date">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary" onclick="applyFilters()">Apply Filter</button>
                        </div>
                        <div class="col-md-4">
                            <div class="btn-group">
                                <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                    Export CSV
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="exportCSV('users')">Users Data</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportCSV('transactions')">Transactions Data</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportCSV('subscriptions')">Subscriptions Data</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportCSV('feedback')">Feedback Data</a></li>
                                </ul>
                            </div>
                            <button class="btn btn-secondary" onclick="exportReports()">Export PDF</button>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Total Users</h4>
                                    <h2 class="text-primary"><?php echo count($usersData); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Total Subscriptions</h4>
                                    <h2 class="text-success"><?php echo count($subscriptionsData); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Total Transactions</h4>
                                    <h2 class="text-warning"><?php echo count($transactionsData); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Total Feedback</h4>
                                    <h2 class="text-info"><?php echo count($feedbackData); ?></h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 1 -->
                    <div class="row">
                        <!-- Regional Distribution -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Regional Distribution</h4>
                                    <div id="regionChart"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Top Cities -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Top Cities</h4>
                                    <div id="cityChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 2 -->
                    <div class="row">
                        <!-- Age Groups -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Age Groups</h4>
                                    <div id="ageChart"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Gender Distribution -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Gender Distribution</h4>
                                    <div id="genderChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 3 -->
                    <div class="row">
                        <!-- Visitor Types -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Visitor Types</h4>
                                    <div id="visitorChart"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Monthly Registration Trend -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Monthly Registration Trend</h4>
                                    <div id="monthlyChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 4 -->
                    <div class="row">
                        <!-- Subscription Status -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Subscription Status</h4>
                                    <div id="subscriptionChart"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transaction Status -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Transaction Status</h4>
                                    <div id="transactionChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row 5 -->
                    <div class="row">
                        <!-- Feedback Ratings -->
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">Feedback Ratings Distribution</h4>
                                    <div id="feedbackChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- container -->
            </div>
            <!-- content -->
        </div>

        <?php require 'includes/right-sidebar.php'; ?>  

        <!-- ============================================================== -->
        <!-- End Page content -->
        <!-- ============================================================== -->

    </div>
    <!-- END wrapper -->

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

    <script>
        // Prevent demo.dashboard.js from running
        window.Dashboard = { init: function() {} };
        
        // Chart data from PHP
        const regionData = <?php echo json_encode($regionData); ?>;
        const cityData = <?php echo json_encode($topCities); ?>;
        const ageData = <?php echo json_encode($ageGroups); ?>;
        const genderData = <?php echo json_encode($genderData); ?>;
        const visitorData = <?php echo json_encode($visitorTypes); ?>;
        const monthlyData = <?php echo json_encode($monthlyData); ?>;
        const subscriptionData = <?php echo json_encode($subscriptionStatus); ?>;
        const transactionData = <?php echo json_encode($transactionStatus); ?>;
        const feedbackData = <?php echo json_encode($feedbackRatings); ?>;

        // Wait for DOM to load
        document.addEventListener('DOMContentLoaded', function() {
            // Regional Distribution Chart
            if (document.querySelector("#regionChart")) {
                const regionOptions = {
                    series: Object.values(regionData),
                    chart: {
                        type: 'pie',
                        height: 350
                    },
                    labels: Object.keys(regionData),
                    colors: ['#FF6B6B', '#4ECDC4', '#45B7D1'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " users"
                            }
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                width: 200
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                };
                new ApexCharts(document.querySelector("#regionChart"), regionOptions).render();
            }

            // Top Cities Chart
            if (document.querySelector("#cityChart")) {
                const cityOptions = {
                    series: [{
                        name: 'Users',
                        data: Object.values(cityData)
                    }],
                    chart: {
                        type: 'bar',
                        height: 350
                    },
                    xaxis: {
                        categories: Object.keys(cityData)
                    },
                    colors: ['#96CEB4'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " users"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#cityChart"), cityOptions).render();
            }

            // Age Groups Chart
            if (document.querySelector("#ageChart")) {
                const ageOptions = {
                    series: Object.values(ageData),
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    labels: Object.keys(ageData),
                    colors: ['#FF9999', '#66B2FF', '#99FF99', '#FFCC99', '#FF99CC'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " users"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#ageChart"), ageOptions).render();
            }

            // Gender Chart
            if (document.querySelector("#genderChart")) {
                const genderOptions = {
                    series: Object.values(genderData),
                    chart: {
                        type: 'pie',
                        height: 350
                    },
                    labels: Object.keys(genderData),
                    colors: ['#4A90E2', '#F5A623', '#7ED321'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " users"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#genderChart"), genderOptions).render();
            }

            // Visitor Types Chart
            if (document.querySelector("#visitorChart")) {
                const visitorOptions = {
                    series: Object.values(visitorData),
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    labels: Object.keys(visitorData),
                    colors: ['#50E3C2', '#B8E986'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " users"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#visitorChart"), visitorOptions).render();
            }

            // Monthly Trend Chart
            if (document.querySelector("#monthlyChart")) {
                const monthlyOptions = {
                    series: [{
                        name: 'Registrations',
                        data: Object.values(monthlyData)
                    }],
                    chart: {
                        type: 'line',
                        height: 350
                    },
                    xaxis: {
                        categories: Object.keys(monthlyData)
                    },
                    stroke: {
                        curve: 'smooth'
                    },
                    colors: ['#9013FE'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " registrations"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#monthlyChart"), monthlyOptions).render();
            }

            // Subscription Status Chart
            if (document.querySelector("#subscriptionChart")) {
                const subscriptionOptions = {
                    series: Object.values(subscriptionData),
                    chart: {
                        type: 'pie',
                        height: 350
                    },
                    labels: Object.keys(subscriptionData),
                    colors: ['#28a745', '#dc3545', '#ffc107'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " subscriptions"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#subscriptionChart"), subscriptionOptions).render();
            }

            // Transaction Status Chart
            if (document.querySelector("#transactionChart")) {
                const transactionOptions = {
                    series: Object.values(transactionData),
                    chart: {
                        type: 'donut',
                        height: 350
                    },
                    labels: Object.keys(transactionData),
                    colors: ['#17a2b8', '#ffc107', '#dc3545'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " transactions"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#transactionChart"), transactionOptions).render();
            }

            // Feedback Ratings Chart
            if (document.querySelector("#feedbackChart")) {
                const feedbackOptions = {
                    series: [{
                        name: 'Count',
                        data: Object.values(feedbackData)
                    }],
                    chart: {
                        type: 'bar',
                        height: 350
                    },
                    xaxis: {
                        categories: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars']
                    },
                    colors: ['#FF6B6B'],
                    tooltip: {
                        y: {
                            formatter: function (val) {
                                return val + " feedback"
                            }
                        }
                    }
                };
                new ApexCharts(document.querySelector("#feedbackChart"), feedbackOptions).render();
            }
        });

        // Filter functions
        function applyFilters() {
            const timeFilter = document.getElementById('timeFilter').value;
            const monthFilter = document.getElementById('monthFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            let url = 'reports.php?filter=' + timeFilter;
            if (timeFilter === 'month' && monthFilter) {
                url += '&month=' + monthFilter;
            } else if (timeFilter === 'custom' && startDate && endDate) {
                url += '&start_date=' + startDate + '&end_date=' + endDate;
            }
            window.location.href = url;
        }

        // Show/hide month and date range filters based on time filter selection
        document.getElementById('timeFilter').addEventListener('change', function() {
            const monthDiv = document.getElementById('monthFilterDiv');
            const dateRangeDiv = document.getElementById('dateRangeDiv');
            if (this.value === 'month') {
                monthDiv.style.display = 'block';
                dateRangeDiv.style.display = 'none';
            } else if (this.value === 'custom') {
                monthDiv.style.display = 'none';
                dateRangeDiv.style.display = 'flex';
            } else {
                monthDiv.style.display = 'none';
                dateRangeDiv.style.display = 'none';
            }
        });

        function exportCSV(type) {
            const timeFilter = document.getElementById('timeFilter').value;
            const monthFilter = document.getElementById('monthFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            let url = 'reports.php?export=csv&export_type=' + type + '&filter=' + timeFilter;
            if (timeFilter === 'month' && monthFilter) {
                url += '&month=' + monthFilter;
            } else if (timeFilter === 'custom' && startDate && endDate) {
                url += '&start_date=' + startDate + '&end_date=' + endDate;
            }
            window.location.href = url;
        }

        function exportReports() {
            window.print();
        }
    </script>

</body>

</html>