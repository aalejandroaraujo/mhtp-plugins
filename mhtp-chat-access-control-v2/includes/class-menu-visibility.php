<?php
/**
 * Menu Visibility Control for MHTP Chat Access Control
 * 
 * This file contains functions to control the visibility of the
 * "Chat con expertos" menu item based on user login status and available sessions.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Menu Visibility Control Class
 */
class MHTP_Menu_Visibility_Control {
    
    /**
     * Initialize the menu visibility control
     */
    public function __construct() {
        // Hook into nav menu items to filter them
        add_filter('wp_nav_menu_objects', array($this, 'filter_menu_items'), 10, 2);
    }
    
    /**
     * Filter menu items to hide "Chat con expertos" when necessary
     */
    public function filter_menu_items($items, $args) {
        // If user is not logged in, hide the chat menu item
        if (!is_user_logged_in()) {
            return $this->hide_chat_menu_item($items);
        }
        
        // If user is logged in but has no available sessions, hide the chat menu item
        $user_id = get_current_user_id();
        $available_sessions = $this->get_user_available_sessions($user_id);
        
        if ($available_sessions <= 0) {
            return $this->hide_chat_menu_item($items);
        }
        
        // Otherwise, show all menu items
        return $items;
    }
    
    /**
     * Hide the "Chat con expertos" menu item from the items array
     */
    private function hide_chat_menu_item($items) {
        // Get the chat page slug from settings
        $options = get_option('mhtp_chat_access_options');
        $chat_page_slug = isset($options['chat_page_slug']) ? $options['chat_page_slug'] : 'chat-con-expertos';
        
        // Convert to array if it's not already
        if (!is_array($items)) {
            return $items;
        }
        
        foreach ($items as $key => $item) {
            // Check if this menu item links to the chat page
            if ($this->is_chat_menu_item($item, $chat_page_slug)) {
                // Remove this item from the array
                unset($items[$key]);
            }
        }
        
        // Re-index the array
        return array_values($items);
    }
    
    /**
     * Check if a menu item is the chat menu item
     */
    private function is_chat_menu_item($item, $chat_page_slug) {
        // Check if the menu item URL contains the chat page slug
        if (strpos($item->url, '/' . $chat_page_slug . '/') !== false) {
            return true;
        }
        
        // Check if the menu item title contains "Chat con expertos"
        if (strpos(strtolower($item->title), 'chat con expertos') !== false) {
            return true;
        }
        
        // Check if this is a custom menu item with a specific ID or class
        if (isset($item->classes) && is_array($item->classes)) {
            foreach ($item->classes as $class) {
                if (strpos($class, 'chat-menu-item') !== false || 
                    strpos($class, 'menu-chat') !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get number of available sessions for a user
     */
    public function get_user_available_sessions($user_id) {
        // Get available sessions from user meta
        $available_sessions = get_user_meta($user_id, 'mhtp_available_sessions', true);
        
        // If not set, default to 0
        if ($available_sessions === '' || $available_sessions === false) {
            $available_sessions = 0;
        }
        
        // Allow filtering of available sessions
        return apply_filters('mhtp_user_available_sessions', intval($available_sessions), $user_id);
    }
}
