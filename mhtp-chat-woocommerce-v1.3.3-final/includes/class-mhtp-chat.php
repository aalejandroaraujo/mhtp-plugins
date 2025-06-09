<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MHTP_Chat {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_scripts() {
        $expert_id = isset( $_GET['ExpertId'] ) ? absint( $_GET['ExpertId'] ) : 0;

        wp_register_script(
            'typebot-js',
            'https://cdn.jsdelivr.net/npm/@typebot.io/js@0.2',
            array(),
            '0.2',
            true
        );
        wp_enqueue_script( 'typebot-js' );

        wp_register_script(
            'mhtp-chat-init',
            MHTP_CHAT_PLUGIN_URL . 'assets/js/mhtp-chat-init.js',
            array( 'typebot-js' ),
            MHTP_CHAT_VERSION,
            true
        );

        wp_localize_script( 'mhtp-chat-init', 'mhtpChatData', array( 'ExpertId' => $expert_id ) );
        wp_enqueue_script( 'mhtp-chat-init' );
    }
}
