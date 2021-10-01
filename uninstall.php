<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$option_name = ['api_token', 'domains'];
foreach ($option_name as $option) {
    delete_option($option);
}
