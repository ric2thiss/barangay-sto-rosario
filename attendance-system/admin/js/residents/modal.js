/**
 * Modal Module
 * Exports modal element and API functions
 */
export const modal = document.getElementById("ShowResidentModal");

/**
 * Get resident data from API
 */
export const getResident = async (id = null) => {
    try {
        // OLD ENDPOINTS (backward compatible):
        //   ../api/v1/request.php?query=residents&id=${id}
        //   ../api/v1/request.php?query=residents
        // NEW ENDPOINTS:
        //   ../api/residents/show.php?id=${id} (with ID)
        //   ../api/residents/index.php (without ID)
        // Build the URL dynamically (with or without ID)
        const url = id
            ? `../api/residents/show.php?id=${id}`
            : `../api/residents/index.php`;

        const res = await fetch(url);

        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }

        const data = await res.json();
        return data; // ✅ return to use it outside
    } catch (error) {
        console.error("Failed to fetch resident(s):", error);
        return null; // optional fallback
    }
};
