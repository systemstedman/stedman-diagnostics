<?php

require_once __DIR__ . "/includes/auth.php";

requireLogin();

require_once __DIR__ . "/config/database.php";

/*
|--------------------------------------------------------------------------
| Logged-in User Information
|--------------------------------------------------------------------------
*/

$userName = $_SESSION["full_name"] ?? $_SESSION["username"] ?? "User";
$userRole = $_SESSION["role"] ?? "Service Person";

$isAdmin = (strcasecmp(trim((string)$userRole), "Admin") === 0);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Stedman Pharmaceuticals Pvt Ltd (Diagnostics Divison) | Service Management System</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        /* ========================================
           GENERAL
        ======================================== */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fa;
            font-family: Arial, Helvetica, sans-serif;
            color: #344054;
        }

        .hidden {
            display: none !important;
        }

        /* ========================================
           TOP HEADER
        ======================================== */

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 10px 24px;
            background: #123b63;
            color: #ffffff;
        }

        .logo {
            display: flex;
            align-items: baseline;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 19px;
            font-weight: bold;
            white-space: nowrap;
        }

        .logo h3 {
            margin: 0;
            font-size: 19px;
            line-height: 1.2;
        }

        .logo h6 {
            margin: 0;
            font-size: 13px;
            font-weight: 500;
            opacity: 0.9;
        }

        .header-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 14px;
            font-size: 13px;
        }

        .user-information {
            display: flex;
            flex-direction: column;
            gap: 2px;
            text-align: right;
        }

        .user-name {
            font-size: 13px;
            font-weight: bold;
        }

        .user-role {
            display: inline-block;
            align-self: flex-end;
            padding: 2px 8px;
            border-radius: 12px;
            background: #e5efff;
            color: #123b63;
            font-size: 10px;
            font-weight: bold;
        }

        .logout-button {
            display: inline-block;
            padding: 6px 13px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 6px;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            white-space: nowrap;
            transition: 0.2s;
        }

        .logout-button:hover {
            background: #ffffff;
            color: #123b63;
        }

        /* ========================================
           MAIN NAVIGATION
        ======================================== */

        .main-navigation {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 20px;
            background: #ffffff;
            border-bottom: 1px solid #dddddd;
            overflow-x: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 13px;
            border: none;
            border-radius: 7px;
            background: transparent;
            color: #344054;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: 0.2s;
        }

        .nav-item:hover {
            background: #edf4fb;
            color: #123b63;
        }

        .nav-item.active {
            background: #123b63;
            color: #ffffff;
        }

        .nav-icon {
            font-size: 15px;
        }

        .admin-label {
            display: inline-block;
            margin-left: 3px;
            padding: 2px 5px;
            border-radius: 4px;
            background: #e8f1fb;
            color: #123b63;
            font-size: 9px;
            font-weight: bold;
        }

        /* ========================================
           MAIN PAGE CONTAINER
        ======================================== */

        .container {
            max-width: 1400px;
            margin: 10px auto;
            padding: 0 18px 14px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 12px;
            padding-bottom: 8px;
        }

        .section-header h2 {
            margin: 0;
            color: #123b63;
            font-size: 20px;
        }

        .section-header .button-row {
            margin-top: 0;
        }

        .welcome {
            padding: 2px 0 8px;
            margin-bottom: 10px;
        }

        .welcome h1 {
            display: inline;
            margin: 0;
            color: #123b63;
            font-size: 21px;
        }

        .welcome h2 {
            display: inline;
            margin: 0 0 0 6px;
            color: #086f91;
            font-size: 16px;
            font-weight: 600;
        }

        .welcome p {
            margin: 4px 0 0;
            color: #667085;
            font-size: 13px;
            line-height: 1.4;
        }

        /* ========================================
           HOME MENU CARDS
        ======================================== */

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(
                auto-fit,
                minmax(220px, 1fr)
            );
            gap: 20px;
            margin-top: 20px;
        }

        .menu-card {
            padding: 25px;
            background: #ffffff;
            border: 1px solid #e4e7ec;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.2s;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.08);
        }

        .menu-card .icon {
            margin-bottom: 15px;
            font-size: 30px;
        }

        .menu-card h2 {
            color: #123b63;
            font-size: 18px;
        }

        .menu-card p {
            color: #667085;
            font-size: 13px;
            line-height: 1.6;
        }

        /* ========================================
           TABLES
        ======================================== */

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #ffffff;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #e4e7ec;
            text-align: left;
        }

        th {
            background: #f2f6fa;
            color: #123b63;
        }

        /* ========================================
           BUTTONS
        ======================================== */

        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }

        .primary-btn,
        .secondary-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: 0.2s;
        }

        .primary-btn {
            background: #123b63;
            color: #ffffff;
        }

        .primary-btn:hover {
            background: #0b2946;
        }

        .secondary-btn {
            background: #e8eef5;
            color: #123b63;
        }

        .secondary-btn:hover {
            background: #d7e3f0;
        }

        /* ========================================
           RESPONSIVE DESIGN
        ======================================== */

        @media (max-width: 768px) {

            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 18px;
            }

            .header-right {
                width: 100%;
                justify-content: space-between;
            }

            .user-information {
                text-align: left;
            }

            .user-role {
                align-self: flex-start;
            }

            .main-navigation {
                padding: 10px;
            }

            .nav-item {
                padding: 10px 12px;
            }

            .container {
                padding: 0 12px;
            }

            .section-header {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<!-- ========================================
     HEADER
======================================== -->

<header class="header">

    <div class="logo">
        <h3> Stedman Pharmaceuticals Pvt Ltd </h3> <h6> (Diagnostics Division) </h6>
    </div>

    <div class="header-right">

        <div class="user-information">

            <span class="user-name">
                Welcome,
                <?= htmlspecialchars(
                    (string)$userName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

            <span class="user-role">
                <?= htmlspecialchars(
                    (string)$userRole,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>

        <a
            href="logout.php"
            class="logout-button"
            onclick="return confirm('Are you sure you want to log out?');"
        >
            Logout
        </a>

    </div>

</header>


<!-- ========================================
     MAIN NAVIGATION
======================================== -->

<nav class="main-navigation">

    <?php if ($isAdmin): ?>

    <button
        type="button"
        class="nav-item active"
        data-section="dashboard"
        onclick="showSection('dashboard', this)"
    >
        <span class="nav-icon">📊</span>
        Dashboard
    </button>

    <?php endif; ?>

    <button
        type="button"
        class="nav-item"
        data-section="register"
        onclick="showSection('register', this)"
    >
        <span class="nav-icon">📞</span>
        Log Call
    </button>

    <button
        type="button"
        class="nav-item"
        data-section="pending"
        onclick="showSection('pending', this)"
    >
        <span class="nav-icon">⏳</span>
        Active Calls
    </button>

    <button
        type="button"
        class="nav-item"
        data-section="completed"
        onclick="showSection('completed', this)"
    >
        <span class="nav-icon">✅</span>
        Completed Calls
    </button>

    <?php if ($isAdmin): ?>

        <!-- LAB MASTER -->

        <button
            type="button"
            class="nav-item"
            data-section="labMaster"
            onclick="showSection('labMaster', this)"
        >
            <span class="nav-icon">🧪</span>
            Lab Master
            <span class="admin-label">ADMIN</span>
        </button>

        <!-- INSTRUMENT MASTER -->

        <button
            type="button"
            class="nav-item"
            data-section="instrumentMaster"
            onclick="showSection('instrumentMaster', this)"
        >
            <span class="nav-icon">🔬</span>
            Instrument Master
            <span class="admin-label">ADMIN</span>
        </button>

    <?php endif; ?>

</nav>


<!-- ========================================
     MAIN CONTENT
======================================== -->

<div class="container">


    <!-- ====================================
         DASHBOARD
    ===================================== -->

    <section id="dashboard" class="section<?= $isAdmin ? "" : " hidden" ?>">

        <div class="welcome">

            <h1>Welcome to Stedman Pharmaceuticals Pvt Ltd </h1> <h2> (Diagnostics Divison) </h2>

            <p>
                Manage diagnostic laboratory service calls,
                application support, instrument servicing,
                and service reports from one place.
            </p>

        </div>

        <div class="section-header">

            <h2>📊 Dashboard Overview</h2>

            <div class="button-row">
                <button
                    type="button"
                    class="secondary-btn"
                    onclick="loadDashboard()"
                >
                    Refresh Dashboard
                </button>
                <button
                    type="button"
                    class="primary-btn"
                    onclick="exportDashboardReport()"
                >
                    Export to Excel
                </button>
            </div>

        </div>

        <div class="dashboard-summary">

            <div class="dashboard-row dashboard-header-row">

                <div class="dashboard-cell service-type-cell">
                    Service Type
                </div>

                <div class="dashboard-cell">Total</div>

                <div class="dashboard-cell">Completed</div>

                <div class="dashboard-cell">Pending</div>

            </div>

            <!-- SERVICE CALLS -->

            <div class="dashboard-row">

                <div class="dashboard-cell service-type-cell">
                    🔧 Service Calls
                </div>

                <div class="dashboard-cell">
                    <span id="totalServiceCalls" class="dashboard-number">0</span>
                    <small>Total Service Calls</small>
                </div>

                <div class="dashboard-cell">
                    <span id="completedServiceCalls" class="dashboard-number completed-number">0</span>
                    <small>Completed</small>
                </div>

                <div class="dashboard-cell">
                    <span id="pendingServiceCalls" class="dashboard-number pending-number">0</span>
                    <small>Pending</small>
                </div>

            </div>

            <!-- APPLICATION CALLS -->

            <div class="dashboard-row">

                <div class="dashboard-cell service-type-cell">
                    💻 Application Calls
                </div>

                <div class="dashboard-cell">
                    <span id="totalApplicationCalls" class="dashboard-number">0</span>
                    <small>Total Application Calls</small>
                </div>

                <div class="dashboard-cell">
                    <span id="completedApplicationCalls" class="dashboard-number completed-number">0</span>
                    <small>Completed</small>
                </div>

                <div class="dashboard-cell">
                    <span id="pendingApplicationCalls" class="dashboard-number pending-number">0</span>
                    <small>Pending</small>
                </div>

            </div>

        </div>

        <!-- ADDITIONAL SUMMARY -->

        <div class="dashboard-extra-summary">

            <div class="dashboard-extra-card">

                <div class="dashboard-extra-icon">🧪</div>

                <div class="dashboard-extra-content">

                    <span id="totalHospitals" class="dashboard-extra-number">0</span>

                    <span class="dashboard-extra-title">Active Labs</span>

                    <small>Total active diagnostic labs</small>

                </div>

            </div>

            <div class="dashboard-extra-card">

                <div class="dashboard-extra-icon">🔬</div>

                <div class="dashboard-extra-content">

                    <span id="totalInstruments" class="dashboard-extra-number">0</span>

                    <span class="dashboard-extra-title">Active Instruments</span>

                    <small>Total active instruments</small>

                </div>

            </div>

            <div class="dashboard-extra-card">

                <div class="dashboard-extra-icon">📅</div>

                <div class="dashboard-extra-content">

                    <span id="todayServices" class="dashboard-extra-number">0</span>

                    <span class="dashboard-extra-title">Today's Calls</span>

                    <small>Calls registered today</small>

                </div>

            </div>

            <div class="dashboard-extra-card">

                <div class="dashboard-extra-icon">📈</div>

                <div class="dashboard-extra-content">

                    <span id="monthlyServices" class="dashboard-extra-number">0</span>

                    <span class="dashboard-extra-title">This Month</span>

                    <small>Calls registered this month</small>

                </div>

            </div>

        </div>

        <!-- QUICK ACTIONS -->

        <div class="quick-actions">

            <h2>Quick Actions</h2>

            <div class="quick-action-grid">

                <button
                    type="button"
                    class="primary-btn"
                    onclick="showSection('register')"
                >
                    📞 Log New Call
                </button>

                <button
                    type="button"
                    class="secondary-btn"
                    onclick="showSection('pending')"
                >
                    ⏳ View Active Calls
                </button>

                <button
                    type="button"
                    class="secondary-btn"
                    onclick="showSection('completed')"
                >
                    ✅ View Completed Calls
                </button>

            </div>

        </div>

    </section>


    <!-- ====================================
         LOG CALL
    ===================================== -->

    <section id="register" class="section hidden">

        <div class="section-header">
            <h2>📞 Log New Call</h2>
        </div>

        <form id="registerForm">

            <div class="form-grid">

                <div class="form-group">

                    <label for="lab_id">Diagnostic Lab Name *</label>

                    <select id="lab_id" name="lab_id" required>

                        <option value="">Select Lab</option>

                    </select>

                </div>

                <div class="form-group">
    <label for="address">Lab Address</label>

    <textarea
        id="address"
        name="lab_address_display"
        readonly
    ></textarea>
</div>

<div class="form-group">
    <label for="city_location">City</label>

    <input
        type="text"
        id="city_location"
        readonly
    >
</div>

<div class="form-group">
    <label for="phone">Lab Phone / Mobile</label>

    <input
        type="text"
        id="phone"
        readonly
    >
</div>

<div class="form-group">
    <label for="email">Lab Email</label>

    <input
        type="text"
        id="email"
        readonly
    >
</div>
                <div class="form-group">
    <label for="instrument_id">Instrument</label>

    <select id="instrument_id" name="instrument_id">
        <option value="">Select Instrument (Optional)</option>
    </select>
</div>
                <div class="form-group">

                    <label for="contact_person">Contact Person *</label>

                    <input
                        type="text"
                        id="contact_person"
                        name="contact_person"
                        readonly
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="mobile">Contact Mobile Number *</label>

                    <input
                        type="text"
                        id="mobile"
                        name="mobile"
                        maxlength="20"
                        readonly
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="call_type">Call Type *</label>

                    <select id="call_type" name="call_type" required>

                        <option value="">Select Call Type</option>

                        <option value="Application Call">Application Call</option>

                        <option value="Service Call">Service Call</option>

                    </select>

                </div>

                <div class="form-group full-width">

                    <label for="problem_reported">Problem Reported *</label>

                    <textarea
                        id="problem_reported"
                        name="problem_reported"
                        required
                        placeholder="Describe the problem reported by the lab"
                    ></textarea>

                </div>

            </div>

            <div class="button-row">

                <button type="submit" class="primary-btn">
                    Submit Call
                </button>

                <button
                    type="button"
                    class="secondary-btn"
                    onclick="showSection(<?= $isAdmin ? "'dashboard'" : "'pending'" ?>)"
                >
                    Cancel
                </button>

            </div>

        </form>

        <div id="registerMessage"></div>

    </section>


    <!-- ====================================
         ACTIVE CALLS
    ===================================== -->

    <section id="pending" class="section hidden">

        <div class="section-header">

            <h2>⏳ Active Calls</h2>

            <div class="button-row">
                <button
                    type="button"
                    class="secondary-btn"
                    onclick="loadPendingServices()"
                >
                    Refresh
                </button>
                <button
                    type="button"
                    class="primary-btn"
                    onclick="exportPendingServices()"
                >
                    Export to Excel
                </button>
            </div>

        </div>

        <div id="pendingServices">
            <p>Loading active calls...</p>
        </div>

    </section>


    <!-- ====================================
         COMPLETED CALLS
    ===================================== -->

    <section id="completed" class="section hidden">

        <div class="section-header">

            <h2>✅ Completed Calls</h2>

            <div class="button-row">
                <button
                    type="button"
                    class="secondary-btn"
                    onclick="loadCompletedCalls()"
                >
                    Refresh
                </button>
                <button
                    type="button"
                    class="primary-btn"
                    onclick="exportCompletedCalls()"
                >
                    Export to Excel
                </button>
            </div>

        </div>

        <div id="completedServices">
            <p>Completed calls will appear here.</p>
        </div>

    </section>


    <?php if ($isAdmin): ?>

        <!-- ====================================
             LAB MASTER - ADMIN ONLY
        ===================================== -->

        <section id="labMaster" class="section hidden">

            <div class="section-header">

                <h2>🧪 Lab Master</h2>

                <div class="button-row">
                    <button
                        type="button"
                        class="secondary-btn"
                        onclick="loadLabMaster()"
                    >
                        Refresh
                    </button>
                    <button
                        type="button"
                        class="primary-btn"
                        onclick="exportLabMaster()"
                    >
                        Export to Excel
                    </button>
                </div>

            </div>

            <p>
                Manage diagnostic lab details and contact information.
            </p>

            <div id="labMasterContent">
                <p>Loading lab master...</p>
            </div>

        </section>


        <!-- ====================================
             INSTRUMENT MASTER - ADMIN ONLY
        ===================================== -->

        <section id="instrumentMaster" class="section hidden">

            <div class="section-header">

                <h2>🔬 Instrument Master</h2>

                <div class="button-row">
                    <button
                        type="button"
                        class="secondary-btn"
                        onclick="loadInstrumentMaster()"
                    >
                        Refresh
                    </button>
                    <button
                        type="button"
                        class="primary-btn"
                        onclick="exportInstrumentMaster()"
                    >
                        Export to Excel
                    </button>
                </div>

            </div>

            <p>
                View instruments, including model numbers,
                serial numbers and status.
            </p>

            <div id="instrumentMasterContent">
                <p>Loading instrument master...</p>
            </div>

        </section>

    <?php endif; ?>


    <!-- ====================================
         SERVICE CALL DETAILS
    ===================================== -->

    <section id="serviceDetails" class="section hidden">

        <div class="section-header">
            <h2>Service Call Details</h2>
            <button
                type="button"
                class="primary-btn"
                onclick="exportServiceDetails()"
            >
                Export to Excel
            </button>
        </div>

        <div id="selectedService"></div>

        <form id="completeServiceForm" enctype="multipart/form-data">

            <input
                type="hidden"
                name="service_call_id"
                id="service_call_id"
            >

            <div class="form-grid service-details-grid">

                <div class="form-group sd-col-3">

                    <label for="stage2_instrument_name">Instrument Name</label>

                    <input
                        type="text"
                        id="stage2_instrument_name"
                        name="instrument_name"
                    >

                </div>

                <div class="form-group sd-col-3">

                    <label for="stage2_serial_no">Serial Number</label>

                    <input
                        type="text"
                        id="stage2_serial_no"
                        name="serial_no"
                    >

                </div>
                <!-- VISIT IN TIME - MANDATORY -->

                <div class="form-group sd-col-3">

                    <label for="visit_in_time">Visit In Time *</label>

                    <input
                        type="datetime-local"
                        id="visit_in_time"
                        name="visit_in_time"
                        required
                    >

                    <small>Arrival date and time at lab.</small>

                </div>


                <!-- VISIT OUT TIME - MANDATORY -->

                <div class="form-group sd-col-3">

                    <label for="visit_out_time">Visit Out Time *</label>

                    <input
                        type="datetime-local"
                        id="visit_out_time"
                        name="visit_out_time"
                        required
                    >

                    <small>Departure date and time from lab.</small>

                </div>

                <div class="form-group sd-col-4">

                    <label for="stage2_problem_reported">Problem Reported *</label>

                    <textarea
                        id="stage2_problem_reported"
                        name="problem_reported"
                        required
                    ></textarea>

                </div>

                <div class="form-group sd-col-4">

                    <label for="observations">Observations *</label>

                    <textarea
                        id="observations"
                        name="observations"
                        required
                    ></textarea>

                </div>

                <div class="form-group sd-col-4">

                    <label for="actions_taken">Actions Taken *</label>

                    <textarea
                        id="actions_taken"
                        name="actions_taken"
                        required
                    ></textarea>

                </div>

                <div class="form-group sd-col-6">

                    <label>Whether Spares/Material is Required? *</label>

                    <div class="radio-group">
                        <label>
                            <input
                                type="radio"
                                name="spares_required"
                                value="No"
                                checked
                            >
                            No
                        </label>
                        <label>
                            <input
                                type="radio"
                                name="spares_required"
                                value="Yes"
                            >
                            Yes
                        </label>
                    </div>

                </div>

                <div class="form-group sd-col-6">

                    <label for="signed_report">Upload Signed Service Report</label>

                    <input
                        type="file"
                        id="signed_report"
                        name="signed_report"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                    >

                    <small>
                        Accepted formats: PDF, JPG, PNG, DOC, DOCX.
                    </small>

                </div>

                <div id="sparesRequiredFields" class="form-group sd-col-12 spares-inline-grid hidden">

                    <div class="spares-inline-col">
                        <label>Whether Spare/Material is Replaced ?</label>

                        <div class="radio-group">
                            <label>
                                <input
                                    type="radio"
                                    name="spares_to_be_replaced"
                                    value="No"
                                >
                                No
                            </label>
                            <label>
                                <input
                                    type="radio"
                                    name="spares_to_be_replaced"
                                    value="Yes"
                                >
                                Yes
                            </label>
                        </div>
                    </div>

                    <div class="spares-inline-col spares-details-col">
                        <label for="spares_details">Spares Details *</label>

                        <textarea
                            id="spares_details"
                            name="spares_details"
                            placeholder="Enter the spares or material details"
                        ></textarea>
                    </div>

                </div>

            </div>

            <div class="button-row">

                <button type="submit" class="primary-btn">
                    Complete Call
                </button>

                <button
                    type="button"
                    class="secondary-btn"
                    onclick="showSection('pending')"
                >
                    Back
                </button>

            </div>

        </form>

        <div id="completeMessage"></div>

    </section>

</div>


<!-- ========================================
     JAVASCRIPT
======================================== -->

<script src="assets/js/app.js"></script>

<script>

/*
|--------------------------------------------------------------------------
| User Role
|--------------------------------------------------------------------------
*/

const currentUserRole = <?= json_encode($userRole) ?>;
const isAdminUser = <?= $isAdmin ? 'true' : 'false' ?>;


/*
|--------------------------------------------------------------------------
| Section Navigation
|--------------------------------------------------------------------------
*/

function showSection(sectionId, clickedButton = null) {

    /*
     * Admin-only sections.
     */

    const adminOnlySections = [
        'labMaster',
        'instrumentMaster'
    ];

    if (
        !isAdminUser &&
        adminOnlySections.includes(sectionId)
    ) {

        alert(
            'Access denied. This section is available only to Admin users.'
        );

        return;
    }


    /*
     * Hide all sections.
     */

    const sections = document.querySelectorAll(
        '.container > .section'
    );

    sections.forEach(function(section) {
        section.classList.add('hidden');
    });


    /*
     * Display requested section.
     */

    const targetSection = document.getElementById(sectionId);

    if (!targetSection) {

        console.error('Section not found:', sectionId);

        return;
    }

    targetSection.classList.remove('hidden');


    /*
     * Update active navigation item.
     */

    document.querySelectorAll(
        '.main-navigation .nav-item'
    ).forEach(function(button) {

        button.classList.remove('active');

    });


    const navButton = clickedButton || document.querySelector(
        '.main-navigation .nav-item[data-section="' + sectionId + '"]'
    );

    if (navButton) {
        navButton.classList.add('active');
    }


    /*
     * Load Dashboard.
     */

    if (
        sectionId === 'dashboard' &&
        typeof loadDashboard === 'function'
    ) {

        loadDashboard();

    }


    /*
     * Load registration dropdowns.
     */

    if (sectionId === 'register') {

        if (typeof loadLabs === 'function') {
            loadLabs();
        }

    }


    /*
     * Load Active Calls.
     */

    if (
        sectionId === 'pending' &&
        typeof loadPendingServices === 'function'
    ) {

        loadPendingServices();

    }


    /*
     * Load Completed Calls.
     */

    if (
        sectionId === 'completed' &&
        typeof loadCompletedCalls === 'function'
    ) {

        loadCompletedCalls();

    }


    /*
     * Load Lab Master.
     */

    if (
        sectionId === 'labMaster' &&
        isAdminUser &&
        typeof loadLabMaster === 'function'
    ) {

        loadLabMaster();

    }


    /*
     * Load Instrument Master.
     */

    if (
        sectionId === 'instrumentMaster' &&
        isAdminUser &&
        typeof loadInstrumentMaster === 'function'
    ) {

        loadInstrumentMaster();

    }

}


/*
|--------------------------------------------------------------------------
| Initialize Application
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', function() {
    const requestedSection = new URLSearchParams(window.location.search).get('section');
    const allowedSections = ['dashboard', 'register', 'pending', 'completed', 'labMaster', 'instrumentMaster'];
    const defaultSection = <?= $isAdmin ? "'dashboard'" : "'pending'" ?>;
    const initialSection = allowedSections.includes(requestedSection)
        ? requestedSection
        : defaultSection;

    showSection(initialSection);
});

</script>

</body>
</html>
