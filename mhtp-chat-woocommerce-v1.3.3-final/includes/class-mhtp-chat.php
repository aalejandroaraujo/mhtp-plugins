<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MHTP_Chat {
    /** Plugin version */
    const VERSION = '3.1.9';
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts() {
        $expert_id  = isset( $_GET['ExpertId'] ) ? absint( $_GET['ExpertId'] ) : 0;
        $user       = wp_get_current_user();
        $user_email = ( $user instanceof WP_User ) ? $user->user_email : '';

        wp_enqueue_script(
            'mhtp-typebot-widget',
            'https://cdn.typebot.io/widget.js',
            array(),
            null,
            true
        );

        wp_register_script(
            'mhtp-chat-init',
            MHTP_CHAT_PLUGIN_URL . 'assets/js/mhtp-chat-init.js',
            array( 'jquery', 'mhtp-typebot-widget' ),
            MHTP_CHAT_VERSION,
            true
        );

        wp_localize_script(
            'mhtp-chat-init',
            'mhtpChatData',
            array(
                'ExpertId' => $expert_id,
                'UserId'   => $user_email,
            )
        );
        wp_enqueue_script( 'mhtp-chat-init' );
    }
}
