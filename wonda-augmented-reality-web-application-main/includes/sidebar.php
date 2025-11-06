<?php
$user = $_SESSION['user'];
$profileImg = !empty($user['profile']) ? 'assets/images/users/' . htmlspecialchars($user['profile']) : 'assets/images/default-profile.png';
$displayName = strtoupper(substr($user['name'], 0, 1)) . substr($user['name'], 1);
$role = ($user['type'] == 1) ? 'Admin' : 'User';
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
?>

          <!-- ========== Left Sidebar Start ========== -->
            <?php
            $userType = isset($_SESSION['user']['type']) ? $_SESSION['user']['type'] : null;
            ?>

            <div class="leftside-menu">

                <!-- LOGO -->
                <a href="index.php" class="logo text-center logo-light">
                    <span class="logo-lg">
                        <img src="assets/images/logo.png" alt="" height="50">
                    </span>
                    <span class="logo-sm">
                        <img src="assets/images/logo_sm.png" alt="" height="50">
                    </span>
                </a>

                <!-- LOGO -->
                <a href="index.php" class="logo text-center logo-dark">
                    <span class="logo-lg">
                        <img src="assets/images/logo-dark.png" alt="" height="50">
                    </span>
                    <span class="logo-sm">
                        <img src="assets/images/logo_sm_dark.png" alt="" height="50">
                    </span>
                </a>

                <div class="h-100" id="leftside-menu-container" data-simplebar>
                    
                    <!-- User Profile Section -->
                    <div class="user-profile-sidebar">
                        <img src="<?php echo $profileImg; ?>" alt="Profile" class="profile-img">
                        <div class="profile-name"><?php echo htmlspecialchars($displayName); ?></div>
                        <div class="profile-role"><?php echo $role; ?></div>
                    </div>

                    <ul class="side-nav">
                        <?php if ($userType === 0): ?>
                            <li class="side-nav-title side-nav-item">Main</li>
                    
                            <li class="side-nav-item">
                                <a href="calendar.php" class="side-nav-link">
                                    <i class="uil-calendar-alt"></i>
                                    <span> Calendar </span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a href="map.php" class="side-nav-link">
                                    <i class="uil-map"></i>
                                    <span> Map </span>
                                </a>
                            </li>
                            <li class="side-nav-title side-nav-item">Reservation</li>
                            <li class="side-nav-item">
                                <a href="subscribe.php" class="side-nav-link">
                                    <i class="uil-book-open"></i>
                                    <span> Book / Subscribe </span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a href="table.php?table=transactions" class="side-nav-link">
                                    <i class="uil-exchange"></i>
                                    <span> Transactions </span>
                                </a>
                            </li>

                            <!-- Ratings for regular users -->
                            <li class="side-nav-item">
                                <a href="ratings.php" class="side-nav-link">
                                    <i class="uil-star"></i>
                                    <span> Ratings </span>
                                </a>
                            </li>
                            
                            <li class="side-nav-title side-nav-item">Mobile App</li>
                            <li class="side-nav-item">
                                <a href="download.php" class="side-nav-link">
                                    <i class="uil-cloud-download"></i>
                                    <span> Download App </span>
                                </a>
                            </li>
                        <?php elseif ($userType === 1): ?>
                            <li class="side-nav-title side-nav-item">Home</li>
                            <li class="side-nav-item">
                                <a href="dashboard.php" class="side-nav-link">
                                    <i class="uil-dashboard"></i>
                                    <span> Dashboard </span>
                                </a>
                            </li>
                                  <li class="side-nav-item">
                                <a href="reports.php" class="side-nav-link">
                                    <i class="uil-chart-line"></i>
                                    <span> Report & Analytics </span>
                                </a>
                            </li>
                            <li class="side-nav-title side-nav-item">Reservation</li>
                            <li class="side-nav-item">
                                <a href="calendar.php" class="side-nav-link">
                                    <i class="uil-calendar-alt"></i>
                                    <span> Calendar </span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a href="table.php?table=transactions" class="side-nav-link">
                                    <i class="uil-exchange"></i>
                                    <span> Transactions </span>
                                </a>
                            </li>
                          
                            <li class="side-nav-title side-nav-item">News & Feedback</li>
                            <li class="side-nav-item">
                                <a href="table.php?table=news" class="side-nav-link">
                                    <i class="uil-newspaper"></i>
                                    <span> News & Promos </span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a href="table.php?table=feedback" class="side-nav-link">
                                    <i class="uil-star"></i>
                                    <span> Feedbacks & Ratings </span>
                                </a>
                            </li>
                  
                      
                            <li class="side-nav-title side-nav-item">Management</li>
                            <li class="side-nav-item">
                                <a href="table.php?table=subscriptions" class="side-nav-link">
                                    <i class="uil-box"></i>
                                    <span> Subscriptions</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a href="https://auth-db1417.hstgr.io/index.php?db=u467106394_wondago" target="_blank" class="side-nav-link">
                                    <i class="uil-database"></i>
                                    <span> Database</span>
                                </a>
                            </li> 
                            <li class="side-nav-item">
                                <a href="payment.php" class="side-nav-link">
                                    <i class="uil-credit-card"></i>
                                    <span> Payment Methods</span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a href="table.php?table=users" class="side-nav-link">
                                    <i class="uil-users-alt"></i>
                                    <span> System Users </span>
                                </a>
                            </li>
                            <li class="side-nav-item">
                                <a href="club_500.php" class="side-nav-link">
                                    <i class="uil-envelope"></i>
                                    <span> Club 500 Requests </span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                <!-- Sidebar -left -->
            </div>
            <!-- Left Sidebar End -->
             
            <style>
            .topalert {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 350px;
                min-width: 250px;
                width: auto;
                float: right;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                pointer-events: auto;
            }

            /* User Profile Sidebar Styles */
            .user-profile-sidebar {
                text-align: center;
                margin: 20px 0;
                padding: 15px 10px;
                border-bottom: 1px solid rgba(255,255,255,0.1);
                background: rgba(255,255,255,0.05);
                border-radius: 8px;
                margin: 15px;
            }

            .profile-img {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                object-fit: cover;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                border: 3px solid rgba(255,255,255,0.2);
                transition: all 0.3s ease;
            }

            .profile-img:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 12px rgba(0,0,0,0.25);
            }

            .profile-name {
                font-weight: bold;
                font-size: 16px;
                margin-top: 8px;
                color: #fff !important;
                text-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }

            .profile-role {
                                color: #fff !important;

                font-size: 13px;
                margin-top: 2px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                opacity: 0.8;
            }

            /* Dark theme adjustments */
            [data-leftbar-theme="dark"] .user-profile-sidebar {
                background: rgba(255,255,255,0.08);
                border-bottom: 1px solid rgba(255,255,255,0.15);
            }

            [data-leftbar-theme="dark"] .profile-name {
                color: #fff;
            }

            [data-leftbar-theme="dark"] .profile-role {
                color: #aaa;
            }

            /* Light theme adjustments */
            [data-leftbar-theme="light"] .user-profile-sidebar {
                background: rgba(0,0,0,0.03);
                border-bottom: 1px solid rgba(0,0,0,0.1);
            }

            [data-leftbar-theme="light"] .profile-name {
                color: #333;
            }

            [data-leftbar-theme="light"] .profile-role {
                color: #666;
            }

            [data-leftbar-theme="light"] .profile-img {
                border: 3px solid rgba(0,0,0,0.1);
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .user-profile-sidebar {
                    margin: 10px;
                    padding: 10px;
                }
                
                .profile-img {
                    width: 50px;
                    height: 50px;
                }
                
                .profile-name {
                    font-size: 14px;
                }
                
                .profile-role {
                    font-size: 12px;
                }
            }
            </style>