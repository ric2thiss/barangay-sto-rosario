/**
 * View Handler Module
 * Handles view button clicks and modal display
 */
import { getResident } from "./modal.js";
import { ModalRenderer } from "./modalRenderer.js";

export class ViewHandler {
    constructor() {
        this.modalRenderer = new ModalRenderer();
    }

    /**
     * Handle view button click
     */
    async handleViewClick(residentId) {
        if (!residentId) {
            console.error("Resident ID not found!");
            alert("Failed to load resident data. Please try again.");
            return;
        }

        // Fetch resident data from backend
        const data = await getResident(residentId);

        console.log("Resident data:", data);
        
        if (!data) {
            console.error("Resident data not found!");
            alert("Failed to load resident data. Please try again.");
            return;
        }

        // Render data into modal
        const success = this.modalRenderer.render(data);
        
        if (success) {
            // Get modal element and show it
            const modal = document.getElementById("ShowResidentModal");
            if (modal) {
                modal.classList.remove("hidden");
            }
        }
    }

    /**
     * Initialize view handlers
     */
    init() {
        // Handle click events (delegation approach)
        document.addEventListener("click", async (e) => {
            // Allow edit links to work normally
            if (e.target.closest('.edit-link') || e.target.classList.contains('edit-link')) {
                return; // Let the onclick handler in the link work
            }
            
            // Check if the clicked element is inside a link
            const clickedLink = e.target.closest('a');
            if (clickedLink) {
                // If it's a regular link (including view links that are now anchors), let it work normally
                const isDeleteButton = clickedLink.classList.contains('delete');
                const isEditLink = clickedLink.classList.contains('edit-link');
                const isViewLink = clickedLink.tagName === 'A' && clickedLink.classList.contains('view');
                
                // Allow normal link navigation for view links and other links (except delete)
                if (isViewLink || (!isDeleteButton && !isEditLink)) {
                    return; // Allow normal link navigation - don't prevent default
                }
            }
            
            // Also check if clicking directly on a link (not a button)
            if (e.target.tagName === 'A' && !e.target.classList.contains('delete') && !e.target.classList.contains('edit-link')) {
                return; // Allow normal navigation for all anchor tags except delete and edit-link
            }
            
            // Handle view button clicks (for backward compatibility with old button-based views)
            if (e.target.classList.contains("view") || e.target.closest(".view")) {
                // Only handle if it's a button, not an anchor tag
                const viewElement = e.target.classList.contains("view") ? e.target : e.target.closest(".view");
                if (viewElement.tagName === 'A') {
                    return; // Let anchor tags work normally
                }
                
                e.preventDefault();
                e.stopPropagation();
                const resident_id = viewElement.dataset.id;
                
                if (resident_id) {
                    await this.handleViewClick(resident_id);
                }
            }
        });
    }
}
