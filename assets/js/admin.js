/**
 * MaxtDesign Lean Consent - Admin JavaScript
 * 
 * Handles color picker initialization
 * 
 * @package MaxtDesign_Lean_Consent
 * @since 1.6.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize WordPress color picker
        if ($.fn.wpColorPicker) {
            $('.mdlc-color-picker').wpColorPicker();
        }
        
    });
    
})(jQuery);

