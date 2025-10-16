/**
 * FeeFlow JavaScript
 * Save this file as: assets/js/feeflow.js
 */

jQuery(document).ready(function($) {
    
    // Grade options based on syllabus
    const gradeOptions = {
        'IGCSE': [
            'A-Level',
            'AS-Level',
            'A & AS Level',
            '6th Class',
            '7th Class',
            '8th Class',
            '9th Class',
            '10th Class'
        ],
        'CBSE': [
            'Class 1',
            'Class 2',
            'Class 3',
            'Class 4',
            'Class 5',
            'Class 6',
            'Class 7',
            'Class 8',
            'Class 9',
            'Class 10'
        ],
        'ICSE': [
            'Class 1',
            'Class 2',
            'Class 3',
            'Class 4',
            'Class 5',
            'Class 6',
            'Class 7',
            'Class 8',
            'Class 9',
            'Class 10'
        ],
        'SSC': [
            'Class 1',
            'Class 2',
            'Class 3',
            'Class 4',
            'Class 5',
            'Class 6',
            'Class 7',
            'Class 8',
            'Class 9',
            'Class 10'
        ],
        'Inter': [
            '1st Year',
            '2nd Year'
        ],
        'IB': [
            'Grade 1',
            'Grade 2',
            'Grade 3',
            'Grade 4',
            'Grade 5',
            'Grade 6',
            'Grade 7',
            'Grade 8',
            'Grade 9',
            'Grade 10',
            'Grade 11',
            'Grade 12'
        ],
        'Other': [
            'Not Applicable'
        ]
    };

    // Function to update grade options based on syllabus
    function updateGradeOptions(syllabus, selectedGrade, isModal) {
        selectedGrade = selectedGrade || '';
        isModal = isModal || false;
        
        const gradeSelect = isModal ? $('#modal-student-grade') : $('#student-grade');
        gradeSelect.empty();
        gradeSelect.append('<option value="">Select grade</option>');
        
        if (syllabus && gradeOptions[syllabus]) {
            gradeOptions[syllabus].forEach(function(grade) {
                const selected = grade === selectedGrade ? 'selected' : '';
                gradeSelect.append('<option value="' + grade + '" ' + selected + '>' + grade + '</option>');
            });
        }
    }

    // Initialize grade options on page load
    updateGradeOptions('IGCSE', '', false);

    // Syllabus change handler for add form
    $('#student-syllabus').on('change', function() {
        const selectedSyllabus = $(this).val();
        updateGradeOptions(selectedSyllabus, '', false);
    });

    // Syllabus change handler for modal edit form
    $('#modal-student-syllabus').on('change', function() {
        const selectedSyllabus = $(this).val();
        updateGradeOptions(selectedSyllabus, '', true);
    });
    
    // Navigation
    $('.feeflow-nav-btn').on('click', function() {
        const section = $(this).data('section');
        
        // Update navigation
        $('.feeflow-nav-btn').removeClass('active');
        $(this).addClass('active');
        
        // Update sections
        $('.feeflow-section').removeClass('active');
        $('#section-' + section).addClass('active');
        
        // Load data based on section
        if (section === 'students') {
            loadStudents();
        } else if (section === 'trash') {
            loadTrash();
        } else if (section === 'settings') {
            loadSettings();
        }
    });
    
    // Student Search for Transactions
    let searchTimeout;
    $('#student-search').on('input', function() {
        const search = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (search.length < 2) {
            $('#student-results').removeClass('active').empty();
            return;
        }
        
        searchTimeout = setTimeout(function() {
            searchStudent(search);
        }, 300);
    });
    
    function searchStudent(search) {
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_search_student',
                nonce: feeflowAjax.nonce,
                search: search
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    displaySearchResults(response.data);
                } else {
                    $('#student-results').html('<div style="padding: 15px; text-align: center; color: #6c757d;">No students found</div>').addClass('active');
                }
            },
            error: function() {
                showToast('Error searching students', 'error');
            }
        });
    }
    
    function displaySearchResults(students) {
        let html = '';
        students.forEach(function(student) {
            html += '<div class="student-result-item" data-student-id="' + student.id + '">';
            html += '<strong>' + escapeHtml(student.student_name) + '</strong>';
            html += '<span>' + escapeHtml(student.parent_phone) + ' • ' + escapeHtml(student.subjects) + ' • ₹' + student.monthly_fee + '</span>';
            html += '</div>';
        });
        $('#student-results').html(html).addClass('active');
    }
    
    // Select student from search results
    $(document).on('click', '.student-result-item', function() {
        const studentId = $(this).data('student-id');
        loadStudentDetails(studentId);
        $('#student-results').removeClass('active').empty();
        $('#student-search').val($(this).find('strong').text());
    });
    
    // Load student details
    function loadStudentDetails(studentId) {
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_get_student_details',
                nonce: feeflowAjax.nonce,
                student_id: studentId
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    const student = response.data;
                    
                    $('#selected-student-id').val(student.id);
                    $('#display-student-name').text(student.student_name);
                    $('#display-parent-phone').text(student.parent_phone);
                    $('#display-subjects').text(student.subjects);
                    $('#display-monthly-fee').text(student.monthly_fee);
                    
                    if (student.last_payment) {
                        const lastPaymentDate = new Date(student.last_payment.payment_date).toLocaleDateString('en-GB');
                        $('#display-last-payment').text('₹' + student.last_payment.amount + ' on ' + lastPaymentDate);
                    } else {
                        $('#display-last-payment').text('No previous payment');
                    }
                    
                    $('#transaction-amount').val(student.monthly_fee);
                    
                    // Set current month
                    const today = new Date();
                    const currentMonth = today.toISOString().substr(0, 7);
                    $('#transaction-month').val(currentMonth);
                    
                    // Set today's date
                    $('#transaction-date').val(today.toISOString().substr(0, 10));
                    
                    $('#transaction-form').slideDown();
                } else {
                    showToast(response.data || 'Failed to load student details', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error loading student details', 'error');
            }
        });
    }
    
    // Cancel transaction
    $('#cancel-transaction').on('click', function() {
        $('#transaction-form').slideUp();
        $('#student-search').val('');
        resetTransactionForm();
    });
    
    // Submit transaction
    $('#submit-transaction').on('click', function() {
        if (!validateTransactionForm()) {
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_add_transaction',
                nonce: feeflowAjax.nonce,
                student_id: $('#selected-student-id').val(),
                amount: $('#transaction-amount').val(),
                payment_month: $('#transaction-month').val(),
                payment_method: $('#transaction-method').val(),
                payment_date: $('#transaction-date').val(),
                notes: $('#transaction-notes').val()
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast('Transaction added successfully! Receipt #' + response.data.receipt_number, 'success');
                    loadStudents();
                    loadDashboardData();
                    
                    // Open receipt in new tab
                    if (response.data.pdf_url) {
                        window.open(response.data.pdf_url, '_blank');
                    }
                    
                    // Reset form
                    $('#transaction-form').slideUp();
                    $('#student-search').val('');
                    resetTransactionForm();
                    
                    // Reload dashboard data
                    loadDashboardData();
                } else {
                    showToast(response.data || 'Failed to add transaction', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error adding transaction', 'error');
            }
        });
    });
    
    function validateTransactionForm() {
        const amount = $('#transaction-amount').val();
        const month = $('#transaction-month').val();
        const date = $('#transaction-date').val();
        
        if (!amount || amount <= 0) {
            showToast('Please enter a valid amount', 'warning');
            return false;
        }
        
        if (!month) {
            showToast('Please select payment month', 'warning');
            return false;
        }
        
        if (!date) {
            showToast('Please select payment date', 'warning');
            return false;
        }
        
        return true;
    }
    
    function resetTransactionForm() {
        $('#selected-student-id').val('');
        $('#transaction-amount').val('');
        $('#transaction-month').val('');
        $('#transaction-date').val('');
        $('#transaction-notes').val('');
        $('#transaction-method').val('Cash');
    }
    
    // Student Management
    $('#add-student-btn').on('click', function() {
        $('#student-form-title').text('Add New Student');
        $('#edit-student-id').val('');
        $('#student-form')[0].reset();
        $('#student-syllabus').val('IGCSE');
        updateGradeOptions('IGCSE', '', false);
        $('#student-form-container').slideDown();
    });
    
    $('#cancel-student-form').on('click', function() {
        $('#student-form-container').slideUp();
        $('#student-form')[0].reset();
    });
    
    // Submit student form
    $('#student-form').on('submit', function(e) {
        e.preventDefault();
        
        const studentId = $('#edit-student-id').val();
        const action = studentId ? 'feeflow_update_student' : 'feeflow_add_student';
        
        showLoader();
        
        const formData = {
            action: action,
            nonce: feeflowAjax.nonce,
            student_name: $('#student-name').val(),
            parent_phone: $('#parent-phone').val(),
            subjects: $('#student-subjects').val(),
            syllabus: $('#student-syllabus').val(),
            grade: $('#student-grade').val(),
            monthly_fee: $('#student-fee').val()
        };
        
        if (studentId) {
            formData.student_id = studentId;
        }
        
        console.log('Submitting student data:', formData);
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoader();
                console.log('Server response:', response);
                
                if (response.success) {
                    showToast(studentId ? 'Student updated successfully' : 'Student added successfully', 'success');
                    $('#student-form-container').slideUp();
                    $('#student-form')[0].reset();
                    loadStudents();
                    loadDashboardData();
                } else {
                    showToast(response.data || 'Failed to save student', 'error');
                }
            },
            error: function(xhr, status, error) {
                hideLoader();
                console.error('AJAX error:', error);
                console.error('Response:', xhr.responseText);
                showToast('Error saving student: ' + error, 'error');
            }
        });
    });
    
    // Load students
    function loadStudents() {
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_get_students',
                nonce: feeflowAjax.nonce
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    displayStudents(response.data);
                    allStudents = response.data;
                } else {
                    $('#students-table-body').html('<tr><td colspan="10" class="text-center">Error loading students</td></tr>');
                }
            },
            error: function() {
                hideLoader();
                $('#students-table-body').html('<tr><td colspan="10" class="text-center">Error loading students</td></tr>');
            }
        });
    }
    
    let allStudents = [];
    
    function displayStudents(students) {
        if (students.length === 0) {
            $('#students-table-body').html('<tr><td colspan="10" class="text-center">No students found</td></tr>');
            return;
        }
        
        let html = '';
        students.forEach(function(student) {
            const statusClass = student.status === 'active' ? 'status-active' : 'status-inactive';
            const lastPayment = student.last_payment_date !== 'Never' 
                ? new Date(student.last_payment_date).toLocaleDateString('en-GB')
                : 'Never';
            
            const cleanPhone = student.parent_phone.replace(/\D/g, '');
            
            html += '<tr>';
            html += '<td>' + escapeHtml(student.student_name) + '</td>';
            html += '<td>';
            html += '<a href="#" class="whatsapp-reminder-btn" data-student-name="' + escapeHtml(student.student_name) + '" data-phone="' + escapeHtml(cleanPhone) + '" title="Send WhatsApp reminder to ' + escapeHtml(student.parent_phone) + '">';
            html += '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">';
            html += '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>';
            html += '</svg>';
            html += '<span>Send Reminder</span>';
            html += '</a>';
            html += '</td>';
            html += '<td>' + lastPayment + '</td>';
            html += '<td>' + escapeHtml(student.parent_phone) + '</td>';
            html += '<td>' + escapeHtml(student.subjects) + '</td>';
            html += '<td>' + escapeHtml(student.syllabus) + '</td>';
            html += '<td>' + escapeHtml(student.grade || 'N/A') + '</td>';
            html += '<td>₹' + student.monthly_fee + '</td>';
            html += '<td><span class="status-badge ' + statusClass + '">' + student.status + '</span></td>';
            html += '<td>';
            html += '<div class="action-buttons">';
            html += '<button class="feeflow-btn feeflow-btn-sm feeflow-btn-primary edit-student" data-id="' + student.id + '">Edit</button>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        });
        
        $('#students-table-body').html(html);
    }
    
    // WhatsApp Reminder Click Handler
    $(document).on('click', '.whatsapp-reminder-btn', function(e) {
        e.preventDefault();
        
        const studentName = $(this).data('student-name');
        const phone = $(this).data('phone');
        
        let message = localStorage.getItem('feeflow_whatsapp_message') || 
            'Dear Parent,\n\nThis is a gentle reminder that the tuition fee payment for ' + studentName + ' is due.\n\nPlease make the payment at your earliest convenience.\n\nThank you!';
        
        message = message.replace(/{student_name}/g, studentName);
        const encodedMessage = encodeURIComponent(message);
        const whatsappUrl = 'https://wa.me/91' + phone + '?text=' + encodedMessage;
        
        window.open(whatsappUrl, '_blank');
    });
    
    // Edit student - Open Modal
    $(document).on('click', '.edit-student', function() {
        const studentId = $(this).data('id');
        const student = allStudents.find(function(s) {
            return s.id == studentId;
        });
        
        if (student) {
            $('#modal-student-id').val(student.id);
            $('#modal-student-status').val(student.status);
            $('#modal-student-name').val(student.student_name);
            $('#modal-parent-phone').val(student.parent_phone);
            $('#modal-student-subjects').val(student.subjects);
            $('#modal-student-syllabus').val(student.syllabus);
            updateGradeOptions(student.syllabus, student.grade, true);
            $('#modal-student-fee').val(student.monthly_fee);
            
            // Update toggle button text
            const toggleBtn = $('#modal-toggle-status');
            if (student.status === 'active') {
                toggleBtn.text('Deactivate').removeClass('feeflow-btn-success').addClass('feeflow-btn-warning');
            } else {
                toggleBtn.text('Activate').removeClass('feeflow-btn-warning').addClass('feeflow-btn-success');
            }
            
            $('#edit-student-modal').fadeIn();
        }
    });
    
    // Modal close handlers
    $('.feeflow-modal-close').on('click', function() {
        $('#edit-student-modal').fadeOut();
    });
    
    $(window).on('click', function(e) {
        if ($(e.target).is('.feeflow-modal')) {
            $('.feeflow-modal').fadeOut();
        }
    });
    
    // Toggle status from modal
    $('#modal-toggle-status').on('click', function() {
        const studentId = $('#modal-student-id').val();
        const currentStatus = $('#modal-student-status').val();
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        if (!confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this student?')) {
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_toggle_student_status',
                nonce: feeflowAjax.nonce,
                student_id: studentId,
                status: newStatus
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast('Status updated successfully', 'success');
                    $('#edit-student-modal').fadeOut();
                    loadStudents();
                    loadDashboardData();
                } else {
                    showToast(response.data || 'Failed to update status', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error updating status', 'error');
            }
        });
    });
    
    // Submit edit student form from modal
    $('#edit-student-form').on('submit', function(e) {
        e.preventDefault();
        
        showLoader();
        
        const formData = {
            action: 'feeflow_update_student',
            nonce: feeflowAjax.nonce,
            student_id: $('#modal-student-id').val(),
            student_name: $('#modal-student-name').val(),
            parent_phone: $('#modal-parent-phone').val(),
            subjects: $('#modal-student-subjects').val(),
            syllabus: $('#modal-student-syllabus').val(),
            grade: $('#modal-student-grade').val(),
            monthly_fee: $('#modal-student-fee').val()
        };
        
        console.log('Submitting modal student data:', formData);
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                hideLoader();
                console.log('Server response:', response);
                
                if (response.success) {
                    showToast('Student updated successfully', 'success');
                    $('#edit-student-modal').fadeOut();
                    loadStudents();
                } else {
                    showToast(response.data || 'Failed to update student', 'error');
                }
            },
            error: function(xhr, status, error) {
                hideLoader();
                console.error('AJAX error:', error);
                console.error('Response:', xhr.responseText);
                showToast('Error updating student: ' + error, 'error');
            }
        });
    });
    
    // Delete student from modal
    $('#modal-delete-student').on('click', function() {
        const studentId = $('#modal-student-id').val();
        
        if (!confirm('Are you sure you want to delete this student? This will move the student to trash.')) {
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_delete_student',
                nonce: feeflowAjax.nonce,
                student_id: studentId
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast('Student moved to trash', 'success');
                    $('#edit-student-modal').fadeOut();
                    loadStudents();
                } else {
                    showToast(response.data || 'Failed to delete student', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error deleting student', 'error');
            }
        });
    });
    
    // Student search and filter
    $('#students-search').on('input', function() {
        filterStudents();
    });
    
    $('#students-filter').on('change', function() {
        filterStudents();
    });
    
    function filterStudents() {
        const searchTerm = $('#students-search').val().toLowerCase();
        const filterStatus = $('#students-filter').val();
        
        let filtered = allStudents.filter(function(student) {
            const matchesSearch = student.student_name.toLowerCase().includes(searchTerm) ||
                                student.parent_phone.includes(searchTerm) ||
                                student.subjects.toLowerCase().includes(searchTerm);
            
            const matchesFilter = filterStatus === 'all' || student.status === filterStatus;
            
            return matchesSearch && matchesFilter;
        });
        
        displayStudents(filtered);
    }
    
    // Export students
    $('#export-students-btn').on('click', function() {
        window.location.href = feeflowAjax.ajaxurl + '?action=feeflow_export_students&nonce=' + feeflowAjax.nonce;
    });
    
    // Reports - Transaction Report
    $('#generate-transaction-report').on('click', function() {
        const fromDate = $('#report-from-date').val();
        const toDate = $('#report-to-date').val();
        
        if (!fromDate || !toDate) {
            showToast('Please select both from and to dates', 'warning');
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_generate_report',
                nonce: feeflowAjax.nonce,
                report_type: 'transaction_range',
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    displayTransactionReport(response.data);
                } else {
                    showToast('Failed to generate report', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error generating report', 'error');
            }
        });
    });
    
    // Reports - Fee Status Report
    $('#generate-fee-report').on('click', function() {
        const month = $('#report-month').val();
        const status = $('#report-fee-type').val();
        
        if (!month) {
            showToast('Please select a month', 'warning');
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_generate_report',
                nonce: feeflowAjax.nonce,
                report_type: 'fee_status',
                month: month,
                status: status
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    displayFeeStatusReport(response.data);
                } else {
                    showToast('Failed to generate report', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error generating report', 'error');
            }
        });
    });
    
    let currentTransactionReportData = {};
    let currentFeeStatusReportData = {};
    
    function displayTransactionReport(reportData) {
        currentTransactionReportData = {
            type: 'transaction_range',
            from_date: $('#report-from-date').val(),
            to_date: $('#report-to-date').val()
        };
        
        const data = reportData.data;
        
        if (data.length === 0) {
            $('#transaction-report-results').slideUp();
            showToast('No transactions found for the selected date range', 'warning');
            return;
        }
        
        let total = 0;
        data.forEach(function(t) {
            total += parseFloat(t.amount);
        });
        
        $('#transaction-report-title').text('Transaction Report (' + data.length + ' transactions, Total: ₹' + total.toFixed(2) + ')');
        
        let headHtml = '<tr>';
        headHtml += '<th>Receipt #</th>';
        headHtml += '<th>Date</th>';
        headHtml += '<th>Student</th>';
        headHtml += '<th>Phone</th>';
        headHtml += '<th>Amount</th>';
        headHtml += '<th>Method</th>';
        headHtml += '<th>Month</th>';
        headHtml += '<th>Actions</th>';
        headHtml += '</tr>';
        $('#transaction-report-table-head').html(headHtml);
        
        let bodyHtml = '';
        data.forEach(function(row) {
            const date = new Date(row.payment_date).toLocaleDateString('en-GB');
            const month = new Date(row.payment_month).toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
            
            bodyHtml += '<tr>';
            bodyHtml += '<td>' + escapeHtml(row.receipt_number || row.id) + '</td>';
            bodyHtml += '<td>' + date + '</td>';
            bodyHtml += '<td>' + escapeHtml(row.student_name) + '</td>';
            bodyHtml += '<td>' + escapeHtml(row.parent_phone) + '</td>';
            bodyHtml += '<td>₹' + row.amount + '</td>';
            bodyHtml += '<td>' + escapeHtml(row.payment_method) + '</td>';
            bodyHtml += '<td>' + month + '</td>';
            bodyHtml += '<td>';
            bodyHtml += '<button class="feeflow-btn feeflow-btn-sm feeflow-btn-danger delete-transaction" data-id="' + row.id + '">Delete</button>';
            bodyHtml += '</td>';
            bodyHtml += '</tr>';
        });
        
        $('#transaction-report-table-body').html(bodyHtml);
        $('#transaction-report-results').slideDown();
        allTransactionReportData = data;
    }
    
    function displayFeeStatusReport(reportData) {
        currentFeeStatusReportData = {
            type: 'fee_status',
            month: $('#report-month').val(),
            status: $('#report-fee-type').val()
        };
        
        const data = reportData.data;
        const status = reportData.status;
        
        if (data.length === 0) {
            $('#fee-status-report-results').slideUp();
            showToast('No students found who ' + (status === 'paid' ? 'paid' : 'haven\'t paid') + ' for this month', 'warning');
            return;
        }
        
        const monthName = new Date($('#report-month').val() + '-01').toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
        $('#fee-status-report-title').text((status === 'paid' ? 'Paid' : 'Unpaid') + ' Fees Report for ' + monthName + ' (' + data.length + ' students)');
        
        let headHtml = '<tr>';
        headHtml += '<th>Student Name</th>';
        headHtml += '<th>Phone</th>';
        headHtml += '<th>Subjects</th>';
        headHtml += '<th>Monthly Fee</th>';
        headHtml += '<th>Last Payment</th>';
        
        if (status === 'paid') {
            headHtml += '<th>Receipt #</th>';
            headHtml += '<th>Amount Paid</th>';
            headHtml += '<th>Payment Method</th>';
            headHtml += '<th>Actions</th>';
        } else {
            headHtml += '<th>Send Reminder</th>';
        }
        
        headHtml += '</tr>';
        $('#fee-status-report-table-head').html(headHtml);
        
        let bodyHtml = '';
        data.forEach(function(row) {
            const lastPayment = row.last_payment_date 
                ? new Date(row.last_payment_date).toLocaleDateString('en-GB')
                : 'Never';
            
            const cleanPhone = row.parent_phone.replace(/\D/g, '');
            
            bodyHtml += '<tr>';
            bodyHtml += '<td>' + escapeHtml(row.student_name) + '</td>';
            bodyHtml += '<td>' + escapeHtml(row.parent_phone) + '</td>';
            bodyHtml += '<td>' + escapeHtml(row.subjects) + '</td>';
            bodyHtml += '<td>₹' + row.monthly_fee + '</td>';
            bodyHtml += '<td>' + lastPayment + '</td>';
            
            if (status === 'paid') {
                bodyHtml += '<td>' + escapeHtml(row.receipt_number || '-') + '</td>';
                bodyHtml += '<td>₹' + row.amount + '</td>';
                bodyHtml += '<td>' + escapeHtml(row.payment_method) + '</td>';
                bodyHtml += '<td>';
                bodyHtml += '<button class="feeflow-btn feeflow-btn-sm feeflow-btn-primary download-receipt" data-receipt="' + escapeHtml(row.receipt_number) + '" title="Download Receipt">';
                bodyHtml += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
                bodyHtml += '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>';
                bodyHtml += '<polyline points="7 10 12 15 17 10"></polyline>';
                bodyHtml += '<line x1="12" y1="15" x2="12" y2="3"></line>';
                bodyHtml += '</svg>';
                bodyHtml += '</button>';
                bodyHtml += '</td>';
            } else {
                bodyHtml += '<td>';
                bodyHtml += '<a href="#" class="whatsapp-reminder-btn" data-student-name="' + escapeHtml(row.student_name) + '" data-phone="' + escapeHtml(cleanPhone) + '">';
                bodyHtml += '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">';
                bodyHtml += '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>';
                bodyHtml += '</svg>';
                bodyHtml += '<span>Send Reminder</span>';
                bodyHtml += '</a>';
                bodyHtml += '</td>';
            }
            
            bodyHtml += '</tr>';
        });
        
        $('#fee-status-report-table-body').html(bodyHtml);
        $('#fee-status-report-results').slideDown();
        allFeeStatusReportData = data;
    }
    
    let allTransactionReportData = [];
    let allFeeStatusReportData = [];
    
    // Download receipt button handler
    $(document).on('click', '.download-receipt', function() {
        const receiptNumber = $(this).data('receipt');
        const receiptUrl = feeflowAjax.ajaxurl.replace('/admin-ajax.php', '') + '/wp-content/uploads/feeflow-receipts/receipt_' + receiptNumber + '.html';
        window.open(receiptUrl, '_blank');
    });
    
    // Delete transaction
    $(document).on('click', '.delete-transaction', function() {
        const transactionId = $(this).data('id');
        
        if (!confirm('Are you sure you want to delete this transaction?')) {
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_delete_transaction',
                nonce: feeflowAjax.nonce,
                transaction_id: transactionId
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast('Transaction deleted successfully', 'success');
                    $('#generate-transaction-report').click();
                } else {
                    showToast(response.data || 'Failed to delete transaction', 'error');
                    loadDashboardData();
                }
            },
            error: function() {
                hideLoader();
                showToast('Error deleting transaction', 'error');
            }
        });
    });
    
    // Export Transaction Report to Excel
    $('#export-transaction-excel').on('click', function() {
        const params = new URLSearchParams({
            action: 'feeflow_export_excel',
            nonce: feeflowAjax.nonce,
            report_type: currentTransactionReportData.type,
            from_date: currentTransactionReportData.from_date,
            to_date: currentTransactionReportData.to_date
        });
        
        window.location.href = feeflowAjax.ajaxurl + '?' + params.toString();
    });
    
    // Export Fee Status Report to Excel
    $('#export-fee-status-excel').on('click', function() {
        const params = new URLSearchParams({
            action: 'feeflow_export_excel',
            nonce: feeflowAjax.nonce,
            report_type: currentFeeStatusReportData.type,
            month: currentFeeStatusReportData.month,
            status: currentFeeStatusReportData.status
        });
        
        window.location.href = feeflowAjax.ajaxurl + '?' + params.toString();
    });
    
    // Report search for transaction report
    $('#transaction-report-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('#transaction-report-table-body tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });
    
    // Report search for fee status report
    $('#fee-status-report-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('#fee-status-report-table-body tr').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });
    
    // Trash Management
    function loadTrash() {
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_get_trash',
                nonce: feeflowAjax.nonce
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    displayTrashStudents(response.data.students);
                    displayTrashTransactions(response.data.transactions);
                } else {
                    showToast('Error loading trash', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error loading trash', 'error');
            }
        });
    }
    
    function displayTrashStudents(students) {
        if (students.length === 0) {
            $('#trash-students-body').html('<tr><td colspan="5" class="text-center">No deleted students</td></tr>');
            return;
        }
        
        let html = '';
        students.forEach(function(student) {
            const deletedDate = new Date(student.updated_at).toLocaleDateString('en-GB');
            
            html += '<tr>';
            html += '<td>' + escapeHtml(student.student_name) + '</td>';
            html += '<td>' + escapeHtml(student.parent_phone) + '</td>';
            html += '<td>₹' + student.monthly_fee + '</td>';
            html += '<td>' + deletedDate + '</td>';
            html += '<td>';
            html += '<div class="action-buttons">';
            html += '<button class="feeflow-btn feeflow-btn-sm feeflow-btn-success restore-item" data-type="student" data-id="' + student.id + '">Restore</button>';
            html += '<button class="feeflow-btn feeflow-btn-sm feeflow-btn-danger permanent-delete" data-type="student" data-id="' + student.id + '">Delete Forever</button>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        });
        
        $('#trash-students-body').html(html);
    }
    
    function displayTrashTransactions(transactions) {
        if (transactions.length === 0) {
            $('#trash-transactions-body').html('<tr><td colspan="6" class="text-center">No deleted transactions</td></tr>');
            return;
        }
        
        let html = '';
        transactions.forEach(function(trans) {
            const paymentDate = new Date(trans.payment_date).toLocaleDateString('en-GB');
            const deletedDate = new Date(trans.created_at).toLocaleDateString('en-GB');
            
            html += '<tr>';
            html += '<td>' + escapeHtml(trans.receipt_number || trans.id) + '</td>';
            html += '<td>' + escapeHtml(trans.student_name || 'N/A') + '</td>';
            html += '<td>₹' + trans.amount + '</td>';
            html += '<td>' + paymentDate + '</td>';
            html += '<td>' + deletedDate + '</td>';
            html += '<td>';
            html += '<div class="action-buttons">';
            html += '<button class="feeflow-btn feeflow-btn-sm feeflow-btn-success restore-item" data-type="transaction" data-id="' + trans.id + '">Restore</button>';
            html += '<button class="feeflow-btn feeflow-btn-sm feeflow-btn-danger permanent-delete" data-type="transaction" data-id="' + trans.id + '">Delete Forever</button>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        });
        
        $('#trash-transactions-body').html(html);
    }
    
    // Restore item
    $(document).on('click', '.restore-item', function() {
        const itemType = $(this).data('type');
        const itemId = $(this).data('id');
        
        if (!confirm('Are you sure you want to restore this ' + itemType + '?')) {
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_restore_item',
                nonce: feeflowAjax.nonce,
                item_type: itemType,
                item_id: itemId
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast(response.data.message, 'success');
                    loadTrash();
                } else {
                    showToast(response.data || 'Failed to restore item', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error restoring item', 'error');
            }
        });
    });
    
    // Permanent delete
    $(document).on('click', '.permanent-delete', function() {
        const itemType = $(this).data('type');
        const itemId = $(this).data('id');
        
        if (!confirm('Are you sure you want to PERMANENTLY delete this ' + itemType + '? This action cannot be undone!')) {
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_permanent_delete',
                nonce: feeflowAjax.nonce,
                item_type: itemType,
                item_id: itemId
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast(response.data.message, 'success');
                    loadTrash();
                } else {
                    showToast(response.data || 'Failed to delete item', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error deleting item', 'error');
            }
        });
    });
    
    // Settings
    function loadSettings() {
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_get_settings',
                nonce: feeflowAjax.nonce
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    const settings = response.data;
                    $('#institution-name').val(settings.institution_name || '');
                    $('#institution-address').val(settings.institution_address || '');
                    $('#institution-phone').val(settings.institution_phone || '');
                    $('#institution-email').val(settings.institution_email || '');
                    
                    const savedMessage = localStorage.getItem('feeflow_whatsapp_message');
                    if (savedMessage) {
                        $('#whatsapp-reminder-message').val(savedMessage);
                    }
                }
            },
            error: function() {
                hideLoader();
                showToast('Error loading settings', 'error');
            }
        });
    }
    
    // Save settings
    $('#settings-form').on('submit', function(e) {
        e.preventDefault();
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_save_settings',
                nonce: feeflowAjax.nonce,
                institution_name: $('#institution-name').val(),
                institution_address: $('#institution-address').val(),
                institution_phone: $('#institution-phone').val(),
                institution_email: $('#institution-email').val()
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast('Settings saved successfully', 'success');
                } else {
                    showToast(response.data || 'Failed to save settings', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error saving settings', 'error');
            }
        });
    });
    
    // Save WhatsApp message template
    $('#save-whatsapp-message').on('click', function() {
        const message = $('#whatsapp-reminder-message').val();
        
        if (!message.trim()) {
            showToast('Please enter a reminder message', 'warning');
            return;
        }
        
        localStorage.setItem('feeflow_whatsapp_message', message);
        showToast('WhatsApp message template saved successfully', 'success');
    });
    
    // Upgrade Database
    $('#upgrade-database-btn').on('click', function() {
        if (!confirm('This will upgrade your database to add the Grade field. Continue?')) {
            return;
        }
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_upgrade_database',
                nonce: feeflowAjax.nonce
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    showToast(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    showToast(response.data || 'Failed to upgrade database', 'error');
                }
            },
            error: function() {
                hideLoader();
                showToast('Error upgrading database', 'error');
            }
        });
    });
    
    // Dashboard Functions
    let dashboardChart = null;
    
    function loadDashboardData() {
        const today = new Date();
        const currentMonth = today.toISOString().substr(0, 7);
        const monthName = today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        
        $('#dashboard-month-name').text(monthName);
        
        showLoader();
        
        $.ajax({
            url: feeflowAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'feeflow_get_dashboard_data',
                nonce: feeflowAjax.nonce,
                month: currentMonth
            },
            success: function(response) {
                hideLoader();
                
                if (response.success) {
                    updateDashboard(response.data);
                } else {
                    console.error('Failed to load dashboard data');
                }
            },
            error: function() {
                hideLoader();
                console.error('Error loading dashboard data');
            }
        });
    }
    
    function updateDashboard(data) {
        $('#stat-total-students').text(data.total_students);
        $('#stat-monthly-revenue').text('₹' + Number(data.monthly_revenue).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#stat-paid-students').text(data.paid_students);
        $('#stat-unpaid-students').text(data.unpaid_students);
        
        $('#progress-collected').text('₹' + Number(data.collected_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#progress-expected').text('₹' + Number(data.expected_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#progress-remaining').text('₹' + Number(data.remaining_amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        
        const percentage = data.expected_amount > 0 ? Math.min((data.collected_amount / data.expected_amount) * 100, 100) : 0;
        $('#collection-progress-fill').css('width', percentage + '%');
        $('#collection-percentage').text(Math.round(percentage) + '%');
        
        $('#legend-paid-count').text(data.paid_students);
        $('#legend-unpaid-count').text(data.unpaid_students);
        
        updatePieChart(data.paid_students, data.unpaid_students);
    }
    
    function updatePieChart(paid, unpaid) {
        const ctx = document.getElementById('paymentPieChart');
        
        if (!ctx) return;
        
        if (dashboardChart) {
            dashboardChart.destroy();
        }
        
        dashboardChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Paid', 'Unpaid'],
                datasets: [{
                    data: [paid, unpaid],
                    backgroundColor: ['#56ab2f', '#f5576c'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = paid + unpaid;
                                const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Utility Functions
    function showLoader() {
        $('#feeflow-loader').fadeIn();
    }
    
    function hideLoader() {
        $('#feeflow-loader').fadeOut();
    }
    
    function showToast(message, type) {
        type = type || 'success';
        const toast = $('#feeflow-toast');
        toast.removeClass('success error warning').addClass(type);
        toast.text(message);
        toast.addClass('show');
        
        setTimeout(function() {
            toast.removeClass('show');
        }, 3000);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text ? String(text).replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
    }
    
    // Click outside to close student results
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#student-search, #student-results').length) {
            $('#student-results').removeClass('active');
        }
    });
    
    // Initialize: Set default dates for reports
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#report-from-date').val(firstDay.toISOString().substr(0, 10));
    $('#report-to-date').val(today.toISOString().substr(0, 10));
    $('#report-month').val(today.toISOString().substr(0, 7));
    
    // Load students on page load
    loadStudents();
    
    // Load dashboard data on page load
    loadDashboardData();
});