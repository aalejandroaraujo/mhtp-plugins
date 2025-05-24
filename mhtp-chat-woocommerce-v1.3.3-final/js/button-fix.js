/**
 * JavaScript to force button text color to white
 * This is an aggressive approach to ensure button text is visible
 */
jQuery(document).ready(function($) {
    // Function to force button text color
    function forceButtonTextColor() {
        // Target all buttons with "Ver resumen" text
        $('button:contains("Ver resumen"), a:contains("Ver resumen")').each(function() {
            $(this).css({
                'color': 'white',
                'font-weight': 'bold',
                'text-shadow': '0px 0px 3px rgba(0, 0, 0, 0.5)'
            });
            
            // Also add inline style attribute for maximum compatibility
            $(this).attr('style', 'color: white !important; font-weight: bold !important; text-shadow: 0px 0px 3px rgba(0, 0, 0, 0.5) !important;');
        });
    }
    
    // Run immediately
    forceButtonTextColor();
    
    // Also run after a short delay to catch dynamically added elements
    setTimeout(forceButtonTextColor, 500);
    setTimeout(forceButtonTextColor, 1500);
    
    // Use MutationObserver to catch dynamically added buttons
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            forceButtonTextColor();
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
});
