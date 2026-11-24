<?php
require_once __DIR__ . "/includes/auth.php";
requireLogin();
require_once __DIR__ . "/config/database.php";

function h($value): string
{
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    $pageError = "A valid service call ID is required.";
    $service = null;
} else {
    try {
        $stmt = $conn->prepare("
            SELECT
                sc.*,
                lm.diagnostic_lab_name,
                im.instrument_name,
                im.serial_number
            FROM service_calls sc
            LEFT JOIN lab_master lm ON lm.id = sc.lab_id
            LEFT JOIN instrument_master im ON im.id = sc.instrument_id
            WHERE sc.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();

        if (!$service) {
            http_response_code(404);
            $pageError = "The requested service call could not be found.";
        }
    } catch (Throwable $e) {
        error_log("Service call view error: " . $e->getMessage());
        http_response_code(500);
        $pageError = "Unable to load service call details. Please try again.";
        $service = null;
    }
}

function displayValue(array $service, string $key): string
{
    $value = $service[$key] ?? null;
    if ($value === null || $value === "") {
        return '<span class="empty-value">—</span>';
    }

    if (in_array($key, ["logged_datetime", "visit_in_time", "visit_out_time"], true)) {
        try {
            $date = new DateTime((string)$value);
            return h($date->format("d-m-Y h:i A"));
        } catch (Throwable $e) {
            // Fall through and display the database value as text.
        }
    }

    if ($key === "signed_report_url") {
        $url = trim((string)$value);
        $safeUrl = "";

        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ["http", "https"], true)) {
            $safeUrl = $url;
        } elseif (
            strpos($url, "..") === false &&
            preg_match('~^/?uploads/[A-Za-z0-9._/-]+$~', $url)
        ) {
            $safeUrl = ltrim($url, "/");
        }

        if ($safeUrl !== "") {
            return '<a class="report-link" href="' . h($safeUrl) . '" target="_blank" rel="noopener noreferrer">Open signed service report</a>'
                . '<div class="raw-url">' . h($url) . '</div>';
        }
        return h($url);
    }

    return nl2br(h($value));
}

$fields = [
    "id" => "Record ID",
    "call_id" => "Call ID",
    "status" => "Status",
    "lab_id" => "Lab ID",
    "instrument_id" => "Instrument ID",
    "call_type" => "Call Type",
    "problem_reported" => "Problem Reported",
    "contact_person" => "Contact Person",
    "closed_by" => "Closed By",
    "logged_datetime" => "Logged Date & Time",
    "logged_by" => "Logged By",
    "visit_in_time" => "Visit In Time",
    "visit_out_time" => "Visit Out Time",
    "observations" => "Observations",
    "actions_taken" => "Actions Taken",
    "spares_required" => "Whether Spares/Material is Required?",
    "spares_replaced" => "Whether Spare/Material is Replaced?",
    "spares_status" => "Spares Summary Status",
    "spares_details" => "Spares Details",
    "signed_report_url" => "Signed Service Report",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Call Details | Stedman Pharmaceuticals Pvt Ltd (Diagnostics Divison)</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #f4f7fb; color: #25364a; font-family: "Segoe UI", Arial, sans-serif; margin: 0; }
        .detail-header { background: linear-gradient(135deg, #0b4f71, #086f91); color: #fff; padding: 10px 4%; display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
        .detail-header h1 { margin: 0; font-size: 19px; line-height: 1.2; }
        .detail-header span { font-size: 13px; opacity: 0.9; }
        .detail-container { max-width: 1320px; margin: 12px auto; padding: 0 18px 16px; }
        .detail-card { background: #fff; border: 1px solid #e1e8ef; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,.05); overflow: hidden; }
        .detail-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; padding: 10px 18px; border-bottom: 1px solid #e7edf2; }
        .detail-toolbar h2 { color: #0b4f71; margin: 0; font-size: 18px; }
        .detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .detail-item { display: grid; grid-template-columns: 190px minmax(0, 1fr); border-bottom: 1px solid #edf1f5; border-right: 1px solid #edf1f5; }
        .detail-item:nth-child(2n) { border-right: 0; }
        .detail-label { padding: 8px 12px; color: #475467; background: #f8fafc; font-weight: 600; font-size: 12.5px; }
        .detail-value { padding: 8px 12px; font-size: 13px; line-height: 1.4; overflow-wrap: anywhere; }
        .empty-value { color: #98a2b3; }
        .report-link { color: #087b9e; font-weight: 600; font-size: 13px; }
        .raw-url { color: #667085; font-size: 11px; margin-top: 2px; }
        .detail-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .detail-button { display: inline-block; border: 0; border-radius: 6px; padding: 7px 13px; background: #0b4f71; color: white; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .detail-button.secondary { background: #eef4f7; color: #0b4f71; border: 1px solid #d5e2e9; }
        .error-card { padding: 24px; color: #b42318; }
        @media (max-width: 900px) {
            .detail-grid { grid-template-columns: 1fr; }
            .detail-item { border-right: 0; grid-template-columns: 170px minmax(0, 1fr); }
        }
        @media (max-width: 600px) {
            .detail-item { grid-template-columns: 135px minmax(0, 1fr); }
            .detail-header h1 { font-size: 17px; }
        }
    </style>
</head>
<body>
    <header class="detail-header">
        <h1>Stedman Pharmaceuticals Pvt Ltd</h1>
        <span>(Diagnostics Division)</span>
    </header>

    <main class="detail-container">
        <div class="detail-actions" style="margin-bottom:10px">
            <a class="detail-button secondary" href="index.php?section=completed">← Back to Completed Calls</a>
            <a class="detail-button secondary" href="index.php">Dashboard</a>
        </div>

        <section class="detail-card">
            <?php if (!$service): ?>
                <div class="error-card">
                    <h2>Service call unavailable</h2>
                    <p><?= h($pageError ?? "Unable to load service call details.") ?></p>
                </div>
            <?php else: ?>
                <div class="detail-toolbar">
                    <div>
                        <h2>Service Call: <?= h($service["call_id"] ?? ("Record #" . $service["id"])) ?></h2>
                        <p style="margin:2px 0 0;color:#667085;font-size:12px">All details below are read-only.</p>
                    </div>
                    <div class="detail-actions">
                        <?php if (!empty($service["signed_report_url"])): ?>
                            <?= displayValue($service, "signed_report_url") ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-grid">
                    <?php foreach ($fields as $key => $label): ?>
                        <div class="detail-item">
                            <div class="detail-label"><?= h($label) ?></div>
                            <div class="detail-value">
                                <?php
                                if ($key === "lab_id" && !empty($service["diagnostic_lab_name"])) {
                                    echo h($service["lab_id"]) . " — " . h($service["diagnostic_lab_name"]);
                                } elseif ($key === "instrument_id" && !empty($service["instrument_name"])) {
                                    echo h($service["instrument_id"]) . " — " . h($service["instrument_name"]);
                                    if (!empty($service["serial_number"])) {
                                        echo " (Serial: " . h($service["serial_number"]) . ")";
                                    }
                                } else {
                                    echo displayValue($service, $key);
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
