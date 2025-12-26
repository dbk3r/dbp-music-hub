<?php

add_action('plugins_loaded', function() {
    add_filter('template_include', function($template) {
        if (is_post_type_archive('dbp_audio')) {
            error_log('DBP Template-Loader aktiv!');
            $plugin_template = dirname(__FILE__) . '/templates/archive-db_audio.php';
            if (file_exists($plugin_template)) {
                error_log('DBP Template gefunden und geladen!');
                return $plugin_template;
            } else {
                error_log('DBP Template nicht gefunden: ' . $plugin_template);
            }
        }
        return $template;
    });
});
