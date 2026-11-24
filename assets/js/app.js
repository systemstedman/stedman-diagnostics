"use strict";

/* =========================================================
   GENERAL HELPERS
========================================================= */

async function fetchJson(url, options = {}) {
    let response;

    try {
        response = await fetch(url, {
            credentials: "same-origin",
            ...options,
            headers: {
                Accept: "application/json",
                ...(options.headers || {})
            }
        });
    } catch (error) {
        console.error("Network error:", url, error);

        throw new Error(
            "Cannot connect to the server. Check Apache and the URL."
        );
    }

    const text = await response.text();

    let data;

    try {
        data = JSON.parse(text);
    } catch (error) {
        console.error("API URL:", url);
        console.error("HTTP status:", response.status);
        console.error("Raw server response:", text);

        throw new Error(
            "PHP returned invalid JSON. Check the browser Console for the actual server response."
        );
    }

    if (!response.ok || data.success === false) {
        throw new Error(
            data.message ||
            data.error ||
            "Request failed with HTTP " + response.status
        );
    }

    return data;
}


/*
 * APIs in the existing project return either:
 *
 * 1. An array:
 *    [{...}, {...}]
 *
 * 2. An object:
 *    {success:true, data:[...]}
 *
 * This helper supports both.
 */

function responseRows(response) {
    if (Array.isArray(response)) {
        return response;
    }

    if (response && Array.isArray(response.data)) {
        return response.data;
    }

    return [];
}


function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, function (character) {
        const replacements = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        };

        return replacements[character];
    });
}


function displayMessage(elementId, message, type = "info") {
    const element = document.getElementById(elementId);

    if (!element) {
        console.log(message);
        return;
    }

    element.textContent = message;
    element.className = "message " + type;
}


function formatDate(value) {
    if (!value) {
        return "-";
    }

    const date = new Date(String(value).replace(" ", "T"));

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return date.toLocaleString();
}


function showApiError(error) {
    console.error(error);
    alert(error.message || "An unexpected error occurred.");
}


/* =========================================================
   DASHBOARD
========================================================= */

async function loadDashboard() {
    try {
        const response = await fetchJson("api/dashboard.php");

        const data = response.data || response;

        const mapping = {
            totalServiceCalls: "total_service_calls",
            completedServiceCalls: "completed_service_calls",
            pendingServiceCalls: "pending_service_calls",

            totalApplicationCalls: "total_application_calls",
            completedApplicationCalls: "completed_application_calls",
            pendingApplicationCalls: "pending_application_calls",

            totalHospitals: "total_labs",
            totalInstruments: "total_instruments",
            todayServices: "today_calls",
            monthlyServices: "monthly_calls"
        };

        Object.entries(mapping).forEach(([elementId, key]) => {
            const element = document.getElementById(elementId);

            if (element) {
                element.textContent = data[key] ?? 0;
            }
        });

    } catch (error) {
        console.error("Dashboard loading failed:", error);
    }
}


/* =========================================================
   LAB MASTER DATA FOR LOG CALL
========================================================= */

const NOT_AVAILABLE_TEXT = "Not Available";

function formatFieldOrNotAvailable(value) {
    const trimmed = String(value ?? "").trim();
    return trimmed !== "" ? trimmed : NOT_AVAILABLE_TEXT;
}

async function loadLabs() {
    const select = document.getElementById("lab_id");

    if (!select) {
        return;
    }

    clearLabDetails();

    select.replaceChildren(
        new Option("Loading laboratories...", "")
    );

    try {
        const response = await fetchJson("api/labs.php");
        const labs = responseRows(response);

        select.replaceChildren(
            new Option("Select Diagnostic Lab", "")
        );

        labs.forEach(lab => {
            const option = new Option(
                lab.diagnostic_lab_name,
                lab.id
            );

            option.dataset.city = String(lab.city_location ?? "").trim();
            option.dataset.contact = String(lab.lab_incharge_contact ?? "").trim();
            option.dataset.mobile = String(lab.mobile_number ?? "").trim();
            option.dataset.email = String(lab.email_address ?? "").trim();
            option.dataset.address = String(lab.full_address ?? "").trim();

            select.add(option);
        });

        if (labs.length === 0) {
            select.replaceChildren(
                new Option(NOT_AVAILABLE_TEXT, "")
            );
        }

    } catch (error) {
        console.error("Lab loading failed:", error);

        select.replaceChildren(
            new Option("Unable to load laboratories", "")
        );

        displayMessage(
            "registerMessage",
            error.message,
            "error"
        );
    }
}


/* =========================================================
   LAB SELECTION AND AUTO-FILL
========================================================= */

function clearLabDetails() {
    [
        "address",
        "city_location",
        "phone",
        "email",
        "contact_person",
        "mobile"
    ].forEach(id => {
        const element = document.getElementById(id);

        if (element) {
            element.value = "";
        }
    });

    const instrument = document.getElementById("instrument_id");

    if (instrument) {
        instrument.replaceChildren(
            new Option("Select Instrument (Optional)", "")
        );
    }
}


async function handleLabSelection() {
    const labSelect = document.getElementById("lab_id");

    if (!labSelect) {
        return;
    }

    clearLabDetails();

    const labId = labSelect.value;
    const option = labSelect.selectedOptions[0];

    if (!labId || !option) {
        return;
    }

    const address = document.getElementById("address");
    const city = document.getElementById("city_location");
    const phone = document.getElementById("phone");
    const email = document.getElementById("email");

    if (address) {
        address.value = formatFieldOrNotAvailable(option.dataset.address);
    }

    if (city) {
        city.value = formatFieldOrNotAvailable(option.dataset.city);
    }

    if (phone) {
        phone.value = formatFieldOrNotAvailable(option.dataset.mobile);
    }

    if (email) {
        email.value = formatFieldOrNotAvailable(option.dataset.email);
    }

    const contact = document.getElementById("contact_person");
    const mobile = document.getElementById("mobile");

    if (contact) {
        contact.value = formatFieldOrNotAvailable(option.dataset.contact);
    }

    if (mobile) {
        mobile.value = formatFieldOrNotAvailable(option.dataset.mobile);
    }

    await loadInstrumentsForLab(labId);
}


/* =========================================================
   INSTRUMENT DROPDOWN
========================================================= */

async function loadInstrumentsForLab(labId) {
    const select = document.getElementById("instrument_id");

    if (!select) {
        return;
    }

    select.replaceChildren(
        new Option("Loading instruments...", "")
    );

    try {
        const response = await fetchJson(
            "api/instruments.php?lab_id=" +
            encodeURIComponent(labId)
        );

        const instruments = responseRows(response);

        select.replaceChildren(
            new Option("Select Instrument (Optional)", "")
        );

        instruments.forEach(instrument => {
            const label =
                instrument.instrument_name +
                " - " +
                instrument.serial_number;

            const option = new Option(label, instrument.id);

            option.dataset.serial = instrument.serial_number || "";
            option.dataset.model = instrument.model_number || "";

            select.add(option);
        });

        if (instruments.length === 0) {
            select.replaceChildren(
                new Option(NOT_AVAILABLE_TEXT, "")
            );
        }

    } catch (error) {
        console.error("Instrument loading failed:", error);

        select.replaceChildren(
            new Option("Unable to load instruments", "")
        );

        displayMessage(
            "registerMessage",
            error.message,
            "error"
        );
    }
}


/* =========================================================
   ENGINEERS
========================================================= */

/* =========================================================
   REGISTER SERVICE CALL
========================================================= */

async function registerServiceCall(event) {
    event.preventDefault();

    const form = event.currentTarget;

    if (!form.reportValidity()) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');

    if (button) {
        button.disabled = true;
        button.textContent = "Submitting...";
    }

    displayMessage(
        "registerMessage",
        "Registering service call...",
        "info"
    );

    try {
        const response = await fetchJson(
            "api/create-service.php",
            {
                method: "POST",
                body: new FormData(form)
            }
        );

        displayMessage(
            "registerMessage",
            "Call registered successfully. Call ID: " +
                (response.call_id || ""),
            "success"
        );

        alert(
            "Service call registered successfully.\nCall ID: " +
            (response.call_id || "")
        );

        form.reset();
        clearLabDetails();

        await Promise.all([
            loadDashboard(),
            loadPendingServices()
        ]);

        await loadLabs();

    } catch (error) {
        console.error("Call registration failed:", error);

        displayMessage(
            "registerMessage",
            error.message,
            "error"
        );

        alert(error.message);

    } finally {
        if (button) {
            button.disabled = false;
            button.textContent = "Submit Call";
        }
    }
}


/* =========================================================
   ACTIVE CALLS
========================================================= */

async function loadPendingServices() {
    const container = document.getElementById("pendingServices");

    if (!container) {
        return;
    }

    container.innerHTML = "<p>Loading active calls...</p>";

    try {
        const response = await fetchJson("api/pending-services.php");
        const calls = responseRows(response);

        if (calls.length === 0) {
            container.innerHTML = "<p>No active service calls found.</p>";
            return;
        }

        let html = `
            <div class="table-responsive">
            <table id="activeCallsTable" class="data-table">
                <thead>
                    <tr>
                        <th>Call ID</th>
                        <th>Lab</th>
                        <th>Instrument</th>
                        <th>Call Type</th>
                        <th>Problem</th>
                        <th>Logged By</th>
                        <th>Status</th>
                        <th>Logged Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        calls.forEach(call => {
            html += `
                <tr>
                    <td>${escapeHtml(call.call_id)}</td>
                    <td>${escapeHtml(call.diagnostic_lab_name)}</td>
                    <td>${escapeHtml(call.instrument_name || "-")}
                        <br><small>${escapeHtml(call.serial_number || "")}</small>
                    </td>
                    <td>${escapeHtml(call.call_type)}</td>
                    <td>${escapeHtml(call.problem_reported)}</td>
                    <td>${escapeHtml(call.logged_by || "-")}</td>
                    <td>${escapeHtml(call.status)}</td>
                    <td>${escapeHtml(formatDate(call.logged_datetime))}</td>
                    <td>
                        <button
                            type="button"
                            class="primary-btn"
                            onclick="openServiceDetails(${Number(call.id)})"
                        >
                            Open
                        </button>
                    </td>
                </tr>
            `;
        });

        html += "</tbody></table></div>";

        container.innerHTML = html;

    } catch (error) {
        console.error("Active calls loading failed:", error);

        container.innerHTML =
            "<p class='error'>" +
            escapeHtml(error.message) +
            "</p>";
    }
}


/* =========================================================
   COMPLETED CALLS
========================================================= */

async function loadCompletedCalls() {
    const container = document.getElementById("completedServices");

    if (!container) {
        return;
    }

    container.innerHTML = "<p>Loading completed calls...</p>";

    try {
        const response = await fetchJson("api/completed-services.php");
        const calls = responseRows(response);

        if (calls.length === 0) {
            container.innerHTML = "<p>No completed calls found.</p>";
            return;
        }

        let html = `
            <div class="table-responsive">
            <table id="completedCallsTable" class="data-table">
                <thead>
                    <tr>
                        <th>Call ID</th>
                        <th>Lab</th>
                        <th>Instrument</th>
                        <th>Call Type</th>
                        <th>Problem</th>
                        <th>Logged By</th>
                        <th>Closed By</th>
                        <th>Visit Out</th>
                        <th>Report</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        calls.forEach(call => {
            let report = "-";

            if (call.signed_report_url) {
                const path = String(call.signed_report_url)
                    .split("/")
                    .map(encodeURIComponent)
                    .join("/");

                report = `
                    <a href="${escapeHtml(path)}" target="_blank" rel="noopener">
                        View Report
                    </a>
                `;
            }

            html += `
                <tr>
                    <td>${escapeHtml(call.call_id)}</td>
                    <td>${escapeHtml(call.diagnostic_lab_name)}</td>
                    <td>${escapeHtml(call.instrument_name || "-")}</td>
                    <td>${escapeHtml(call.call_type)}</td>
                    <td>${escapeHtml(call.problem_reported)}</td>
                    <td>${escapeHtml(call.logged_by || "-")}</td>
                    <td>${escapeHtml(call.closed_by || "-")}</td>
                    <td data-filter-value="${escapeHtml(call.visit_out_time || "")}">${escapeHtml(formatDate(call.visit_out_time))}</td>
                    <td>${report}</td>
                    <td>
                        <button
                            type="button"
                            class="primary-btn"
                            onclick="window.location.href='service-call-view.php?id=${Number(call.id)}'"
                        >
                            View
                        </button>
                    </td>
                </tr>
            `;
        });

        html += "</tbody></table></div>";

        container.innerHTML = html;

    } catch (error) {
        console.error("Completed calls loading failed:", error);

        container.innerHTML =
            "<p class='error'>" +
            escapeHtml(error.message) +
            "</p>";
    }
}


/* =========================================================
   OPEN SERVICE DETAILS
========================================================= */

async function openServiceDetails(id) {
    try {
        const response = await fetchJson(
            "api/service-details.php?id=" +
            encodeURIComponent(id)
        );

        const service = response.service || response.data || response;

        const selected = document.getElementById("selectedService");

        if (selected) {
            selected.innerHTML = `
                <div class="service-summary-bar">
                    <div class="service-summary-item"><strong>Call ID:</strong> <span>${escapeHtml(service.call_id)}</span></div>
                    <div class="service-summary-item"><strong>Lab:</strong> <span>${escapeHtml(service.diagnostic_lab_name)}</span></div>
                    <div class="service-summary-item"><strong>Status:</strong> <span>${escapeHtml(service.status || "-")}</span></div>
                    <div class="service-summary-item"><strong>Instrument:</strong> <span>${escapeHtml(service.instrument_name || "-")}</span></div>
                    <div class="service-summary-item"><strong>Phone:</strong> <span>${escapeHtml(service.lab_mobile || "-")}</span></div>
                    <div class="service-summary-item"><strong>Email:</strong> <span>${escapeHtml(service.email_address || "-")}</span></div>
                    <div class="service-summary-item service-summary-address"><strong>Address:</strong> <span>${escapeHtml(service.full_address || "-")}</span></div>
                </div>
            `;
        }

        document.getElementById("service_call_id").value = service.id;
        document.getElementById("stage2_instrument_name").value =
            service.instrument_name || "";
        document.getElementById("stage2_serial_no").value =
            service.serial_number || "";
        document.getElementById("stage2_problem_reported").value =
            service.problem_reported || "";

        const observations = document.getElementById("observations");
        const actions = document.getElementById("actions_taken");
        const sparesDetails = document.getElementById("spares_details");

        if (observations) {
            observations.value = service.observations || "";
        }

        if (actions) {
            actions.value = service.actions_taken || "";
        }

        if (sparesDetails) {
            sparesDetails.value = service.spares_details || "";
        }

        const visitIn = document.getElementById("visit_in_time");
        const visitOut = document.getElementById("visit_out_time");

        // Restore previously saved visit values; the service person may edit them.
        const toDateTimeLocal = value => {
            if (!value) return "";
            return String(value).replace(" ", "T").slice(0, 16);
        };
        if (visitIn) visitIn.value = toDateTimeLocal(service.visit_in_time);
        if (visitOut) visitOut.value = toDateTimeLocal(service.visit_out_time);

        const form = document.getElementById("completeServiceForm");
        if (form) {
            form.dataset.closedBy = service.current_user || "";
            form.dataset.sparesCarryForward =
                String(service.status || "").toLowerCase() === "spares to be replaced" ? "1" : "0";
        }

        const setRadio = (name, value) => {
            document.querySelectorAll(`#completeServiceForm input[name="${name}"]`).forEach(radio => {
                radio.checked = String(radio.value) === String(value);
            });
        };

        setRadio("spares_required", service.spares_required || (
            String(service.spares_status || "").toLowerCase() === "not required" ? "No" :
            (service.spares_status ? "Yes" : "No")
        ));
        setRadio("spares_to_be_replaced", service.spares_replaced || "");

        // Every previously saved field remains editable on a follow-up visit.
        ["observations", "actions_taken", "spares_details",
         "stage2_instrument_name", "stage2_serial_no", "stage2_problem_reported",
         "visit_in_time", "visit_out_time"].forEach(id => {
            const element = document.getElementById(id);
            if (element) element.readOnly = false;
        });
        updateSparesFields();
        updateCompletionButton();
        showSection("serviceDetails");

    } catch (error) {
        showApiError(error);
    }
}


/* =========================================================
   SPARES / MATERIAL WORKFLOW
========================================================= */

function getRadioValue(name) {
    const selected = document.querySelector(`input[name="${name}"]:checked`);
    return selected ? selected.value : "";
}

function updateSparesFields() {
    const required = getRadioValue("spares_required");
    const wrapper = document.getElementById("sparesRequiredFields");
    const details = document.getElementById("spares_details");

    if (!wrapper) return;

    const show = required === "Yes";
    wrapper.classList.toggle("hidden", !show);

    if (details) {
        details.required = show;
        if (!show && !details.readOnly) details.value = "";
    }

    const replacementRadios = document.querySelectorAll('input[name="spares_to_be_replaced"]');
    replacementRadios.forEach(radio => {
        radio.required = show;
    });

    updateCompletionButton();
}

function updateCompletionButton() {
    const form = document.getElementById("completeServiceForm");
    const button = form ? form.querySelector('button[type="submit"]') : null;
    const required = getRadioValue("spares_required");
    const replacement = getRadioValue("spares_to_be_replaced");
    const report = document.getElementById("signed_report");

    if (!button) return;

    if (required === "Yes" && replacement === "No") {
        button.textContent = "Save - Spares to be Replaced";
        if (report) report.required = false;
    } else {
        button.textContent = "Complete Call";
        if (report) report.required = true;
    }
}

/* =========================================================
   COMPLETE SERVICE CALL
========================================================= */

async function completeServiceCall(event) {
    event.preventDefault();

    const form = event.currentTarget;

    if (!form.reportValidity()) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');

    if (button) {
        button.disabled = true;
        button.textContent = "Saving...";
    }

    try {
        const response = await fetchJson(
            "api/complete-service.php",
            {
                method: "POST",
                body: new FormData(form)
            }
        );

        displayMessage(
            "completeMessage",
            response.message || "Service call completed.",
            "success"
        );

        alert(response.message || "Service call updated successfully.");

        form.reset();
        updateSparesFields();
        updateCompletionButton();

        await Promise.all([
            loadDashboard(),
            loadPendingServices(),
            loadCompletedCalls()
        ]);

        if (response.status === "Completed") {
            showSection("completed");
        } else {
            showSection("pending");
        }

    } catch (error) {
        console.error("Call completion failed:", error);

        displayMessage(
            "completeMessage",
            error.message,
            "error"
        );

        alert(error.message);

    } finally {
        if (button) {
            button.disabled = false;
            updateCompletionButton();
        }
    }
}


/* =========================================================
   ADMIN: LAB MASTER
========================================================= */

async function loadLabMaster() {
    const container = document.getElementById("labMasterContent");

    if (!container) {
        return;
    }

    container.innerHTML = "<p>Loading laboratories...</p>";

    try {
        const response = await fetchJson("api/labs.php?all=1");
        const labs = responseRows(response);

        let html = `
            <div> <h3>Add Laboratory</h3></div>

            <form id="labMasterForm">
                <input type="hidden" name="id" value="">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Lab Name *</label>
                        <input name="diagnostic_lab_name" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Person</label>
                        <input name="lab_incharge_contact">
                    </div>

                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input name="mobile_number">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email_address">
                    </div>

                    <div class="form-group">
                        <label>City</label>
                        <input name="city_location">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label>Full Address</label>
                        <textarea name="full_address"></textarea>
                    </div>
                </div>

                <button class="primary-btn" type="submit">
                    Save Laboratory
                </button>

                <button
                    class="secondary-btn"
                    type="button"
                    onclick="resetLabForm()"
                >
                    Clear
                </button>
            </form>

            <div id="labMasterMessage"></div>

            <h3>Existing Laboratories</h3>

            <div class="table-responsive">
            <table id="labMasterTable" class="data-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Lab Name</th>
                        <th>City</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        labs.forEach((lab, index) => {
            const json = JSON.stringify(lab)
                .replace(/</g, "\\u003c")
                .replace(/'/g, "\\u0027");

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(lab.diagnostic_lab_name)}</td>
                    <td>${escapeHtml(lab.city_location)}</td>
                    <td>${escapeHtml(lab.mobile_number)}</td>
                    <td>${escapeHtml(lab.email_address)}</td>
                    <td>${escapeHtml(lab.status)}</td>
                    <td>
                        <button
                            type="button"
                            class="secondary-btn"
                            onclick='editLab(${json})'
                        >
                            Edit
                        </button>
                    </td>
                </tr>
            `;
        });

        html += "</tbody></table></div>";

        container.innerHTML = html;

        document.getElementById("labMasterForm")
            .addEventListener("submit", saveLab);

    } catch (error) {
        console.error("Lab master loading failed:", error);

        container.innerHTML =
            "<p class='error'>" +
            escapeHtml(error.message) +
            "</p>";
    }
}


async function saveLab(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());

    const isEdit = Boolean(data.id);
    const url = "api/labs.php";

    if (isEdit) {
        data._method = "PUT";
    }

    try {
        const response = await fetchJson(url, {
            method: "POST",
            headers: isEdit
                ? {
                    "Content-Type": "application/json",
                    "X-HTTP-Method-Override": "PUT"
                }
                : {
                    "Content-Type": "application/json"
                },
            body: JSON.stringify(data)
        });

        displayMessage(
            "labMasterMessage",
            response.message || "Laboratory saved.",
            "success"
        );

        await loadLabMaster();
        await loadLabs();

    } catch (error) {
        displayMessage("labMasterMessage", error.message, "error");
    }
}


function editLab(lab) {
    const form = document.getElementById("labMasterForm");

    if (!form) {
        return;
    }

    Object.keys(lab).forEach(key => {
        if (form.elements[key]) {
            form.elements[key].value = lab[key] ?? "";
        }
    });

    const button = form.querySelector('button[type="submit"]');

    if (button) {
        button.textContent = "Update Laboratory";
    }

    form.scrollIntoView({ behavior: "smooth" });
}


function resetLabForm() {
    const form = document.getElementById("labMasterForm");

    if (form) {
        form.reset();
        form.elements.id.value = "";

        const button = form.querySelector('button[type="submit"]');

        if (button) {
            button.textContent = "Save Laboratory";
        }
    }
}


/* =========================================================
   ADMIN: INSTRUMENT MASTER
========================================================= */

async function loadInstrumentMaster() {
    const container = document.getElementById("instrumentMasterContent");

    if (!container) {
        return;
    }

    container.innerHTML = "<p>Loading instruments...</p>";

    try {
        const [instrumentResponse, labResponse] = await Promise.all([
            fetchJson("api/instruments.php?all=1"),
            fetchJson("api/labs.php?all=1")
        ]);

        const instruments = responseRows(instrumentResponse);
        const labs = responseRows(labResponse);

        let labOptions = '<option value="">Select Lab</option>';

        labs.forEach(lab => {
            labOptions += `
                <option value="${Number(lab.id)}">
                    ${escapeHtml(lab.diagnostic_lab_name)}
                </option>
            `;
        });

        let html = `
            <h3>Add Instrument</h3>

            <form id="instrumentMasterForm">
                <input type="hidden" name="id" value="">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Laboratory *</label>
                        <select name="lab_id" required>
                            ${labOptions}
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Instrument Name *</label>
                        <input name="instrument_name" required>
                    </div>

                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input name="manufacturer">
                    </div>

                    <div class="form-group">
                        <label>Model Number</label>
                        <input name="model_number">
                    </div>

                    <div class="form-group">
                        <label>Serial Number *</label>
                        <input name="serial_number" required>
                    </div>

                    <div class="form-group">
                        <label>Installation Date</label>
                        <input type="date" name="installation_date">
                    </div>

                    <div class="form-group">
                        <label>Warranty Status</label>
                        <input name="warranty_status">
                    </div>

                    <div class="form-group">
                        <label>Warranty Expiry Date</label>
                        <input type="date" name="warranty_expiry_date">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </div>

                <button class="primary-btn" type="submit">
                    Save Instrument
                </button>
            </form>

            <div id="instrumentMasterMessage"></div>

            <h3>Existing Instruments</h3>

            <div class="table-responsive">
            <table id="instrumentMasterTable" class="data-table">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Lab</th>
                        <th>Instrument</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
        `;

        instruments.forEach((instrument, index) => {
            const json = JSON.stringify(instrument)
                .replace(/</g, "\\u003c")
                .replace(/'/g, "\\u0027");

            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(instrument.diagnostic_lab_name)}</td>
                    <td>${escapeHtml(instrument.instrument_name)}</td>
                    <td>${escapeHtml(instrument.serial_number)}</td>
                    <td>${escapeHtml(instrument.status)}</td>
                    <td>
                        <button
                            type="button"
                            class="secondary-btn"
                            onclick='editInstrument(${json})'
                        >
                            Edit
                        </button>
                    </td>
                </tr>
            `;
        });

        html += "</tbody></table></div>";

        container.innerHTML = html;

        document.getElementById("instrumentMasterForm")
            .addEventListener("submit", saveInstrument);

    } catch (error) {
        console.error("Instrument master loading failed:", error);

        container.innerHTML =
            "<p class='error'>" +
            escapeHtml(error.message) +
            "</p>";
    }
}


async function saveInstrument(event) {
    event.preventDefault();

    const form = event.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());

    const isEdit = Boolean(data.id);

    if (isEdit) {
        data._method = "PUT";
    }

    try {
        const response = await fetchJson("api/instruments.php", {
            method: "POST",
            headers: isEdit
                ? {
                    "Content-Type": "application/json",
                    "X-HTTP-Method-Override": "PUT"
                }
                : {
                    "Content-Type": "application/json"
                },
            body: JSON.stringify(data)
        });

        displayMessage(
            "instrumentMasterMessage",
            response.message || "Instrument saved.",
            "success"
        );

        await loadInstrumentMaster();
        await loadLabInstrumentMapping();

    } catch (error) {
        displayMessage(
            "instrumentMasterMessage",
            error.message,
            "error"
        );
    }
}


function editInstrument(instrument) {
    const form = document.getElementById("instrumentMasterForm");

    if (!form) {
        return;
    }

    Object.keys(instrument).forEach(key => {
        if (form.elements[key]) {
            form.elements[key].value = instrument[key] ?? "";
        }
    });

    const button = form.querySelector('button[type="submit"]');

    if (button) {
        button.textContent = "Update Instrument";
    }

    form.scrollIntoView({ behavior: "smooth" });
}


/* =========================================================
   ADMIN: LAB–INSTRUMENT MAPPING
========================================================= */

/* =========================================================
   SECTION NAVIGATION
   index.php already defines showSection().
   Do not define another copy here.
========================================================= */


/* =========================================================
   INITIALIZE
========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    const labSelect = document.getElementById("lab_id");

    if (labSelect) {
        labSelect.addEventListener("change", handleLabSelection);
    }

    const registerForm = document.getElementById("registerForm");

    if (registerForm) {
        registerForm.addEventListener("submit", registerServiceCall);
    }

    const completionForm = document.getElementById("completeServiceForm");

    if (completionForm) {
        completionForm.addEventListener("submit", completeServiceCall);

        completionForm.querySelectorAll('input[name="spares_required"]').forEach(radio => {
            radio.addEventListener("change", updateSparesFields);
        });

        completionForm.querySelectorAll('input[name="spares_to_be_replaced"]').forEach(radio => {
            radio.addEventListener("change", updateCompletionButton);
        });

        updateSparesFields();
        updateCompletionButton();
    }

    loadLabs();
    loadEngineers();
    loadDashboard();
});



/* =========================================================
   OUTSIDE-TABLE, DEPENDENT COLUMN FILTERS
   - Filters are displayed above the table, never in the table header.
   - Dropdown values come from the currently loaded database-backed rows.
   - Each dropdown's values depend on all other active filters.
========================================================= */

function normalizeFilterText(value) {
    return String(value ?? "").replace(/\s+/g, " ").trim();
}

function getFilterCellValue(row, columnIndex) {
    const cell = row.cells[columnIndex];
    if (!cell) return "";

    // Prefer explicit data-filter-value where a table needs a clean value.
    if (cell.dataset && Object.prototype.hasOwnProperty.call(cell.dataset, "filterValue")) {
        return normalizeFilterText(cell.dataset.filterValue);
    }

    return normalizeFilterText(cell.textContent);
}

let searchableFilterDismissBound = false;

function closeAllSearchableFilterMenus(exceptContainer = null) {
    document.querySelectorAll(".searchable-filter-dropdown.open").forEach(container => {
        if (exceptContainer && container === exceptContainer) return;
        container.classList.remove("open");
        const menu = container.querySelector(".searchable-filter-menu");
        if (menu) menu.classList.add("hidden");
    });
}

function ensureSearchableFilterDismissHandler() {
    if (searchableFilterDismissBound) return;
    searchableFilterDismissBound = true;

    document.addEventListener("click", event => {
        if (!event.target.closest(".searchable-filter-dropdown")) {
            closeAllSearchableFilterMenus();
        }
    });
}

function applyTableFilters(root = document) {
    ensureSearchableFilterDismissHandler();

    const tables = [];

    if (root.matches && root.matches("table.data-table")) tables.push(root);
    if (root.querySelectorAll) tables.push(...root.querySelectorAll("table.data-table"));

    tables.forEach(table => {
        const thead = table.querySelector("thead");
        const tbody = table.querySelector("tbody");
        if (!thead || !tbody) return;

        // Remove any old in-table filter row from previous versions.
        thead.querySelectorAll(".column-filter-row").forEach(row => row.remove());

        const headerRow = thead.querySelector("tr");
        if (!headerRow) return;

        const headers = Array.from(headerRow.querySelectorAll("th"));
        if (!headers.length) return;

        const tableId = table.id || "";
        const isMasterTable = tableId === "labMasterTable" || tableId === "instrumentMasterTable";
        const skipFilterLabels = tableId === "activeCallsTable"
            ? new Set(["call id", "instrument", "problem", "action"])
            : tableId === "completedCallsTable"
                ? new Set(["visit out"])
                : isMasterTable
                    ? new Set(["s.no", "action"])
                    : new Set();

        const rows = Array.from(tbody.querySelectorAll("tr"))
            .filter(row => !row.classList.contains("empty-row") && row.cells.length > 0);

        // Place a separate toolbar directly before the table wrapper.
        const wrapper = table.closest(".table-responsive") || table;
        let toolbar = wrapper.previousElementSibling;

        // Completed calls have a dedicated Visit Out date-range toolbar.
        if (
            tableId === "completedCallsTable" &&
            toolbar &&
            toolbar.classList.contains("completed-date-filter-toolbar")
        ) {
            toolbar = toolbar.previousElementSibling;
        }

        if (!toolbar || !toolbar.classList.contains("table-filters-toolbar") || toolbar.dataset.forTable !== table.id) {
            toolbar = document.createElement("div");
            toolbar.className = "table-filters-toolbar";
            toolbar.dataset.forTable = table.id || ("table-" + Math.random().toString(36).slice(2, 10));
            if (!table.id) table.id = toolbar.dataset.forTable;
            wrapper.parentNode.insertBefore(toolbar, wrapper);
        }

        // Keep current selections only when this is the same table and the values remain valid.
        const previousSelections = new Map();
        toolbar.querySelectorAll("select.table-column-filter, input.table-column-search-input").forEach(control => {
            previousSelections.set(Number(control.dataset.columnIndex), control.value);
        });
        const previousGlobalSearch = toolbar.querySelector("input.table-global-search")?.value || "";
        toolbar.innerHTML = "";

        const title = document.createElement("div");
        title.className = "table-filters-title";
        title.textContent = isMasterTable ? "Search & Filter Table" : "Filter table";
        toolbar.appendChild(title);

        const controls = document.createElement("div");
        controls.className = "table-filters-controls";
        toolbar.appendChild(controls);

        let globalSearchTerm = previousGlobalSearch;
        let globalSearchInput = null;

        if (isMasterTable) {
            const searchLabel = document.createElement("label");
            searchLabel.className = "table-filter-field table-global-search-field";

            const searchText = document.createElement("span");
            searchText.textContent = "Search";
            searchLabel.appendChild(searchText);

            globalSearchInput = document.createElement("input");
            globalSearchInput.type = "search";
            globalSearchInput.className = "table-global-search";
            globalSearchInput.placeholder = tableId === "labMasterTable"
                ? "Search lab, city, phone, email..."
                : "Search lab, instrument, serial no...";
            globalSearchInput.value = globalSearchTerm;
            globalSearchInput.setAttribute("aria-label", "Search master table");
            searchLabel.appendChild(globalSearchInput);
            controls.appendChild(searchLabel);
        }

        const selections = headers.map((_, index) => previousSelections.get(index) || "");
        const selectionModes = headers.map(() => "exact");
        const selects = [];
        const searchableDropdowns = [];
        const availableValuesByColumn = headers.map(() => []);

        function rowMatchesGlobalSearch(row) {
            const query = normalizeFilterText(globalSearchTerm).toLocaleLowerCase();
            if (!query) return true;

            return headers.some((header, index) => {
                const headerLabel = normalizeFilterText(header.textContent).toLocaleLowerCase();
                if (headerLabel === "action") return false;
                return getFilterCellValue(row, index).toLocaleLowerCase().includes(query);
            });
        }

        function rowMatches(row, skipIndex = -1) {
            if (!rowMatchesGlobalSearch(row)) return false;

            return selections.every((selected, index) => {
                if (index === skipIndex || !selected) return true;
                const cellVal = getFilterCellValue(row, index).toLocaleLowerCase();
                const filterVal = normalizeFilterText(selected).toLocaleLowerCase();
                if (isMasterTable && selectionModes[index] === "contains") {
                    return cellVal.includes(filterVal);
                }
                return cellVal === filterVal;
            });
        }

        function renderSearchableMenu(index, showAll = false) {
            const widget = searchableDropdowns[index];
            if (!widget) return;

            const { input, menu, headerText } = widget;
            const query = showAll ? "" : normalizeFilterText(input.value).toLocaleLowerCase();
            const values = availableValuesByColumn[index] || [];
            const filteredValues = query
                ? values.filter(val => val.toLocaleLowerCase().includes(query))
                : values;

            menu.innerHTML = "";

            const allBtn = document.createElement("button");
            allBtn.type = "button";
            allBtn.className = "searchable-filter-option" + (!selections[index] ? " selected" : "");
            allBtn.textContent = "All " + headerText;
            allBtn.addEventListener("mousedown", event => {
                event.preventDefault();
                selections[index] = "";
                selectionModes[index] = "exact";
                input.value = "";
                closeAllSearchableFilterMenus();
                refreshOptions();
                renderFilteredRows();
            });
            menu.appendChild(allBtn);

            if (!filteredValues.length) {
                const emptyItem = document.createElement("div");
                emptyItem.className = "searchable-filter-empty";
                emptyItem.textContent = "No matching options";
                menu.appendChild(emptyItem);
                return;
            }

            filteredValues.forEach(value => {
                const optionBtn = document.createElement("button");
                optionBtn.type = "button";
                const isSelected = selections[index] &&
                    selections[index].toLocaleLowerCase() === value.toLocaleLowerCase();
                optionBtn.className = "searchable-filter-option" + (isSelected ? " selected" : "");
                optionBtn.textContent = value.length > 90 ? value.slice(0, 87) + "..." : value;
                optionBtn.title = value;
                optionBtn.addEventListener("mousedown", event => {
                    event.preventDefault();
                    selections[index] = value;
                    selectionModes[index] = "exact";
                    input.value = value;
                    closeAllSearchableFilterMenus();
                    refreshOptions();
                    renderFilteredRows();
                });
                menu.appendChild(optionBtn);
            });
        }

        function refreshOptions() {
            headers.forEach((header, index) => {
                const headerLabel = normalizeFilterText(header.textContent).toLocaleLowerCase();
                if (skipFilterLabels.has(headerLabel)) return;

                const availableRows = rows.filter(row => rowMatches(row, index));
                const distinct = new Map();
                availableRows.forEach(row => {
                    const value = getFilterCellValue(row, index);
                    if (value && !distinct.has(value.toLocaleLowerCase())) {
                        distinct.set(value.toLocaleLowerCase(), value);
                    }
                });

                const sortedValues = Array.from(distinct.values()).sort((a, b) => a.localeCompare(b, undefined, {
                    numeric: true,
                    sensitivity: "base"
                }));
                availableValuesByColumn[index] = sortedValues;

                if (isMasterTable) {
                    const widget = searchableDropdowns[index];
                    if (!widget) return;
                    if (widget.container.classList.contains("open")) {
                        const showAll = selectionModes[index] === "exact";
                        renderSearchableMenu(index, showAll);
                    }
                    return;
                }

                const select = selects[index];
                if (!select) return;

                const currentValue = selections[index];
                select.innerHTML = "";

                const allOption = document.createElement("option");
                allOption.value = "";
                allOption.textContent = "All " + (normalizeFilterText(header.textContent) || ("Column " + (index + 1)));
                select.appendChild(allOption);

                sortedValues.forEach(value => {
                    const option = document.createElement("option");
                    option.value = value;
                    option.textContent = value.length > 90 ? value.slice(0, 87) + "..." : value;
                    option.title = value;
                    select.appendChild(option);
                });

                const valid = Array.from(select.options).some(option =>
                    option.value.toLocaleLowerCase() === currentValue.toLocaleLowerCase()
                );
                if (currentValue && valid) {
                    select.value = currentValue;
                } else {
                    selections[index] = "";
                    select.value = "";
                }
            });
        }

        function renderFilteredRows() {
            rows.forEach(row => {
                const columnMatch = rowMatches(row);
                const dateMatch = table.id === "completedCallsTable"
                    ? row.dataset.completedDateMatch !== "0"
                    : true;
                row.style.display = columnMatch && dateMatch ? "" : "none";
            });

            // Refresh dependent dropdown values after applying all current selections.
            refreshOptions();

            let emptyRow = tbody.querySelector("tr.filter-empty-row");
            const visibleCount = rows.filter(row => row.style.display !== "none").length;
            if (!visibleCount) {
                if (!emptyRow) {
                    emptyRow = document.createElement("tr");
                    emptyRow.className = "filter-empty-row";
                    const cell = document.createElement("td");
                    cell.colSpan = headers.length;
                    cell.textContent = "No records match the selected filters.";
                    cell.className = "table-filter-empty";
                    emptyRow.appendChild(cell);
                    tbody.appendChild(emptyRow);
                }
            } else if (emptyRow) {
                emptyRow.remove();
            }
        }

        if (globalSearchInput) {
            globalSearchInput.addEventListener("input", () => {
                globalSearchTerm = globalSearchInput.value;
                renderFilteredRows();
            });
        }

        headers.forEach((header, index) => {
            const headerLabel = normalizeFilterText(header.textContent).toLocaleLowerCase();
            if (skipFilterLabels.has(headerLabel)) return;

            const headerText = normalizeFilterText(header.textContent) || ("Column " + (index + 1));
            const label = document.createElement("label");
            label.className = "table-filter-field";

            const labelText = document.createElement("span");
            labelText.textContent = headerText;
            label.appendChild(labelText);

            if (isMasterTable) {
                const container = document.createElement("div");
                container.className = "searchable-filter-dropdown";

                const inputWrap = document.createElement("div");
                inputWrap.className = "searchable-filter-input-wrap";

                const input = document.createElement("input");
                input.type = "text";
                input.className = "table-column-search-input";
                input.dataset.columnIndex = String(index);
                input.autocomplete = "off";
                input.placeholder = "All " + headerText;
                input.value = selections[index] || "";
                input.setAttribute("aria-label", "Search or select " + headerText);

                const toggleBtn = document.createElement("button");
                toggleBtn.type = "button";
                toggleBtn.className = "searchable-filter-toggle";
                toggleBtn.textContent = "▾";
                toggleBtn.setAttribute("aria-label", "Toggle " + headerText + " dropdown");

                const menu = document.createElement("div");
                menu.className = "searchable-filter-menu hidden";

                inputWrap.appendChild(input);
                inputWrap.appendChild(toggleBtn);
                container.appendChild(inputWrap);
                container.appendChild(menu);
                label.appendChild(container);
                controls.appendChild(label);

                searchableDropdowns[index] = { container, input, toggleBtn, menu, headerText };

                const openMenu = (showAll = false) => {
                    closeAllSearchableFilterMenus(container);
                    renderSearchableMenu(index, showAll);
                    container.classList.add("open");
                    menu.classList.remove("hidden");
                };

                input.addEventListener("focus", () => {
                    openMenu(selectionModes[index] === "exact");
                });

                input.addEventListener("click", () => {
                    openMenu(selectionModes[index] === "exact");
                });

                input.addEventListener("input", () => {
                    const val = input.value;
                    selections[index] = val;
                    selectionModes[index] = val.trim() ? "contains" : "exact";
                    renderFilteredRows();
                    openMenu(false);
                });

                input.addEventListener("keydown", event => {
                    if (event.key === "Escape") {
                        closeAllSearchableFilterMenus();
                    } else if (event.key === "Enter") {
                        event.preventDefault();
                        const firstOption = menu.querySelector(".searchable-filter-option:not(:first-child)");
                        if (firstOption && input.value.trim()) {
                            firstOption.dispatchEvent(new MouseEvent("mousedown", { bubbles: true }));
                        } else {
                            closeAllSearchableFilterMenus();
                        }
                    }
                });

                toggleBtn.addEventListener("click", event => {
                    event.preventDefault();
                    event.stopPropagation();
                    if (container.classList.contains("open")) {
                        closeAllSearchableFilterMenus();
                    } else {
                        openMenu(true);
                        input.focus();
                    }
                });
            } else {
                const select = document.createElement("select");
                select.className = "table-column-filter";
                select.dataset.columnIndex = String(index);
                select.setAttribute("aria-label", "Filter by " + headerText);
                select.addEventListener("change", () => {
                    selections[index] = select.value;
                    // A changed filter can invalidate selected values in other dropdowns.
                    refreshOptions();
                    renderFilteredRows();
                });

                selects[index] = select;
                label.appendChild(select);
                controls.appendChild(label);
            }
        });

        const reset = document.createElement("button");
        reset.type = "button";
        reset.className = "secondary-btn table-filter-reset";
        reset.textContent = "Clear Filters";
        reset.addEventListener("click", () => {
            globalSearchTerm = "";
            if (globalSearchInput) globalSearchInput.value = "";
            selections.fill("");
            selectionModes.fill("exact");
            selects.forEach(select => { select.value = ""; });
            searchableDropdowns.forEach(widget => {
                if (widget && widget.input) widget.input.value = "";
            });
            closeAllSearchableFilterMenus();
            rows.forEach(row => { row.style.display = ""; });
            const emptyRow = tbody.querySelector("tr.filter-empty-row");
            if (emptyRow) emptyRow.remove();
            refreshOptions();
        });
        toolbar.appendChild(reset);

        refreshOptions();
        renderFilteredRows();
    });
}

function applyCompletedVisitOutDateFilter() {
    const table = document.getElementById("completedCallsTable");
    if (!table) return;

    const wrapper = table.closest(".table-responsive") || table;
    let toolbar = wrapper.previousElementSibling;

    if (!toolbar || !toolbar.classList.contains("completed-date-filter-toolbar")) {
        toolbar = document.createElement("div");
        toolbar.className = "completed-date-filter-toolbar table-filters-toolbar";
        wrapper.parentNode.insertBefore(toolbar, wrapper);
    }

    toolbar.innerHTML = "";

    const title = document.createElement("div");
    title.className = "table-filters-title";
    title.textContent = "Visit Out Date Range";
    toolbar.appendChild(title);

    const controls = document.createElement("div");
    controls.className = "table-filters-controls";
    toolbar.appendChild(controls);

    function addDateField(labelText, id) {
        const label = document.createElement("label");
        label.className = "table-filter-field";

        const text = document.createElement("span");
        text.textContent = labelText;
        label.appendChild(text);

        const input = document.createElement("input");
        input.type = "date";
        input.id = id;
        input.className = "completed-date-filter";
        label.appendChild(input);
        controls.appendChild(label);

        return input;
    }

    const fromInput = addDateField("From", "completedVisitOutFrom");
    const toInput = addDateField("To", "completedVisitOutTo");

    const selectButton = document.createElement("button");
    selectButton.type = "button";
    selectButton.className = "primary-btn";
    selectButton.textContent = "Select";
    controls.appendChild(selectButton);

    const clearButton = document.createElement("button");
    clearButton.type = "button";
    clearButton.className = "secondary-btn";
    clearButton.textContent = "Clear";
    controls.appendChild(clearButton);

    function applyRange() {
        const from = fromInput.value;
        const to = toInput.value;

        if (from && to && from > to) {
            alert("From date cannot be later than To date.");
            return;
        }

        table.querySelectorAll("tbody tr").forEach(row => {
            if (row.classList.contains("filter-empty-row")) return;

            const visitOutCell = row.cells[7];
            const raw = visitOutCell?.dataset.filterValue || visitOutCell?.textContent || "";
            const date = String(raw).trim().slice(0, 10);

            const matchesFrom = !from || (date && date >= from);
            const matchesTo = !to || (date && date <= to);

            row.dataset.completedDateMatch =
                matchesFrom && matchesTo ? "1" : "0";
        });

        // Combine the date-range result with the normal column filters.
        table.querySelectorAll("tbody tr").forEach(row => {
            if (!row.classList.contains("filter-empty-row")) {
                if (row.dataset.completedDateMatch === "0") {
                    row.style.display = "none";
                }
            }
        });

        let emptyRow = table.querySelector("tbody tr.filter-empty-row");
        const visibleRows = Array.from(
            table.querySelectorAll("tbody tr")
        ).filter(row =>
            !row.classList.contains("filter-empty-row") &&
            row.style.display !== "none"
        );

        if (!visibleRows.length) {
            if (!emptyRow) {
                emptyRow = document.createElement("tr");
                emptyRow.className = "filter-empty-row";
                const cell = document.createElement("td");
                cell.colSpan = table.querySelectorAll("thead th").length;
                cell.textContent = "No records match the selected date range.";
                cell.className = "table-filter-empty";
                emptyRow.appendChild(cell);
                table.querySelector("tbody").appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    }

    selectButton.addEventListener("click", applyRange);

    clearButton.addEventListener("click", () => {
        fromInput.value = "";
        toInput.value = "";
        table.querySelectorAll("tbody tr").forEach(row => {
            if (!row.classList.contains("filter-empty-row")) {
                row.dataset.completedDateMatch = "1";
                row.style.display = "";
            }
        });
        const emptyRow = table.querySelector("tbody tr.filter-empty-row");
        if (emptyRow) emptyRow.remove();
    });
}


let tableFilterObserver = null;

document.addEventListener("DOMContentLoaded", function () {
    applyTableFilters();

    if (tableFilterObserver) tableFilterObserver.disconnect();

    tableFilterObserver = new MutationObserver(function (mutations) {
        const affectedTables = new Set();

        mutations.forEach(mutation => {
            Array.from(mutation.addedNodes).forEach(node => {
                if (node.nodeType !== Node.ELEMENT_NODE) return;
                if (node.matches("table.data-table")) affectedTables.add(node);
                if (node.matches("tbody")) {
                    const parentTable = node.closest("table.data-table");
                    if (parentTable) affectedTables.add(parentTable);
                }
                node.querySelectorAll?.("table.data-table").forEach(table => affectedTables.add(table));
                node.querySelectorAll?.("tbody").forEach(tbody => {
                    const parentTable = tbody.closest("table.data-table");
                    if (parentTable) affectedTables.add(parentTable);
                });
            });
        });

        affectedTables.forEach(table => {
            applyTableFilters(table);
            if (table.id === "completedCallsTable") {
                applyCompletedVisitOutDateFilter();
            }
        });
    });

    tableFilterObserver.observe(document.body, { childList: true, subtree: true });

    if (document.getElementById("completedCallsTable")) {
        applyCompletedVisitOutDateFilter();
    }
});


/* =========================================================
   EXCEL REPORT EXPORTS
   Uses native browser HTML/Excel format so no external
   JavaScript library is required.
========================================================= */

function excelCell(value) {
    const text = String(value ?? "");
    return "<td>" + escapeHtml(text).replace(/\n/g, "<br>") + "</td>";
}

function downloadExcelFile(filename, title, headers, rows) {
    if (!rows || rows.length === 0) {
        alert("No data available to export.");
        return;
    }

    const headerHtml = headers
        .map(header => "<th>" + escapeHtml(header) + "</th>")
        .join("");

    const bodyHtml = rows.map(row =>
        "<tr>" + row.map(excelCell).join("") + "</tr>"
    ).join("");

    const html = `
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                h2 { font-size: 18px; }
                table { border-collapse: collapse; }
                th, td {
                    border: 1px solid #999;
                    padding: 6px;
                    vertical-align: top;
                }
                th {
                    font-weight: bold;
                    background: #eaf1f8;
                }
            </style>
        </head>
        <body>
            <h2>${escapeHtml(title)}</h2>
            <p>Generated: ${escapeHtml(new Date().toLocaleString())}</p>
            <table>
                <thead><tr>${headerHtml}</tr></thead>
                <tbody>${bodyHtml}</tbody>
            </table>
        </body>
        </html>
    `;

    const blob = new Blob(
        ["\ufeff", html],
        { type: "application/vnd.ms-excel;charset=utf-8" }
    );

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = url;
    link.download = filename.endsWith(".xls") ? filename : filename + ".xls";
    document.body.appendChild(link);
    link.click();
    link.remove();

    setTimeout(() => URL.revokeObjectURL(url), 1000);
}


function exportDashboardReport() {
    const get = id => document.getElementById(id)?.textContent?.trim() || "0";

    const rows = [
        ["Service Calls", get("totalServiceCalls"), get("completedServiceCalls"), get("pendingServiceCalls")],
        ["Application Calls", get("totalApplicationCalls"), get("completedApplicationCalls"), get("pendingApplicationCalls")],
        ["Active Diagnostic Labs", get("totalHospitals"), "", ""],
        ["Active Instruments", get("totalInstruments"), "", ""],
        ["Calls Logged Today", get("todayServices"), "", ""],
        ["Calls Logged This Month", get("monthlyServices"), "", ""]
    ];

    downloadExcelFile(
        "dashboard-report.xls",
        "Stedman Pharmaceuticals Pvt Ltd (Diagnostics Divison) - Dashboard Report",
        ["Service Type", "Total", "Completed", "Pending"],
        rows
    );
}


function exportVisibleTable(selector, filename, title) {
    const table = document.querySelector(selector);
    if (!table) {
        alert("Please open the table before exporting.");
        return;
    }

    const visible = element => {
        const style = window.getComputedStyle(element);
        return style.display !== "none" && style.visibility !== "hidden";
    };

    const headerCells = Array.from(table.querySelectorAll("thead tr:last-child th"))
        .filter(visible);
    const headers = headerCells.map(cell => cell.innerText.trim());

    const rows = Array.from(table.querySelectorAll("tbody tr"))
        .filter(row => visible(row) && row.offsetParent !== null)
        .map(row => Array.from(row.cells)
            .filter(visible)
            .map(cell => {
                const link = cell.querySelector("a");
                const button = cell.querySelector("button");
                if (link) return link.innerText.trim() || link.href;
                if (button) return button.innerText.trim();
                return cell.innerText.trim().replace(/\\s+/g, " ");
            }));

    if (!headers.length) {
        alert("No visible table columns are available to export.");
        return;
    }

    downloadExcelFile(filename, title, headers, rows);
}


async function exportPendingServices() {
    exportVisibleTable("#pendingServices table", "active-service-calls.xls",
        "Stedman Pharmaceuticals Pvt Ltd - Filtered Active Service Calls");
}

async function exportCompletedCalls() {
    exportVisibleTable("#completedServices table", "completed-service-calls.xls",
        "Stedman Pharmaceuticals Pvt Ltd - Filtered Completed Service Calls");
}

async function exportLabMaster() {
    exportVisibleTable("#labMasterContent table", "lab-master-report.xls",
        "Stedman Pharmaceuticals Pvt Ltd - Filtered Laboratory Master");
}

async function exportInstrumentMaster() {
    exportVisibleTable("#instrumentMasterContent table", "instrument-master-report.xls",
        "Stedman Pharmaceuticals Pvt Ltd - Filtered Instrument Master");
}