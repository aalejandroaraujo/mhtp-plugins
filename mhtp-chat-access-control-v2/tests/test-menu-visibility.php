<?php
/**
 * Test cases for MHTP Chat Access Control Menu Visibility
 * 
 * This file contains test scenarios to verify the functionality
 * of the menu visibility control feature.
 */

// Test scenarios for menu visibility
$menu_visibility_test_scenarios = array(
    array(
        'name' => 'Menu item hidden for non-logged in users',
        'description' => 'Verify that the Chat con expertos menu item is hidden for users who are not logged in',
        'setup' => 'Log out of WordPress',
        'steps' => array(
            'Visit the homepage or any page with the main navigation menu',
            'Verify that the "Chat con expertos" menu item is not visible in the navigation'
        ),
        'expected_result' => 'The "Chat con expertos" menu item should not appear in the navigation menu'
    ),
    array(
        'name' => 'Menu item hidden for users without sessions',
        'description' => 'Verify that the Chat con expertos menu item is hidden for logged in users with 0 available sessions',
        'setup' => 'Log in as a user with 0 available sessions',
        'steps' => array(
            'Visit the homepage or any page with the main navigation menu',
            'Verify that the "Chat con expertos" menu item is not visible in the navigation'
        ),
        'expected_result' => 'The "Chat con expertos" menu item should not appear in the navigation menu'
    ),
    array(
        'name' => 'Menu item visible for users with sessions',
        'description' => 'Verify that the Chat con expertos menu item is visible for logged in users with available sessions',
        'setup' => 'Log in as a user with at least 1 available session',
        'steps' => array(
            'Visit the homepage or any page with the main navigation menu',
            'Verify that the "Chat con expertos" menu item is visible in the navigation'
        ),
        'expected_result' => 'The "Chat con expertos" menu item should appear in the navigation menu'
    ),
    array(
        'name' => 'Menu item detection by URL',
        'description' => 'Verify that the menu item is correctly identified by its URL',
        'setup' => 'Log out of WordPress',
        'steps' => array(
            'Visit the homepage or any page with the main navigation menu',
            'Inspect the HTML to verify that menu items with URLs containing "chat-con-expertos" are not present'
        ),
        'expected_result' => 'No menu items with URLs containing "chat-con-expertos" should be present in the HTML'
    ),
    array(
        'name' => 'Menu item detection by title',
        'description' => 'Verify that the menu item is correctly identified by its title',
        'setup' => 'Log out of WordPress',
        'steps' => array(
            'Visit the homepage or any page with the main navigation menu',
            'Inspect the HTML to verify that menu items with titles containing "Chat con expertos" are not present'
        ),
        'expected_result' => 'No menu items with titles containing "Chat con expertos" should be present in the HTML'
    ),
    array(
        'name' => 'Menu visibility changes after login',
        'description' => 'Verify that the menu item appears after logging in with a user that has sessions',
        'setup' => 'Log out of WordPress',
        'steps' => array(
            'Visit the homepage and verify the menu item is not visible',
            'Log in as a user with available sessions',
            'Return to the homepage and verify the menu item is now visible'
        ),
        'expected_result' => 'The menu item should appear after logging in'
    ),
    array(
        'name' => 'Menu visibility with multiple menu locations',
        'description' => 'Verify that the menu item is hidden in all menu locations',
        'setup' => 'Log out of WordPress',
        'steps' => array(
            'Visit pages with different menu locations (header, footer, sidebar, etc.)',
            'Verify that the "Chat con expertos" menu item is not visible in any menu location'
        ),
        'expected_result' => 'The menu item should be hidden in all menu locations'
    )
);
