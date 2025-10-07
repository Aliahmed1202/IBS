// Staff Controller - Manages staff operations (Admin only)
class StaffController {
    constructor() {
        this.currentStaff = [];
        this.filteredStaff = [];
        this.searchQuery = '';
        this.init();
    }

    // Initialize staff controller
    init() {
        this.bindEvents();
    }

    // Bind staff events
    bindEvents() {
        // Add staff button
        document.getElementById('add-staff-btn')?.addEventListener('click', () => {
            this.showStaffModal();
        });

        // Staff form submission
        document.getElementById('staff-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleStaffSubmit();
        });

        // Modal close events
        document.getElementById('close-staff-modal')?.addEventListener('click', () => {
            this.hideStaffModal();
        });

        document.getElementById('cancel-staff')?.addEventListener('click', () => {
            this.hideStaffModal();
        });

        // Search functionality
        document.getElementById('search-staff')?.addEventListener('input', 
            utils.debounce((e) => {
                this.searchQuery = e.target.value;
                this.filterStaff();
            }, 300)
        );

        // Staff actions (edit, delete, activate/deactivate)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-edit-staff')) {
                const staffId = parseInt(e.target.closest('.btn-edit-staff').dataset.staffId);
                this.editStaff(staffId);
            }
            
            if (e.target.closest('.btn-delete-staff')) {
                const staffId = parseInt(e.target.closest('.btn-delete-staff').dataset.staffId);
                this.deleteStaff(staffId);
            }

            if (e.target.closest('.btn-toggle-staff')) {
                const staffId = parseInt(e.target.closest('.btn-toggle-staff').dataset.staffId);
                this.toggleStaffStatus(staffId);
            }
        });

        // Modal click outside to close
        document.getElementById('staff-modal')?.addEventListener('click', (e) => {
            if (e.target.id === 'staff-modal') {
                this.hideStaffModal();
            }
        });
    }

    // Load staff data
    loadStaff() {
        try {
            this.currentStaff = Staff.getAll();
            this.filterStaff();
            this.updateStaffStats();
        } catch (error) {
            console.error('Error loading staff:', error);
            this.showError('Failed to load staff data');
        }
    }

    // Filter staff based on search
    filterStaff() {
        let filtered = [...this.currentStaff];

        // Apply search filter
        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            filtered = filtered.filter(staff => 
                staff.name.toLowerCase().includes(query) ||
                staff.username.toLowerCase().includes(query) ||
                staff.phone.includes(query) ||
                staff.email.toLowerCase().includes(query)
            );
        }

        this.filteredStaff = filtered;
        this.renderStaffTable();
    }

    // Render staff table
    renderStaffTable() {
        const tbody = document.getElementById('staff-tbody');
        if (!tbody) return;

        if (this.filteredStaff.length === 0) {
            tbody.innerHTML = `
                <tr class="no-data-row">
                    <td colspan="7">No staff members found</td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = this.filteredStaff.map(staff => `
            <tr>
                <td>${staff.getDisplayName()}</td>
                <td>${staff.username}</td>
                <td>${staff.phone}</td>
                <td>${staff.email || 'N/A'}</td>
                <td>
                    <span class="role-badge role-${staff.role}">${staff.role.charAt(0).toUpperCase() + staff.role.slice(1)}</span>
                </td>
                <td>
                    <span class="status-badge ${staff.isActive ? 'status-active' : 'status-inactive'}">
                        ${staff.getStatus()}
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="btn btn-secondary btn-sm btn-edit-staff" 
                                data-staff-id="${staff.id}" title="Edit Staff">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn ${staff.isActive ? 'btn-warning' : 'btn-success'} btn-sm btn-toggle-staff" 
                                data-staff-id="${staff.id}" title="${staff.isActive ? 'Deactivate' : 'Activate'} Staff">
                            <i class="fas fa-${staff.isActive ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="btn btn-danger btn-sm btn-delete-staff" 
                                data-staff-id="${staff.id}" title="Delete Staff">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    // Update staff statistics
    updateStaffStats() {
        const totalStaff = this.currentStaff.length;
        const activeStaff = this.currentStaff.filter(s => s.isActive).length;
        const adminCount = this.currentStaff.filter(s => s.role === 'admin').length;
        const staffCount = this.currentStaff.filter(s => s.role === 'staff').length;

        // Show staff summary
        this.showStaffSummary({
            totalStaff,
            activeStaff,
            adminCount,
            staffCount
        });
    }

    // Show staff summary
    showStaffSummary(stats) {
        const summaryContainer = document.querySelector('#staff-management .section-header');
        if (!summaryContainer) return;

        // Remove existing summary
        const existingSummary = summaryContainer.querySelector('.staff-summary');
        if (existingSummary) {
            existingSummary.remove();
        }

        // Create new summary
        const summaryDiv = document.createElement('div');
        summaryDiv.className = 'staff-summary';
        summaryDiv.innerHTML = `
            <div class="summary-stats">
                <span class="stat">Total: ${stats.totalStaff}</span>
                <span class="stat success">Active: ${stats.activeStaff}</span>
                <span class="stat info">Admins: ${stats.adminCount}</span>
                <span class="stat">Staff: ${stats.staffCount}</span>
            </div>
        `;

        summaryContainer.appendChild(summaryDiv);
    }

    // Show staff modal
    showStaffModal(staff = null) {
        const modal = document.getElementById('staff-modal');
        const title = document.getElementById('staff-modal-title');
        const form = document.getElementById('staff-form');

        if (staff) {
            title.textContent = 'Edit Staff Member';
            this.fillStaffForm(staff);
            form.dataset.staffId = staff.id;
        } else {
            title.textContent = 'Add New Staff Member';
            form.reset();
            delete form.dataset.staffId;
        }

        modal.style.display = 'block';
        document.getElementById('staff-name').focus();
    }

    // Hide staff modal
    hideStaffModal() {
        const modal = document.getElementById('staff-modal');
        modal.style.display = 'none';
        document.getElementById('staff-form').reset();
    }

    // Fill staff form with data
    fillStaffForm(staff) {
        document.getElementById('staff-name').value = staff.name;
        document.getElementById('staff-username').value = staff.username;
        document.getElementById('staff-password').value = ''; // Don't pre-fill password
        document.getElementById('staff-phone').value = staff.phone;
        document.getElementById('staff-email').value = staff.email || '';
        document.getElementById('staff-role-select').value = staff.role;
    }

    // Handle staff form submission
    handleStaffSubmit() {
        const form = document.getElementById('staff-form');
        const formData = new FormData(form);
        
        const staffData = {
            name: formData.get('staff-name')?.trim(),
            username: formData.get('staff-username')?.trim(),
            password: formData.get('staff-password')?.trim(),
            phone: formData.get('staff-phone')?.trim(),
            email: formData.get('staff-email')?.trim(),
            role: formData.get('staff-role-select')
        };

        // Validate data
        if (!this.validateStaffData(staffData, !!form.dataset.staffId)) {
            return;
        }

        try {
            if (form.dataset.staffId) {
                // Update existing staff
                const staffId = parseInt(form.dataset.staffId);
                const updateData = { ...staffData };
                
                // Only update password if provided
                if (!staffData.password) {
                    delete updateData.password;
                }
                
                User.update(staffId, updateData);
                this.showSuccess('Staff member updated successfully');
            } else {
                // Create new staff
                Staff.create(staffData);
                this.showSuccess('Staff member added successfully');
            }

            this.hideStaffModal();
            this.loadStaff();
            
        } catch (error) {
            console.error('Error saving staff:', error);
            this.showError('Failed to save staff member');
        }
    }

    // Validate staff data
    validateStaffData(data, isEditing = false) {
        const errors = [];

        if (!data.name) errors.push('Name is required');
        if (!data.username) errors.push('Username is required');
        if (!isEditing && !data.password) errors.push('Password is required');
        if (!data.phone) errors.push('Phone number is required');
        if (!data.role) errors.push('Role is required');

        // Check for duplicate username
        if (data.username) {
            const existingUser = User.findByUsername(data.username);
            const form = document.getElementById('staff-form');
            
            if (existingUser && (!isEditing || existingUser.id !== parseInt(form.dataset.staffId))) {
                errors.push('Username already exists');
            }
        }

        // Validate email format
        if (data.email && !this.isValidEmail(data.email)) {
            errors.push('Invalid email format');
        }

        // Validate password length (only if provided)
        if (data.password && data.password.length < 6) {
            errors.push('Password must be at least 6 characters');
        }

        if (errors.length > 0) {
            this.showError(errors.join(', '));
            return false;
        }

        return true;
    }

    // Validate email format
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Edit staff member
    editStaff(staffId) {
        const staff = User.findById(staffId);
        if (staff) {
            this.showStaffModal(staff);
        } else {
            this.showError('Staff member not found');
        }
    }

    // Delete staff member
    deleteStaff(staffId) {
        const staff = User.findById(staffId);
        if (!staff) {
            this.showError('Staff member not found');
            return;
        }

        // Prevent deleting the current user
        const currentUser = window.app?.getCurrentUser();
        if (currentUser && currentUser.id === staffId) {
            this.showError('You cannot delete your own account');
            return;
        }

        if (confirm(`Are you sure you want to delete ${staff.getDisplayName()}?`)) {
            try {
                User.delete(staffId);
                this.showSuccess('Staff member deleted successfully');
                this.loadStaff();
            } catch (error) {
                console.error('Error deleting staff:', error);
                this.showError('Failed to delete staff member');
            }
        }
    }

    // Toggle staff status (activate/deactivate)
    toggleStaffStatus(staffId) {
        const staff = User.findById(staffId);
        if (!staff) {
            this.showError('Staff member not found');
            return;
        }

        // Prevent deactivating the current user
        const currentUser = window.app?.getCurrentUser();
        if (currentUser && currentUser.id === staffId && staff.isActive) {
            this.showError('You cannot deactivate your own account');
            return;
        }

        try {
            if (staff.isActive) {
                User.deactivate(staffId);
                this.showSuccess(`${staff.getDisplayName()} has been deactivated`);
            } else {
                User.activate(staffId);
                this.showSuccess(`${staff.getDisplayName()} has been activated`);
            }
            
            this.loadStaff();
        } catch (error) {
            console.error('Error toggling staff status:', error);
            this.showError('Failed to update staff status');
        }
    }

    // Get staff performance report
    getStaffPerformanceReport() {
        const performanceData = Staff.getPerformanceData();
        return performanceData.map(staff => ({
            name: staff.getDisplayName(),
            role: staff.role,
            totalSales: staff.totalSales,
            salesCount: staff.salesCount,
            monthlySales: staff.performance.monthlySales,
            monthlyTarget: staff.performance.monthlyTarget,
            performanceRating: staff.getPerformanceRating(),
            status: staff.getStatus()
        }));
    }

    // Export staff data
    exportStaffData() {
        const staffData = this.currentStaff.map(staff => ({
            name: staff.name,
            username: staff.username,
            phone: staff.phone,
            email: staff.email || '',
            role: staff.role,
            status: staff.isActive ? 'Active' : 'Inactive',
            createdAt: utils.formatDate(staff.createdAt),
            lastLogin: staff.lastLogin ? utils.formatDateTime(staff.lastLogin) : 'Never'
        }));

        const csv = this.convertToCSV(staffData);
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = URL.createObjectURL(blob);

        const a = document.createElement('a');
        a.href = url;
        a.download = `staff-data-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Convert staff data to CSV
    convertToCSV(data) {
        if (data.length === 0) return '';
        
        const headers = Object.keys(data[0]);
        const rows = data.map(item => 
            headers.map(header => `"${item[header] || ''}"`).join(',')
        );

        return [headers.join(','), ...rows].join('\n');
    }

    // Show success message
    showSuccess(message) {
        window.app?.showMessage(message, 'success');
    }

    // Show error message
    showError(message) {
        window.app?.showMessage(message, 'error');
    }
}
