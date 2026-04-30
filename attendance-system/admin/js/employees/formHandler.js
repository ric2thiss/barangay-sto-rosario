/**
 * Form Handler Module
 * Handles form validation and submission for employee forms
 */
import { EmployeeAPI } from './api.js';

export class EmployeeFormHandler {
    constructor(apiUrl) {
        this.api = new EmployeeAPI(apiUrl);
        this.addForm = null;
        this.editForm = null;
    }

    /**
     * Get form data from add employee form
     */
    getAddFormData() {
        const employeeId = document.getElementById('employee_id')?.value || '';
        const residentId = document.getElementById('resident_id')?.value || '';
        const departmentId = document.getElementById('department_id')?.value || '';
        const positionId = document.getElementById('position_id')?.value || '';
        const hiredDate = document.getElementById('hired_date')?.value || '';

        return {
            employee_id: employeeId,
            resident_id: residentId,
            department_id: departmentId,
            position_id: positionId,
            hired_date: hiredDate
        };
    }

    /**
     * Validate form data
     */
    validateFormData(data) {
        const errors = [];

        if (!data.resident_id) {
            errors.push('Resident selection is required');
        }

        if (!data.department_id) {
            errors.push('Department selection is required');
        }

        if (!data.position_id) {
            errors.push('Position selection is required');
        }

        if (!data.hired_date) {
            errors.push('Hired date is required');
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    /**
     * Handle form submission
     */
    async handleSubmit(event) {
        event?.preventDefault();

        const formData = this.getAddFormData();
        const validation = this.validateFormData(formData);

        if (!validation.isValid) {
            alert(validation.errors.join('\n'));
            return;
        }

        try {
            const result = await this.api.createEmployee(formData);
            
            if (result.message) {
                alert(result.message || 'Employee added successfully!');
            }

            // Reset form
            const form = document.querySelector('#addEmployeeModal form');
            if (form) {
                form.reset();
            }

            // Reload page to show new employee
            window.location.reload();
        } catch (error) {
            alert(error.message || 'Failed to add employee. Please try again.');
            console.error('Form submission error:', error);
        }
    }

    /**
     * Initialize form handlers
     */
    init() {
        const addEmployeeBtn = document.getElementById('addEmployeeBtn');
        if (addEmployeeBtn) {
            addEmployeeBtn.addEventListener('click', (e) => this.handleSubmit(e));
        }

        // Handle edit button clicks (delegation)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('editBtn') || e.target.closest('.editBtn')) {
                const editBtn = e.target.classList.contains('editBtn') ? e.target : e.target.closest('.editBtn');
                const employeeId = editBtn.dataset.id;
                if (employeeId) {
                    this.handleEditClick(employeeId);
                }
            }
        });
    }

    /**
     * Handle edit button click
     */
    handleEditClick(employeeId) {
        const modal = document.getElementById('editEmployeeModal');
        if (modal) {
            const employeeIdInput = document.getElementById('edit_modal_employee_id');
            if (employeeIdInput) {
                employeeIdInput.value = employeeId;
            }
            modal.classList.remove('hidden');
        }
    }
}
