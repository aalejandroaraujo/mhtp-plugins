jQuery(document).ready(function($) {
    // Handle summary button clicks
    $('.mhtp-cs-summary-button').on('click', function(e) {
        e.preventDefault();
        
        const sessionId = $(this).data('session-id');
        
        // In a real implementation, this would fetch the summary from the server
        // For now, just show an alert
        alert('Esta funcionalidad estará disponible próximamente. Resumen para sesión #' + sessionId);
    });
    
    // Force white text on "Ver resumen" buttons
    function forceWhiteText() {
        $('.mhtp-cs-summary-button').each(function() {
            $(this).addClass('white-text-button');
        });
    }
    
    // Run immediately
    forceWhiteText();
    
    // Also run after a short delay to catch dynamically added elements
    setTimeout(forceWhiteText, 500);
});
