<?php
add_action('wp_ajax_dbp_test_minimal', function() {
    error_log('[DBP][dbp_test_minimal] Handler wurde aufgerufen');
    wp_send_json_success(['minimal' => 1]);
});
