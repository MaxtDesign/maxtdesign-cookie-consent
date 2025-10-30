<?php
/**
 * Uninstall Script
 *
 * Fired when the plugin is uninstalled (deleted from WordPress admin).
 * Removes all plugin data from the database for complete cleanup.
 *
 * IMPORTANT: This script only runs when the plugin is DELETED, not deactivated.
 * Deactivation preserves all settings. Deletion removes everything.
 *
 * === What Gets Deleted ===
 *
 * OPTIONS:
 * - mdlc_settings (array) - All plugin configuration
 * - mdlc_version (string) - Plugin version number
 *
 * TRANSIENTS:
 * - mdlc_cache (if exists) - Any cached data
 *
 * NOT DELETED (Does Not Exist):
 * - Custom database tables (plugin doesn't create any)
 * - Scheduled cron jobs (plugin doesn't use wp-cron)
 * - User meta data (plugin doesn't store any)
 * - Post meta data (plugin doesn't store any)
 * - User consent data (stored in browser localStorage, not database)
 *
 * MULTISITE:
 * If WordPress is multisite, data is removed from ALL sites in the network.
 *
 * RECOVERY:
 * After deletion, reinstalling the plugin creates a fresh installation
 * with default settings. User consent choices (in localStorage) are
 * unaffected as they're stored client-side.
 *
 * @package MaxtDesign_Lean_Consent
 * @since 1.6.0
 */

/**
 * Security Check
 *
 * Verify this script is being called by WordPress during plugin deletion.
 * The WP_UNINSTALL_PLUGIN constant is only defined when WordPress
 * is properly uninstalling a plugin.
 *
 * If this constant is not defined, it means someone is trying to
 * access this file directly, which is not allowed.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit; // Exit if accessed directly
}

/**
 * Plugin Data Footprint Verification
 *
 * This checklist documents all WordPress data storage locations
 * and confirms what this plugin uses/doesn't use:
 *
 * [✓] wp_options table:
 *     - mdlc_settings (USED - deleted in uninstall)
 *     - mdlc_version (USED - deleted in uninstall)
 *
 * [✓] Transients (wp_options):
 *     - mdlc_cache (POTENTIALLY USED - deleted in uninstall)
 *
 * [✗] Custom database tables:
 *     - NOT USED (plugin doesn't create custom tables)
 *
 * [✗] wp_usermeta table:
 *     - NOT USED (plugin doesn't store user preferences server-side)
 *
 * [✗] wp_postmeta table:
 *     - NOT USED (plugin doesn't attach data to posts)
 *
 * [✗] wp_termmeta table:
 *     - NOT USED (plugin doesn't work with taxonomy terms)
 *
 * [✗] wp_commentmeta table:
 *     - NOT USED (plugin doesn't work with comments)
 *
 * [✗] Scheduled cron jobs (wp_cron):
 *     - NOT USED (plugin doesn't schedule any tasks)
 *
 * [✗] Uploaded files (wp-content/uploads):
 *     - NOT USED (plugin doesn't upload files)
 *
 * [✗] External databases:
 *     - NOT USED (plugin doesn't connect to external databases)
 *
 * [✗] External API accounts:
 *     - NOT USED (plugin doesn't require external services)
 *
 * [i] User consent data:
 *     - Stored in browser localStorage (client-side)
 *     - NOT in WordPress database
 *     - Unaffected by plugin uninstall
 *     - Users keep their consent choices even after reinstall
 */

/**
 * Delete plugin data for a single site
 *
 * Removes all options and transients created by the plugin.
 * Called for single site installations and for each site in multisite.
 *
 * @since 1.6.0
 */
function mdlc_uninstall_site() {
    // Delete plugin options
    delete_option('mdlc_settings');
    delete_option('mdlc_version');

    // Delete any transients (none currently used, but cleanup just in case)
    delete_transient('mdlc_cache');

    // No custom database tables to drop (plugin doesn't create any)
    // No scheduled cron jobs to remove (plugin doesn't use wp-cron)
    // No user meta to remove (plugin doesn't store user meta)
    // No post meta to remove (plugin doesn't store post meta)
    // User consent data is stored in browser localStorage only (not in database)
}

/**
 * Multisite Cleanup
 *
 * For multisite (network) installations, we need to clean up
 * data from EVERY site in the network, not just the main site.
 *
 * Process:
 * 1. Check if multisite using is_multisite()
 * 2. Get all sites using get_sites()
 * 3. Loop through each site
 * 4. Switch to site using switch_to_blog()
 * 5. Run cleanup for that site
 * 6. Restore using restore_current_blog()
 *
 * This ensures no orphaned data remains on any site.
 */

/**
 * Run uninstall cleanup
 *
 * Handles both single site and multisite installations.
 * For multisite, cleans up data on all sites in the network.
 *
 * @since 1.6.0
 */
function mdlc_uninstall() {
    if (is_multisite()) {
        $sites = get_sites(array(
            'number' => 0, // Get all sites
        ));

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            mdlc_uninstall_site();
            restore_current_blog();
        }
    } else {
        mdlc_uninstall_site();
    }
}

/**
 * Testing Uninstall
 *
 * To verify this script works correctly:
 *
 * 1. BACKUP YOUR DATABASE FIRST
 *
 * 2. Activate plugin and configure settings
 *    - Change popup style to "Modern"
 *    - Change primary color
 *    - Verify settings saved
 *
 * 3. Check database for plugin data:
 *    SELECT * FROM wp_options WHERE option_name LIKE 'mdlc_%';
 *    Should return: mdlc_settings, mdlc_version
 *
 * 4. Deactivate plugin (data should persist)
 *    - Run query again
 *    - Should still return data
 *
 * 5. Delete plugin from admin (Plugins > Delete)
 *    - WordPress will call this uninstall script
 *
 * 6. Check database again:
 *    SELECT * FROM wp_options WHERE option_name LIKE 'mdlc_%';
 *    Should return: ZERO rows (all data removed)
 *
 * 7. For multisite, repeat on multiple sites:
 *    - Network activate plugin
 *    - Configure on 2-3 sites
 *    - Check each site's database for data
 *    - Network delete plugin
 *    - Verify all sites' data removed
 *
 * 8. Test reinstall:
 *    - Reinstall plugin
 *    - Should start with default settings (not previous config)
 */

// Run the uninstall
mdlc_uninstall();

