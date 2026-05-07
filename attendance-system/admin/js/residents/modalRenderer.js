/**
 * Modal Renderer Module
 * Handles rendering resident data into the modal
 */
export class ModalRenderer {
    constructor() {
        this.headerResidentName = document.getElementById("header_resident_name");
        this.name = document.getElementById("name");
        this.id = document.getElementById("id");
        this.philsys_no = document.getElementById("philsys_no");
        this.gender = document.getElementById("gender");
        this.bod = document.getElementById("bod");
        this.pod = document.getElementById("pod");
        this.civil_status = document.getElementById("civil_status");
        this.blood_type = document.getElementById("blood_type");
        this.contact = document.getElementById("contact");
        this.position = document.getElementById("position");
        this.employeer_name = document.getElementById("employeer_name");
        this.income_bracket = document.getElementById("income_bracket");
        this.ooh = document.getElementById("ooh");
        this.residency = document.getElementById("residency");
        this.barangay = document.getElementById("barangay");
        this.postal_code = document.getElementById("postal_code");
        this.id_type = document.getElementById("id_type");
        this.id_number = document.getElementById("id_number");
        this.issue_date = document.getElementById("issue_date");
        this.expiry_date = document.getElementById("expiry_date");
        this.resident_biometrics = document.getElementById("resident_biometrics");
        this.relative_name = document.getElementById("relative_name");
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
            const placeOfBirth = [];
            if (d.place_of_birth_city) placeOfBirth.push(d.place_of_birth_city);
            if (d.place_of_birth_province) placeOfBirth.push(d.place_of_birth_province);
            this.pod.textContent = placeOfBirth.length > 0 ? placeOfBirth.join(', ') : "N/A";
        }
        
        // Civil status (from civil_status table)
        if (this.civil_status) this.civil_status.textContent = d.status_name ?? d.civil_status ?? "N/A";
        if (this.blood_type) this.blood_type.textContent = d.blood_type ?? "N/A";

        // Additional fields
        if (this.contact) this.contact.textContent = d.contact_value ?? "N/A";
        if (this.position) this.position.textContent = d.job_title ?? "N/A";
        if (this.employeer_name) this.employeer_name.textContent = d.employer ?? "N/A";
        if (this.income_bracket) this.income_bracket.textContent = d.income_bracket ?? "N/A";

        // Additional fields
        if (this.ooh) this.ooh.textContent = d.is_owner == 1 ? "Yes" : (d.is_owner == 0 ? "No" : "N/A");
        if (this.residency) this.residency.textContent = d.months_of_residency ? `${d.months_of_residency} months` : "N/A";
        if (this.barangay) this.barangay.textContent = d.barangay ?? "N/A";
        if (this.postal_code) this.postal_code.textContent = d.postal_code ?? "N/A";

        // Additional fields
        if (this.id_type) this.id_type.textContent = d.id_type ?? "N/A";
        if (this.id_number) this.id_number.textContent = d.id_number ?? "N/A";
        if (this.issue_date) this.issue_date.textContent = d.issue_date ?? "N/A";
        if (this.expiry_date) this.expiry_date.textContent = d.expiry_date ?? "N/A";

        // Additional fields
        if (this.resident_biometrics) this.resident_biometrics.textContent = d.biometric_type ?? "N/A";

        // Relatives
        if (this.relative_name) {
            if (d.relatives && Array.isArray(d.relatives) && d.relatives.length > 0) {
                const relativesList = d.relatives.map(rel => {
                    const relName = `${rel.first_name ?? ''} ${rel.last_name ?? ''}`.trim();
                    const relationship = rel.relationship_type ? ` (${rel.relationship_type})` : '';
                    return relName + relationship;
                }).join(', ');
                this.relative_name.textContent = relativesList;
            } else {
                this.relative_name.textContent = "N/A";
            }
        }

        return true;
    }
}
