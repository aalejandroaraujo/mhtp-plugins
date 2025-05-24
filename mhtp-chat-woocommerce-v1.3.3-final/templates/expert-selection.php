<?php
/**
 * Expert selection template
 *
 * @package MHTP_Chat_Interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mhtp-chat-container">
    <div class="mhtp-chat-header">
        <h2 class="mhtp-chat-title">Selecciona un Experto para tu Consulta</h2>
        <p class="mhtp-chat-subtitle">Elige el experto con el que deseas hablar para comenzar tu sesión de chat.</p>
    </div>
    
    <div class="mhtp-experts-grid">
        <?php if (!empty($experts)) : ?>
            <?php foreach ($experts as $expert) : ?>
                <div class="mhtp-expert-card">
                    <div class="mhtp-expert-avatar">
                        <img src="<?php echo esc_url($expert['avatar']); ?>" alt="<?php echo esc_attr($expert['name']); ?>">
                    </div>
                    <div class="mhtp-expert-info">
                        <h3 class="mhtp-expert-name"><?php 
                            // Extract just the name without any specialty or description
                            $full_name = $expert['name'];
                            
                            // Special case for Lucía Apoyo
                            if (strpos($full_name, 'Lucía Apoyo') !== false) {
                                echo 'Lucía Apoyo';
                            } else {
                                // Handle different types of separators (dash, hyphen, en dash, em dash)
                                $name_parts = preg_split('/\s*[-–—]\s*/', $full_name, 2);
                                
                                // If we have parts, use the first one, otherwise use the full name
                                $clean_name = !empty($name_parts[0]) ? trim($name_parts[0]) : trim($full_name);
                                
                                // Remove any "Experto en", "Experta en", "Especialista en" prefixes
                                $clean_name = preg_replace('/^(Experto|Experta|Especialista)\s+en\s+/i', '', $clean_name);
                                
                                echo esc_html($clean_name);
                            }
                        ?></h3>
                        <div class="mhtp-expert-specialty"><?php echo esc_html($expert['specialty']); ?></div>
                        <p class="mhtp-expert-description"><?php 
                            // Clean up description if needed
                            $description = $expert['description'];
                            // Remove any strange characters at the beginning
                            $description = preg_replace('/^[\x00-\x1F\x7F-\xFF]+/', '', $description);
                            echo esc_html(trim($description)); 
                        ?></p>
                        <a href="<?php echo esc_url(add_query_arg('expert_id', $expert['id'], get_permalink())); ?>" class="mhtp-start-chat-button">Comenzar consulta</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p class="mhtp-no-experts">No hay expertos disponibles en este momento.</p>
        <?php endif; ?>
    </div>
</div>
