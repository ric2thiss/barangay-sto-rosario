/**
 * Modal Renderer Module
 * Handles rendering resident data into the modal
 */
export class ModalRenderer {
    constructor() {
        this.headerResidentName = document.getElementById("header_resident_name");
        this.name = document.getElementById("name");
        this.id = document.getElementById("id");
        this.philsys_no = document.getElementById("philsys_no"); // old, keep just in case
        this.gender = document.getElementById("gender");
        this.bod = document.getElementById("bod");
        this.pod = document.getElementById("pod");
        this.age = document.getElementById("age");
        this.civil_status = document.getElementById("civil_status");
        this.nationality = document.getElementById("nationality");
        
        this.contact = document.getElementById("contact");
        this.occupation = document.getElementById("occupation");
        this.monthly_income = document.getElementById("monthly_income");
        this.educational_attainment = document.getElementById("educational_attainment");
        
        this.address_full = document.getElementById("address_full");
        this.total_household = document.getElementById("total_household");
        this.voters_status = document.getElementById("voters_status");

        this.is_pwd = document.getElementById("is_pwd");
        this.is_deceased = document.getElementById("is_deceased");
        this.created_at = document.getElementById("created_at");
        this.updated_at = document.getElementById("updated_at");
    }

    /**
     * Render resident data into modal
     */
    render(residentData) {
        const d = Array.isArray(residentData) ? residentData[0] : residentData;
        
        if (!d) {
            console.error("Resident data is empty!");
            return false;
        }

        // Update header name
        const fullName = `${d.first_name ?? ""} ${d.middle_name ?? ""} ${d.last_name ?? ""} ${d.suffix ?? ""}`.trim();
        if (this.headerResidentName) this.headerResidentName.textContent = fullName;
        
        // Update profile picture
        const photoImg = document.getElementById("resident_photo");
        if (photoImg) {
            if (d.photo_path && d.photo_path.trim() !== '') {
                const currentPath = window.location.pathname;
                const basePath = currentPath.substring(0, currentPath.indexOf('/admin'));
                let imagePath = d.photo_path.trim();
                
                if (!imagePath.startsWith('/')) {
                    imagePath = '/' + imagePath;
                }
                
                const fullImagePath = basePath + imagePath;
                console.log("Loading image from:", fullImagePath);
                photoImg.src = fullImagePath;
                photoImg.onerror = function() {
                    console.error("Failed to load image:", fullImagePath);
                    this.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'192\' height=\'192\'%3E%3Crect width=\'192\' height=\'192\' fill=\'%23e5e7eb\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%239ca3af\' font-size=\'14\'%3ENo Photo%3C/text%3E%3C/svg%3E';
                };
            } else {
                photoImg.src = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'192\' height=\'192\'%3E%3Crect width=\'192\' height=\'192\' fill=\'%23e5e7eb\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%239ca3af\' font-size=\'14\'%3ENo Photo%3C/text%3E%3C/svg%3E';
            }
        }
        
        // Update status badge
        const statusBadge = document.getElementById("resident_status");
        if (statusBadge) {
            if (d.status_type) {
                statusBadge.textContent = d.status_type;
                statusBadge.classList.remove('hidden');
            } else {
                statusBadge.textContent = '';
                statusBadge.classList.add('hidden');
            }
        }
        
        // Fill in modal fields
        if (this.name) this.name.textContent = fullName;
        if (this.id) this.id.textContent = d.resident_id ?? "N/A";
        if (this.philsys_no) this.philsys_no.textContent = d.phil_sys_number ?? "N/A";
        if (this.gender) this.gender.textContent = d.gender ?? "N/A";
        
        // Format birthdate
        if (this.bod) {
            if (d.birthdate) {
                const birthDate = new Date(d.birthdate);
                this.bod.textContent = birthDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            } else {
                this.bod.textContent = "N/A";
            }
        }
        
        // Place of birth
        if (this.pod) {
            this.pod.textContent = d.place_of_birth_city ?? "N/A";
        }
        
        // Age
        if (this.age) this.age.textContent = d.age ?? "N/A";
        
        // Civil status
        if (this.civil_status) this.civil_status.textContent = d.civil_status ?? "N/A";
        
        // Nationality
        if (this.nationality) this.nationality.textContent = d.nationality ?? "N/A";

        // Contact & Occupation
        if (this.contact) this.contact.textContent = d.contact_no ?? "N/A";
        if (this.occupation) this.occupation.textContent = d.occupation ?? "N/A";
        if (this.monthly_income) this.monthly_income.textContent = d.monthly_income ?? "N/A";
        if (this.educational_attainment) this.educational_attainment.textContent = d.educational_attainment ?? "N/A";

        // Residence Status
        if (this.address_full) {
            const addressParts = [];
            if (d.purok) addressParts.push(`Purok ${d.purok}`);
            if (d.barangay) addressParts.push(d.barangay);
            if (d.municipality_city) addressParts.push(d.municipality_city);
            if (d.province) addressParts.push(d.province);
            this.address_full.textContent = addressParts.length > 0 ? addressParts.join(', ') : "N/A";
        }
        
        if (this.total_household) this.total_household.textContent = d.total_household ?? "N/A";
        if (this.voters_status) this.voters_status.textContent = d.voters_status ?? "N/A";

        // Health & Status
        if (this.is_pwd) this.is_pwd.textContent = d.is_pwd ?? "N/A";
        if (this.is_deceased) this.is_deceased.textContent = d.is_deceased ?? "N/A";

        // System Records
        if (this.created_at) {
            this.created_at.textContent = d.created_at ? new Date(d.created_at).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : "N/A";
        }
        if (this.updated_at) {
            this.updated_at.textContent = d.updated_at ? new Date(d.updated_at).toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' }) : "N/A";
        }

        // Visitor Logs
        const logsTableBody = document.getElementById("visitor_logs_table_body");
        if (logsTableBody) {
            logsTableBody.innerHTML = ''; // Clear previous logs
            if (d.visitor_logs && Array.isArray(d.visitor_logs) && d.visitor_logs.length > 0) {
                d.visitor_logs.forEach(log => {
                    const tr = document.createElement("tr");
                    tr.className = "hover:bg-gray-50 transition-colors";
                    
                    const tdDate = document.createElement("td");
                    tdDate.className = "px-4 py-3 whitespace-nowrap text-gray-900";
                    tdDate.textContent = log.created_at ? new Date(log.created_at).toLocaleString() : "N/A";
                    
                    const tdService = document.createElement("td");
                    tdService.className = "px-4 py-3 text-gray-900";
                    tdService.textContent = log.purpose || "N/A";
                    
                    tr.appendChild(tdDate);
                    tr.appendChild(tdService);
                    logsTableBody.appendChild(tr);
                });
            } else {
                const tr = document.createElement("tr");
                const td = document.createElement("td");
                td.colSpan = 2;
                td.className = "px-4 py-6 text-center text-gray-500 italic";
                td.textContent = "No recent visits recorded for this resident.";
                tr.appendChild(td);
                logsTableBody.appendChild(tr);
            }
        }

        return true;
    }
}
