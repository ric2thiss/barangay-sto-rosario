/**
 * API Module
 * Handles all API calls for employees
 */
export class EmployeeAPI {
    constructor(baseUrl) {
        this.baseUrl = baseUrl || '';
        this.apiKey = 'HELLOWORLD';
    }

    /**
     * Create a new employee
     */
    async createEmployee(data) {
        try {
            const formData = new FormData();
            Object.keys(data).forEach(key => {
                if (data[key] !== null && data[key] !== undefined) {
                    formData.append(key, data[key]);
                }
            });

            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'x-api-key': this.apiKey,
                    'Content-Type': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || result.message || 'Failed to create employee');
            }

            return result;
        } catch (error) {
            console.error('Error creating employee:', error);
            throw error;
        }
    }

    /**
     * Update an existing employee
     */
    async updateEmployee(employeeId, data) {
        try {
            const response = await fetch(`${this.baseUrl}?id=${employeeId}`, {
                method: 'PUT',
                headers: {
                    'x-api-key': this.apiKey,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || result.message || 'Failed to update employee');
            }

            return result;
        } catch (error) {
            console.error('Error updating employee:', error);
            throw error;
        }
    }

    /**
     * Delete an employee
     */
    async deleteEmployee(employeeId) {
        try {
            const response = await fetch(`${this.baseUrl}?id=${employeeId}`, {
                method: 'DELETE',
                headers: {
                    'x-api-key': this.apiKey,
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || result.message || 'Failed to delete employee');
            }

            return result;
        } catch (error) {
            console.error('Error deleting employee:', error);
            throw error;
        }
    }
}
