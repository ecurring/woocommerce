<?php
if (defined('ABSPATH')) {
    return;
}

define('PROJECT_DIR', dirname(__DIR__));
define('ABSPATH', PROJECT_DIR . '/vendor/wordpress/wordpress/');
define('WPINC', 'wp-includes');

require_once ABSPATH . WPINC . '/plugin.php';
require_once ABSPATH . WPINC . '/load.php';
require_once ABSPATH . WPINC . '/functions.php';
require_once ABSPATH . WPINC . '/formatting.php';
require_once ABSPATH . WPINC . '/kses.php';
require_once ABSPATH . WPINC . '/wp-db.php';
require_once ABSPATH . WPINC . '/class-wp-post.php';
require_once ABSPATH . WPINC . '/class-wp-post-type.php';
require_once ABSPATH . WPINC . '/class-wp-error.php';
require_once ABSPATH . WPINC . '/class-wp-taxonomy.php';
require_once ABSPATH . WPINC . '/class-wp-user.php';
require_once ABSPATH . WPINC . '/class-wp-dependency.php';
require_once ABSPATH . WPINC . '/class-wp-comment.php';
require_once ABSPATH . WPINC . '/class.wp-dependencies.php';
require_once ABSPATH . WPINC . '/class.wp-scripts.php';
require_once ABSPATH . WPINC . '/class.wp-styles.php';
require_once ABSPATH . WPINC . '/class-wp-http-response.php';
require_once ABSPATH . WPINC . '/rest-api/class-wp-rest-request.php';
require_once ABSPATH . WPINC . '/rest-api/class-wp-rest-response.php';
require_once ABSPATH . WPINC . '/functions.wp-scripts.php';
require_once ABSPATH . WPINC . '/functions.wp-styles.php';
require_once ABSPATH . WPINC . '/post.php';
require_once ABSPATH . WPINC . '/comment.php';
require_once ABSPATH . WPINC . '/taxonomy.php';
require_once ABSPATH . WPINC . '/meta.php';
require_once ABSPATH . WPINC . '/general-template.php';
require_once ABSPATH . WPINC . '/link-template.php';
require_once ABSPATH . WPINC . '/l10n.php';
require_once ABSPATH . WPINC . '/functions.wp-scripts.php';
require_once ABSPATH . WPINC . '/functions.wp-styles.php';
require_once ABSPATH . WPINC . '/pluggable.php';
require_once ABSPATH . WPINC . '/capabilities.php';
require_once ABSPATH . WPINC . '/rest-api.php';
require_once ABSPATH . WPINC . '/class-wp-block-type.php';
require_once ABSPATH . WPINC . '/class-wp-block-type-registry.php';
require_once ABSPATH . WPINC . '/blocks.php';
require_once ABSPATH . WPINC . '/theme.php';
require_once ABSPATH . WPINC . '/post-thumbnail-template.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

require_once PROJECT_DIR . '/includes/ecurring/wc/plugin.php';

require_once PROJECT_DIR . '/vendor/woocommerce/action-scheduler/functions.php';
require_once PROJECT_DIR . '/vendor/woocommerce/woocommerce/includes/wc-order-functions.php';
