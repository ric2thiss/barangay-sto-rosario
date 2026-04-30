/**
 * Delete Handler Module
 * Handles resident deletion functionality
 */
export class DeleteHandler {
    constructor() {
        this.deleteApiUrl = '../api/residents/delete.php';
    }

    /**
     * Handle delete button click
     */
    async handleDeleteClick(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Get the button - works for both direct clicks and delegation
        const deleteBtn = e.currentTarget && e.currentTarget.classList.contains("delete") 
            ? e.currentTarget 
            : e.target.closest(".delete");
        
        if (!deleteBtn) {
            console.error("Delete button not found!");
            return;
        }
        
        console.log("Delete button clicked!", deleteBtn);
        
        const residentId = deleteBtn.getAttribute("data-id") || deleteBtn.dataset.id;
        const residentName = deleteBtn.getAttribute("data-name") || deleteBtn.dataset.name || "this resident";

        console.log("Resident ID:", residentId, "Name:", residentName);

        if (!residentId) {
            console.error("Resident ID not found! Button:", deleteBtn);
            alert("Error: Resident ID is missing.");
            return;
        }

        if (confirm(`Are you sure you want to delete ${residentName}? This action cannot be undone.`)) {
            console.log("Deletion confirmed, sending request...");
            
            // Show loading state
            const originalHTML = deleteBtn.innerHTML;
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<svg class="w-4 h-4 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>Deleting...';
            
            // OLD ENDPOINT (backward compatible): ../api/v1/request.php?query=residents&id=${residentId}
            // NEW ENDPOINT: ../api/residents/delete.php?id=${residentId}
            // Send delete request
            fetch(`${this.deleteApiUrl}?id=${residentId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log("Response status:", response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Delete response:", data);
                if (data.success) {
                    alert(data.message || "Resident deleted successfully!");
                    location.reload();
                } else {
                    alert(data.message || "Failed to delete resident.");
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert("An error occurred while deleting the resident. Please try again.");
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalHTML;
            });
        }
    }

    /**
     * Initialize delete handlers
     */
    init() {
        // Set up direct event listeners on delete buttons as a fallback
        const deleteButtons = document.querySelectorAll(".delete");
        console.log("Found delete buttons:", deleteButtons.length);
        
        deleteButtons.forEach(btn => {
            btn.addEventListener("click", (e) => this.handleDeleteClick(e));
        });

        // Also handle via event delegation
        document.addEventListener("click", (e) => {
            const deleteBtn = e.target.closest(".delete");
            if (deleteBtn) {
                this.handleDeleteClick(e);
            }
        });
    }
}
