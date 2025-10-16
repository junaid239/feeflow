<?php
/**
 * Main Interface Template for FeeFlow
 * Save this file as: templates/main-interface.php
 * FIXED: Monthly Fee input box sizing issue
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="feeflow-app" class="feeflow-container">

    <div class="feeflow-nav">
        <button class="feeflow-nav-btn active" data-section="transactions">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Add Transaction
        </button>
        <button class="feeflow-nav-btn" data-section="students">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Student Management
        </button>
        <button class="feeflow-nav-btn" data-section="reports">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            Reports
        </button>
        <button class="feeflow-nav-btn" data-section="trash">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            Trash
        </button>
        <button class="feeflow-nav-btn" data-section="settings">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M12 1v6m0 6v6m8.66-15.66l-4.24 4.24m-8.49 8.49l-4.24 4.24M23 12h-6m-6 0H1m20.66 8.66l-4.24-4.24m-8.49-8.49l-4.24-4.24"></path>
            </svg>
            Settings
        </button>
    </div>

    <div id="feeflow-loader" class="feeflow-loader" style="display: none;">
        <div class="spinner"></div>
    </div>

    <div id="section-transactions" class="feeflow-section active">
        <div class="feeflow-header">
            <h2>Add Transaction</h2>
            <p class="feeflow-subtitle">Record a new fee payment</p>
        </div>

        <div class="feeflow-card">
            <div class="feeflow-form-group">
                <label for="student-search">Search Student (Name or Phone) *</label>
                <input type="text" id="student-search" class="feeflow-input" placeholder="Start typing name or phone number...">
                <div id="student-results" class="student-results"></div>
            </div>

            <div id="transaction-form" style="display: none;">
                <input type="hidden" id="selected-student-id">

                <div class="student-info-card">
                    <h3 id="display-student-name"></h3>
                    <p><strong>Phone:</strong> <span id="display-parent-phone"></span></p>
                    <p><strong>Subjects:</strong> <span id="display-subjects"></span></p>
                    <p><strong>Monthly Fee:</strong> ₹<span id="display-monthly-fee"></span></p>
                    <p><strong>Last Payment:</strong> <span id="display-last-payment"></span></p>
                </div>

                <div class="feeflow-form-row">
                    <div class="feeflow-form-group">
                        <label for="transaction-amount">Amount (₹) *</label>
                        <input type="number" id="transaction-amount" class="feeflow-input" step="0.01" required>
                    </div>

                    <div class="feeflow-form-group">
                        <label for="transaction-month">Payment Month *</label>
                        <input type="month" id="transaction-month" class="feeflow-input feeflow-monthpicker" required>
                    </div>
                </div>

                <div class="feeflow-form-row">
                    <div class="feeflow-form-group">
                        <label for="transaction-method">Payment Method *</label>
                        <select id="transaction-method" class="feeflow-input" required>
                            <option value="Cash" selected>Cash</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="PhonePe">PhonePe</option>
                            <option value="GooglePay">Google Pay</option>
                        </select>
                    </div>

                    <div class="feeflow-form-group">
                        <label for="transaction-date">Payment Date *</label>
                        <input type="date" id="transaction-date" class="feeflow-input feeflow-datepicker" required>
                    </div>
                </div>

                <div class="feeflow-form-group">
                    <label for="transaction-notes">Notes (Optional)</label>
                    <textarea id="transaction-notes" class="feeflow-input" rows="3" placeholder="Any additional notes..."></textarea>
                </div>

                <div class="feeflow-form-actions">
                    <button type="button" id="cancel-transaction" class="feeflow-btn feeflow-btn-secondary">Cancel</button>
                    <button type="button" id="submit-transaction" class="feeflow-btn feeflow-btn-primary">Submit Payment</button>
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div class="feeflow-dashboard">
            <div class="feeflow-header">
                <div>
                    <h2>Dashboard - Current Month Overview</h2>
                    <p class="feeflow-subtitle dashboard-current-month" id="dashboard-month-name">Loading...</p>
                </div>
            </div>

            <!-- Quick Stats Cards -->
            <div class="dashboard-stats">
                <div class="stat-card stat-card-blue">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value" id="stat-total-students">0</div>
                    </div>
                </div>

                <div class="stat-card stat-card-green">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Revenue This Month</div>
                        <div class="stat-value" id="stat-monthly-revenue">₹0</div>
                    </div>
                </div>

                <div class="stat-card stat-card-success">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Paid Students</div>
                        <div class="stat-value" id="stat-paid-students">0</div>
                    </div>
                </div>

                <div class="stat-card stat-card-warning">
                    <div class="stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Unpaid Students</div>
                        <div class="stat-value" id="stat-unpaid-students">0</div>
                    </div>
                </div>
            </div>

            <!-- Fee Collection Progress Bar -->
            <div class="feeflow-card">
                <h3>Fee Collection Progress</h3>
                <p class="card-description">Track your monthly fee collection goal</p>
                
                <div class="progress-stats">
                    <div class="progress-stat">
                        <span class="progress-stat-label">Collected</span>
                        <span class="progress-stat-value" id="progress-collected">₹0</span>
                    </div>
                    <div class="progress-stat">
                        <span class="progress-stat-label">Expected</span>
                        <span class="progress-stat-value" id="progress-expected">₹0</span>
                    </div>
                    <div class="progress-stat">
                        <span class="progress-stat-label">Remaining</span>
                        <span class="progress-stat-value" id="progress-remaining">₹0</span>
                    </div>
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar" id="collection-progress-bar">
                        <div class="progress-bar-fill" id="collection-progress-fill"></div>
                    </div>
                    <div class="progress-percentage" id="collection-percentage">0%</div>
                </div>
            </div>

            <!-- Pie Chart -->
            <div class="feeflow-card">
                <h3>Payment Status Distribution</h3>
                <p class="card-description">Visual breakdown of paid vs unpaid students this month</p>
                
                <div class="chart-container">
                    <canvas id="paymentPieChart"></canvas>
                </div>

                <div class="chart-legend">
                    <div class="legend-item">
                        <span class="legend-color legend-color-paid"></span>
                        <span class="legend-label">Paid (<span id="legend-paid-count">0</span>)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-color legend-color-unpaid"></span>
                        <span class="legend-label">Unpaid (<span id="legend-unpaid-count">0</span>)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="section-students" class="feeflow-section">
        <div class="feeflow-header">
            <h2>Student Management</h2>
            <div style="display: flex; gap: 10px;">
                <button id="export-students-btn" class="feeflow-btn feeflow-btn-success">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Download Students
                </button>
                <button id="add-student-btn" class="feeflow-btn feeflow-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add New Student
                </button>
            </div>
        </div>

        <div id="student-form-container" class="feeflow-card" style="display: none;">
            <h3 id="student-form-title">Add New Student</h3>
            <form id="student-form">
                <input type="hidden" id="edit-student-id">

                <div class="feeflow-form-row">
                    <div class="feeflow-form-group">
                        <label for="student-name">Student Name *</label>
                        <input type="text" id="student-name" class="feeflow-input" required>
                    </div>

                    <div class="feeflow-form-group">
                        <label for="parent-phone">Parent's Phone Number *</label>
                        <input type="tel" id="parent-phone" class="feeflow-input" required>
                    </div>
                </div>

                <div class="feeflow-form-row feeflow-form-row-three">
                    <div class="feeflow-form-group">
                        <label for="student-subjects">Subjects *</label>
                        <input type="text" id="student-subjects" class="feeflow-input" placeholder="e.g., Math, Physics, Chemistry" required>
                    </div>

                    <div class="feeflow-form-group">
                        <label for="student-syllabus">Syllabus *</label>
                        <select id="student-syllabus" class="feeflow-input" required>
                            <option value="">Select syllabus</option>
                            <option value="IGCSE">IGCSE</option>
                            <option value="CBSE">CBSE</option>
                            <option value="ICSE">ICSE</option>
                            <option value="SSC">SSC (State Board)</option>
                            <option value="Inter">Inter</option>
                            <option value="IB">IB (International Baccalaureate)</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="feeflow-form-group">
                        <label for="student-grade">Grade</label>
                        <select id="student-grade" class="feeflow-input">
                            <option value="">Select grade</option>
                        </select>
                    </div>
                </div>

                <div class="feeflow-form-row">
                    <div class="feeflow-form-group" style="flex: 1;">
                        <label for="student-fee">Monthly Fee Amount (₹) *</label>
                        <input type="number" id="student-fee" class="feeflow-input" step="0.01" min="0" required style="width: 100%; min-width: 200px;">
                    </div>
                </div>

                <div class="feeflow-form-actions">
                    <button type="button" id="cancel-student-form" class="feeflow-btn feeflow-btn-secondary">Cancel</button>
                    <button type="submit" class="feeflow-btn feeflow-btn-primary">Save Student</button>
                </div>
            </form>
        </div>

        <div class="feeflow-card">
            <div class="feeflow-table-controls">
                <input type="text" id="students-search" class="feeflow-input" placeholder="Search students..." aria-label="Search students">
                <select id="students-filter" class="feeflow-input" aria-label="Filter students by status">
                    <option value="all">All Students</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>

            <div class="feeflow-table-container">
                <table class="feeflow-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Send Reminder</th>
                            <th>Last Payment</th>
                            <th>Phone</th>
                            <th>Subjects</th>
                            <th>Syllabus</th>
                            <th>Grade</th>
                            <th>Monthly Fee</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="students-table-body">
                        <tr>
                            <td colspan="10" class="text-center">Loading students...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="section-reports" class="feeflow-section">
        <div class="feeflow-header">
            <h2>Reports</h2>
            <p class="feeflow-subtitle">Generate and export fee reports</p>
        </div>

        <div class="feeflow-card">
            <h3>Transaction Report</h3>
            <p class="card-description">View all transactions within a date range</p>

            <div class="feeflow-form-row">
                <div class="feeflow-form-group">
                    <label for="report-from-date">From Date</label>
                    <input type="date" id="report-from-date" class="feeflow-input feeflow-datepicker">
                </div>
                <div class="feeflow-form-group">
                    <label for="report-to-date">To Date</label>
                    <input type="date" id="report-to-date" class="feeflow-input feeflow-datepicker">
                </div>
                <div class="feeflow-form-group">
                    <button id="generate-transaction-report" class="feeflow-btn feeflow-btn-primary" style="margin-top: 24px;">Generate Report</button>
                </div>
            </div>
        </div>

        <div id="transaction-report-results" class="feeflow-card" style="display: none;">
            <div class="report-header">
                <h3 id="transaction-report-title">Report Results</h3>
                <button id="export-transaction-excel" class="feeflow-btn feeflow-btn-success">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Download Excel
                </button>
            </div>

            <div class="feeflow-table-controls">
                <input type="text" id="transaction-report-search" class="feeflow-input" placeholder="Search in report..." aria-label="Search in report">
            </div>

            <div class="feeflow-table-container">
                <table class="feeflow-table">
                    <thead id="transaction-report-table-head">
                    </thead>
                    <tbody id="transaction-report-table-body">
                    </tbody>
                </table>
            </div>
        </div>

        <div class="feeflow-card">
            <h3>Fee Status Reports</h3>
            <p class="card-description">View students who paid or didn't pay for a specific month</p>

            <div class="feeflow-form-row">
                <div class="feeflow-form-group">
                    <label for="report-month">Month & Year</label>
                    <input type="month" id="report-month" class="feeflow-input feeflow-monthpicker">
                </div>
                <div class="feeflow-form-group">
                    <label for="report-fee-type">Report Type</label>
                    <select id="report-fee-type" class="feeflow-input">
                        <option value="paid" selected>Paid Fees</option>
                        <option value="unpaid">Not Paid Fees</option>
                    </select>
                </div>
                <div class="feeflow-form-group">
                    <button id="generate-fee-report" class="feeflow-btn feeflow-btn-primary" style="margin-top: 24px;">Generate Report</button>
                </div>
            </div>
        </div>

        <div id="fee-status-report-results" class="feeflow-card" style="display: none;">
            <div class="report-header">
                <h3 id="fee-status-report-title">Report Results</h3>
                <button id="export-fee-status-excel" class="feeflow-btn feeflow-btn-success">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Download Excel
                </button>
            </div>

            <div class="feeflow-table-controls">
                <input type="text" id="fee-status-report-search" class="feeflow-input" placeholder="Search in report..." aria-label="Search in report">
            </div>

            <div class="feeflow-table-container">
                <table class="feeflow-table">
                    <thead id="fee-status-report-table-head">
                    </thead>
                    <tbody id="fee-status-report-table-body">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="section-trash" class="feeflow-section">
        <div class="feeflow-header">
            <h2>Trash</h2>
            <p class="feeflow-subtitle">Deleted items are stored here. Restore or permanently delete them.</p>
        </div>

        <div class="feeflow-card">
            <h3>Deleted Students</h3>
            <div class="feeflow-table-container">
                <table class="feeflow-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Monthly Fee</th>
                            <th>Deleted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="trash-students-body">
                        <tr>
                            <td colspan="5" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="feeflow-card">
            <h3>Deleted Transactions</h3>
            <div class="feeflow-table-container">
                <table class="feeflow-table">
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Student</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Deleted Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="trash-transactions-body">
                        <tr>
                            <td colspan="6" class="text-center">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="section-settings" class="feeflow-section">
        <div class="feeflow-header">
            <h2>Settings</h2>
            <p class="feeflow-subtitle">Configure institution details for receipts</p>
        </div>

        <div class="feeflow-card">
            <h3>Institution Details</h3>
            <p class="card-description">This information will appear on all fee receipts</p>

            <form id="settings-form">
                <div class="feeflow-form-group">
                    <label for="institution-name">Institution Name *</label>
                    <input type="text" id="institution-name" class="feeflow-input" placeholder="e.g., Bright Future Academy" required>
                </div>

                <div class="feeflow-form-group">
                    <label for="institution-address">Institution Address *</label>
                    <textarea id="institution-address" class="feeflow-input" rows="3" placeholder="Enter complete address" required></textarea>
                </div>

                <div class="feeflow-form-group">
                    <label for="institution-phone">Contact Phone (Optional)</label>
                    <input type="tel" id="institution-phone" class="feeflow-input" placeholder="e.g., +91 9876543210">
                </div>

                <div class="feeflow-form-group">
                    <label for="institution-email">Contact Email (Optional)</label>
                    <input type="email" id="institution-email" class="feeflow-input" placeholder="e.g., info@institution.com">
                </div>

                <div class="feeflow-form-actions">
                    <button type="submit" class="feeflow-btn feeflow-btn-primary">Save Settings</button>
                </div>
            </form>
        </div>

        <div class="feeflow-card">
            <h3>Receipt Settings</h3>
            <p class="card-description">Configure receipt preferences</p>

            <div class="feeflow-form-group">
                <label>
                    <input type="checkbox" id="auto-download-receipt" checked>
                    <span style="margin-left: 8px;">Auto-download receipt as PDF after transaction</span>
                </label>
            </div>

            <div class="feeflow-form-group">
                <label>
                    <input type="checkbox" id="open-receipt-tab" checked>
                    <span style="margin-left: 8px;">Open receipt in new tab</span>
                </label>
            </div>
        </div>

        <div class="feeflow-card">
            <h3>WhatsApp Reminder Settings</h3>
            <p class="card-description">Customize the fee reminder message sent to parents via WhatsApp</p>

            <div class="feeflow-form-group">
                <label for="whatsapp-reminder-message">Reminder Message Template</label>
                <textarea id="whatsapp-reminder-message" class="feeflow-input" rows="5" placeholder="Enter your custom reminder message...">Dear Parent,

This is a gentle reminder that the tuition fee payment is due.

Please make the payment at your earliest convenience.

Thank you!</textarea>
                <small style="color: var(--text-muted); display: block; margin-top: 5px;">
                    You can use {student_name} to automatically insert the student's name in the message.
                </small>
            </div>

            <div class="feeflow-form-actions">
                <button type="button" id="save-whatsapp-message" class="feeflow-btn feeflow-btn-primary">Save Message Template</button>
            </div>
        </div>
    </div>

    <div id="feeflow-toast" class="feeflow-toast"></div>

    <!-- Edit Student Modal -->
    <div id="edit-student-modal" class="feeflow-modal">
        <div class="feeflow-modal-content">
            <div class="feeflow-modal-header">
                <h3>Edit Student</h3>
                <button class="feeflow-modal-close">&times;</button>
            </div>
            <div class="feeflow-modal-body">
                <form id="edit-student-form">
                    <input type="hidden" id="modal-student-id">
                    <input type="hidden" id="modal-student-status">

                    <div class="feeflow-form-row">
                        <div class="feeflow-form-group">
                            <label for="modal-student-name">Student Name *</label>
                            <input type="text" id="modal-student-name" class="feeflow-input" required>
                        </div>

                        <div class="feeflow-form-group">
                            <label for="modal-parent-phone">Parent's Phone Number *</label>
                            <input type="tel" id="modal-parent-phone" class="feeflow-input" required>
                        </div>
                    </div>

                    <div class="feeflow-form-row feeflow-form-row-three">
                        <div class="feeflow-form-group">
                            <label for="modal-student-subjects">Subjects *</label>
                            <input type="text" id="modal-student-subjects" class="feeflow-input" required>
                        </div>

                        <div class="feeflow-form-group">
                            <label for="modal-student-syllabus">Syllabus *</label>
                            <select id="modal-student-syllabus" class="feeflow-input" required>
                                <option value="">Select syllabus</option>
                                <option value="IGCSE">IGCSE</option>
                                <option value="CBSE">CBSE</option>
                                <option value="ICSE">ICSE</option>
                                <option value="SSC">SSC (State Board)</option>
                                <option value="Inter">Inter</option>
                                <option value="IB">IB (International Baccalaureate)</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="feeflow-form-group">
                            <label for="modal-student-grade">Grade</label>
                            <select id="modal-student-grade" class="feeflow-input">
                                <option value="">Select grade</option>
                            </select>
                        </div>
                    </div>

                    <div class="feeflow-form-row">
                        <div class="feeflow-form-group" style="flex: 1;">
                            <label for="modal-student-fee">Monthly Fee Amount (₹) *</label>
                            <input type="number" id="modal-student-fee" class="feeflow-input" step="0.01" min="0" required style="width: 100%; min-width: 200px;">
                        </div>
                    </div>

                    <div class="feeflow-form-actions">
                        <button type="button" class="feeflow-btn feeflow-btn-secondary feeflow-modal-close">Cancel</button>
                        <button type="button" id="modal-toggle-status" class="feeflow-btn feeflow-btn-warning">Deactivate</button>
                        <button type="button" id="modal-delete-student" class="feeflow-btn feeflow-btn-danger">Delete Student</button>
                        <button type="submit" class="feeflow-btn feeflow-btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>