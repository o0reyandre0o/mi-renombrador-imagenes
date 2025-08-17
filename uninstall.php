<?php
/**
 * Uninstall script for Toc Toc SEO Images Plugin
 * 
 * Fired when the plugin is uninstalled.
 * Cleans up all plugin data from the database.
 * 
 * @package TocTocSEOImages
 * @version 3.6.0
 * @since 3.0.0
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove plugin options from database
 */
function mri_uninstall_cleanup_options() {
    // Remove main plugin options
    delete_option( 'mri_google_ai_options' );
    
    // Remove any additional options that might have been created
    delete_option( 'mri_plugin_version' );
    delete_option( 'mri_compression_stats' );
    delete_option( 'mri_total_processed' );
    delete_option( 'mri_total_saved' );
    delete_option( 'mri_last_bulk_process' );
    
    // For multisite installations
    if ( is_multisite() ) {
        $blog_ids = get_sites( array( 'fields' => 'ids' ) );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            delete_option( 'mri_google_ai_options' );
            delete_option( 'mri_plugin_version' );
            delete_option( 'mri_compression_stats' );
            delete_option( 'mri_total_processed' );
            delete_option( 'mri_total_saved' );
            delete_option( 'mri_last_bulk_process' );
            restore_current_blog();
        }
    }
}

/**
 * Remove plugin meta data from posts
 * Only removes temporary processing flags, keeps user-generated content
 */
function mri_uninstall_cleanup_post_meta() {
    global $wpdb;
    
    // Remove temporary processing flags
    $wpdb->delete(
        $wpdb->postmeta,
        array(
            'meta_key' => '_mri_processing_bulk'
        )
    );
    
    $wpdb->delete(
        $wpdb->postmeta,
        array(
            'meta_key' => '_mri_processing_upload'
        )
    );
    
    // Note: We do NOT remove _wp_attachment_image_alt as this is valuable user data
    // that should persist even after plugin removal
}

/**
 * Clean up transients and cache
 */
function mri_uninstall_cleanup_transients() {
    global $wpdb;
    
    // Remove plugin-specific transients
    $wpdb->query(
        "DELETE FROM {$wpdb->options} 
         WHERE option_name LIKE '_transient_mri_%' 
         OR option_name LIKE '_transient_timeout_mri_%'"
    );
    
    // Clear any WordPress cache
    if ( function_exists( 'wp_cache_flush' ) ) {
        wp_cache_flush();
    }
}

/**
 * Remove custom capabilities (if any were added)
 */
function mri_uninstall_cleanup_capabilities() {
    // Remove custom capabilities if any were added
    // Currently the plugin uses standard WordPress capabilities
    // This function is here for future extensibility
    
    $roles = wp_roles();
    if ( $roles ) {
        foreach ( $roles->roles as $role_name => $role_info ) {
            $role = get_role( $role_name );
            if ( $role ) {
                // Remove custom capabilities if they exist
                $role->remove_cap( 'mri_manage_bulk_processing' );
                $role->remove_cap( 'mri_manage_ai_settings' );
            }
        }
    }
}

/**
 * Log uninstall process (optional)
 */
function mri_uninstall_log() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( 'MRI Plugin: Uninstall process completed at ' . current_time( 'mysql' ) );
    }
}

/**
 * Main uninstall function
 */
function mri_run_uninstall() {
    // Security check
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    
    // Verify the uninstall action
    check_admin_referer( 'bulk-plugins' );
    
    // Run cleanup functions
    mri_uninstall_cleanup_options();
    mri_uninstall_cleanup_post_meta();
    mri_uninstall_cleanup_transients();
    mri_uninstall_cleanup_capabilities();
    mri_uninstall_log();
}

// Execute uninstall
mri_run_uninstall();

/**
 * Optional: Display admin notice about what was cleaned
 * This won't show during actual uninstall, but documents what gets removed
 */
function mri_uninstall_notice() {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $cleaned_items = array(
            'Plugin options and settings',
            'Temporary processing flags',
            'Plugin-specific transients',
            'Custom capabilities (if any)',
            'Cache entries'
        );
        
        $preserved_items = array(
            'Image Alt Text (valuable user data)',
            'Image Titles (valuable user data)', 
            'Image Captions (valuable user data)',
            'Compressed images (file optimizations)',
            'Renamed files (file organization)'
        );
        
        error_log( 'MRI Plugin Uninstall - Cleaned: ' . implode( ', ', $cleaned_items ) );
        error_log( 'MRI Plugin Uninstall - Preserved: ' . implode( ', ', $preserved_items ) );
    }
}
mri_uninstall_notice();

/**
 * Clear any scheduled events
 */
function mri_uninstall_clear_scheduled_events() {
    // Clear any cron jobs that might have been scheduled
    wp_clear_scheduled_hook( 'mri_daily_cleanup' );
    wp_clear_scheduled_hook( 'mri_weekly_stats' );
    wp_clear_scheduled_hook( 'mri_bulk_process_resume' );
}
mri_uninstall_clear_scheduled_events();
