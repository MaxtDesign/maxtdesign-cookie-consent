/**
 * MaxtDesign Cookie Consent - Admin JavaScript
 * 
 * Handles color picker initialization
 * 
 * @package MaxtDesign_Cookie_Consent
 * @since 1.6.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize WordPress color picker
        if ($.fn.wpColorPicker) {
            $('.mdcc-color-picker').wpColorPicker();
        }
        
    });
    
})(jQuery);

