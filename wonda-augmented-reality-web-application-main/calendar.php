<?php
require_once 'includes/config.php'; ?>  

<?php
if ($_SESSION['user']['type'] == 0) {
    $transactions = $db->select('transactions', '*', ['user_id' => $_SESSION['user']['id']]);
} else {
    $transactions = $db->select('transactions');
}
$subscriptions = $db->select('subscriptions' );
$users = $db->select('users');
?>
<!DOCTYPE html>
<html lang="en">

<?php require_once 'includes/head.php'; ?>  
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<style>
@media print {
    @page {
        size: A3 landscape;
        margin: 20mm;
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
 
                                <h4 class="page-title">Calendar</h4>
    <button hidden class="btn btn-info mb-3" onclick="launchQRScanner()">Track Transaction </button>

    <!-- QR Scanner Modal -->
<div class="modal fade" id="qrScannerModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Scan or Upload QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="qr-reader" style="width:100%;"></div>
        <div id="qr-result" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>


                            </div>
                        </div>
                    </div>
                    <!-- end page title -->

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <div class="mt-4 mt-lg-0">
                                                <div id="calendar"></div>
                                            </div>
                                        </div> <!-- end col -->
                                    </div> <!-- end row -->
                                </div> <!-- end card body-->
                            </div> <!-- end card -->
                        </div>
                        <!-- end col-12 -->
                    </div> <!-- end row -->
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

    <!-- bundle -->
    <script src="assets/js/vendor.min.js"></script>
    <script src="assets/js/app.min.js"></script>
    <!-- third party js -->
    <script src="assets/js/vendor/fullcalendar.min.js"></script>
    <!-- third party js ends -->
    <!-- demo app -->
    <script src="assets/js/pages/demo.calendar.js"></script>
    <!-- end demo js-->

</body>
</html>

<script>
    const transactions = <?php echo json_encode($transactions['data'] ?? []); ?>;
    const users = <?php echo json_encode($users['data'] ?? []); ?>;
    const subscriptions = <?php echo json_encode($subscriptions['data'] ?? []); ?>;

    const typeColorMap = {
        0: 'bg-primary',
        1: 'bg-success',
        2: 'bg-warning'
    };

    function getUser(userId) {
        return users.find(u => u.id == userId) || {};
    }

    function getSubscription(subId) {
        return subscriptions.find(s => s.id == subId) || {};
    }

    function parsePaymentInfo(info) {
        const obj = {};
        if (!info) return obj;
        info.split(',').forEach(pair => {
            let [key, val] = pair.split(':');
            if (key && val !== undefined) obj[key.trim()] = val.trim();
        });
        return obj;
    }

    function getUserProfileImage(user) {
        return user.profile ? 'assets/images/users/' + user.profile : 'assets/images/user.png';
    }

    const reservationEvents = transactions.map(trx => {
        const sub = getSubscription(trx.subscription);
        const user = getUser(trx.user_id);
        const colorClass = typeColorMap[trx.type] || 'bg-primary';
        return {
            id: trx.id,
            title: sub.name || 'Reservation',
            start: trx.reservation_date,
            end: trx.end_date,
            allDay: true,
            extendedProps: {
                transaction: trx,
                subscription: sub,
                user: user,
            },
            className: colorClass
        };
    });

    // Add recurring background events to mark Monday (1) - Wednesday (3) as Closed
    const closedDays = [1, 2, 3]; // Monday=1, Tuesday=2, Wednesday=3 (FullCalendar weekday numbers)
    const closedEvents = closedDays.map(d => ({
        id: `closed-${d}`,
        title: 'Closed',
        // daysOfWeek makes this a recurring event on the given weekday
        daysOfWeek: [d],
        // display as background so it marks the calendar background
        display: 'background',
        // color for the background mark (use any hex/bootstrap class you prefer)
        color: '#f8d7da',
        // optional: set a text color for legibility when shown in other views
        textColor: '#a94442'
    }));

    // Use both reservation events and closed background events in the calendar
    const allInitialEvents = reservationEvents.concat(closedEvents);

    function showReservationModal(event) {
        const { transaction, user, subscription } = event.extendedProps;
        const payment = parsePaymentInfo(transaction.payment_info);

        const statusText = transaction.status == 1 ? "Approved" : "Pending";
        const subTypes = ["Ticket", "Event Venue", "Membership"];
        const subTypeLabel = subTypes[subscription.type] || "Unknown";

        let paymentProofImg = payment.Proof
            ? `<img src="assets/images/payments/${payment.Proof}" class="img-fluid rounded mb-2" style="max-width:150px;">`
            : '';

        const userProfileImg = `<img src="${getUserProfileImage(user)}" class="img-thumbnail mb-2" style="max-width:100px;">`;

        let paymentTableRows = '';
        for (const [key, val] of Object.entries(payment)) {
            if (key !== 'Proof') {
                paymentTableRows += `<tr><th>${key}</th><td>${val}</td></tr>`;
            }
        }

        const qrCanvasId = 'qrCanvas_' + transaction.id;
        const uniqueId = transaction.unique_id || `T-${transaction.id}`;

        const html = `
        <div class="modal fade" id="reservation-info-modal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content" id="printable-receipt">
                    <div class="modal-header py-3 px-4 border-bottom-0">
                        <h5 class="modal-title">Reservation Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body px-4 pb-4 pt-0">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                ${userProfileImg}
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr><th>Name</th><td>${user.name || ''}</td></tr>
                                        <tr><th>Email</th><td>${user.email || ''}</td></tr>
                                        <tr><th>Number</th><td>${user.number || ''}</td></tr>
                                        <tr><th>Gender</th><td>${user.gender || ''}</td></tr>
                                        <tr><th>Age</th><td>${user.birthdate ? Math.floor((new Date() - new Date(user.birthdate)) / (365.25 * 24 * 60 * 60 * 1000)) : ''}</td></tr>
                                        <tr><th>Birthdate</th><td>${user.birthdate || ''}</td></tr>
                                        <tr><th>Address</th><td>${user.address || ''}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-8">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Subscription</dt>
                                    <dd class="col-sm-8">${subscription.name || ''} <small class="text-muted">(${subTypeLabel})</small></dd>
                                    <dt class="col-sm-4">Reservation Date</dt>
                                    <dd class="col-sm-8">${transaction.reservation_date}</dd>
                                    <dt class="col-sm-4">End Date</dt>
                                    <dd class="col-sm-8">${transaction.end_date}</dd>
                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">${statusText}</dd>
                                </dl>
                                <hr>
                                <h6>Payment Information</h6>
                                <table class="table table-sm">
                                    <tbody>${paymentTableRows}</tbody>
                                </table>
                                <div class="row mt-3 align-items-center">
                                    <div class="col-auto">
                                        ${paymentProofImg}
                                    </div>
                                    <div class="col">
                                        <canvas id="${qrCanvasId}" style="width:150px; height:150px;"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer px-4 pt-2">
                        <button class="btn btn-primary" onclick="printReceipt()">Print Receipt</button>
                    </div>
                </div>
            </div>
        </div>`;

        $('#reservation-info-modal').remove();
        $('body').append(html);

        const modal = new bootstrap.Modal(document.getElementById('reservation-info-modal'));
        modal.show();

        QRCode.toCanvas(
            document.getElementById(qrCanvasId),
            uniqueId,
            { width: 300, height: 300 },
            function (error) {
                if (error) console.error(error);
            }
        );
    }

  function printReceipt() {
    const receiptElement = document.getElementById('printable-receipt');

    // Clone the receipt content for printing
    const printContents = receiptElement.cloneNode(true);

    // Replace any canvas elements with static images
    const canvases = receiptElement.querySelectorAll('canvas');
    const cloneCanvases = printContents.querySelectorAll('canvas');

    canvases.forEach((originalCanvas, index) => {
        const dataURL = originalCanvas.toDataURL();
        const img = document.createElement('img');
        img.src = dataURL;
        img.style.width = originalCanvas.style.width || '150px';
        img.style.height = originalCanvas.style.height || '150px';
        cloneCanvases[index].replaceWith(img);
    });

    // Create print window
    const win = window.open('', '', 'height=900,width=800');
    win.document.write('<html><head><title>Reservation Receipt</title>');
    win.document.write('<link rel="stylesheet" href="assets/css/app.min.css">');
    win.document.write('<style>body{padding:20px; font-family:sans-serif;}</style>');
    win.document.write('</head><body>');
    win.document.write(printContents.innerHTML);
    win.document.write('</body></html>');
    win.document.close();
    win.focus();

    // Wait for QR image to load before printing
    win.onload = () => {
        setTimeout(() => {
            win.print();
            win.close();
        }, 500);
    };
}

    (function ($) {
        "use strict";
        function CalendarApp() {
            this.$calendar = $("#calendar");
            this.$calendarObj = null;
        }

        CalendarApp.prototype.onEventClick = function (e) {
            if (e.event.extendedProps && e.event.extendedProps.transaction) {
                showReservationModal(e.event);
            }
        };

        CalendarApp.prototype.init = function () {
            const self = this;
            this.$calendarObj = new FullCalendar.Calendar(this.$calendar[0], {
                slotDuration: "00:15:00",
                slotMinTime: "08:00:00",
                slotMaxTime: "19:00:00",
                themeSystem: "bootstrap",
                buttonText: {
                    today: "Today",
                    month: "Month",
                    week: "Week",
                    day: "Day",
                    list: "List",
                    prev: "Prev",
                    next: "Next"
                },
                initialView: "dayGridMonth",
                handleWindowResize: true,
                height: $(window).height() - 200,
                headerToolbar: {
                    left: "prev,next today",
                    center: "title",
                    right: "dayGridMonth,timeGridWeek,timeGridDay,listMonth"
                },
                initialEvents: allInitialEvents,
                editable: false,
                droppable: false,
                selectable: false,
                eventClick: function (e) {
                    self.onEventClick(e);
                }
            });
            this.$calendarObj.render();
        };

        $.CalendarApp = new CalendarApp();
        $.CalendarApp.Constructor = CalendarApp;
    })(window.jQuery);

    (function () {
        "use strict";
        window.jQuery.CalendarApp.init();
    })();

    function launchQRScanner() {
        const modal = new bootstrap.Modal(document.getElementById("qrScannerModal"));
        modal.show();

        const qrReader = new Html5Qrcode("qr-reader");

        qrReader.start(
            { facingMode: "environment" },
            {
                fps: 10,
                qrbox: { width: 250, height: 250 }
            },
            qrCodeMessage => {
                document.getElementById("qr-result").innerHTML =
                    `<div class="alert alert-success">Scanned QR Code: <strong>${qrCodeMessage}</strong></div>`;
                qrReader.stop();
            },
            errorMessage => {
                // silently fail
            }
        ).catch(err => {
            console.error("Unable to start scanner:", err);
        });
    }
</script>
