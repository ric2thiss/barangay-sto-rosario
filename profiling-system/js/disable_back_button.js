// Function to disable the back button in the browser
function DisableBackButton() {
    window.history.forward(); // Move the history forward to prevent going back
}

// Call the DisableBackButton function immediately
DisableBackButton();

// Set the DisableBackButton function to run when the window loads
window.onload = DisableBackButton;

// Event listener for when the page is shown (after being loaded from the cache)
window.onpageshow = function(evt) {
    if (evt.persisted) { // Check if the page was loaded from the cache
        DisableBackButton(); // Disable the back button if the page was cached
    }
}

// Prevent any actions on unload (e.g., navigating away)
window.onunload = function() {
    void (0); // Do nothing on unload
}
