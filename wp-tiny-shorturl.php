<?php
/**
 * Plugin Name:       WordPress Tiny Short URL
 * Plugin URI:        https://codedaokysu.com
 * Description:       Enter a long URL to make a TinyURL with TinyURL API
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Hoang Quang
 * Author URI:        https://codedaokysu.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://codedaokysu.com/wp-tiny-shorturl/
 * Text Domain:       wp-tiny-shorturl
 * Domain Path:       /languages
 */

if (!function_exists('wptiny_enqueue_script')) {
    function wptiny_enqueue_script()
    {
        wp_enqueue_style('wp-tiny-style', plugin_dir_url(__FILE__) . 'css/wp-tiny.css');
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array('jquery'), null, true);
        wp_register_script('main_tiny', plugin_dir_url(__FILE__) . 'js/wp-tiny.js', array('jquery'), null, true);
        wp_localize_script('main_tiny', 'ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_script('main_tiny');
    }
    add_action('wp_enqueue_scripts', 'wptiny_enqueue_script');
}

if (!function_exists('wptiny_option_page')) {
    function wptiny_option_page()
    {
        add_submenu_page(
            'options-general.php',
            'WP Tiny Settings',
            'WP Tiny Settings',
            'manage_options',
            'wptiny-settings',
            'wptiny_ref_page_callback'
        );
    }
    add_action('admin_menu', 'wptiny_option_page');

    /**
     * Display callback for the submenu page.
     */
    function wptiny_ref_page_callback()
    {
        ?>
        <div class="wrap">
            <h1>WP Tiny General Settings</h1>
            <form action="#" method="post">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="api_token">API Token</label></th>
                            <td>
                                <input name="api_token" type="password" id="api_token" value="<?php echo get_option('api_token'); ?>" class="regular-text" />
                                <p class="description">
                                   This API is only availible to authorized users. It requires an API token that is used for http bearer authentication as described in the OpenAPI specification. To create an API token for TinyURL please login to your account and visit <a href="https://tinyurl.com/app/settings/api" target="_blank">Settings -> API</a>. This token should be kept a secret to prevent unathorized use of your account. If you loose your token or belive that it may have been stolen, please removed the old token and create a new one.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="domains">Domains</label></th>
                            <td>
                                <?php $domains = get_option('domains'); ?>
                                <select name="domains" id="domains">
                                    <option value="tinyurl.com" <?php selected($domains, 'tinyurl.com'); ?>>tinyurl.com</option>
                                    <!-- <option value="rotf.lol" <?php //selected($domains, 'rotf.lol'); ?>>rotf.lol</option> -->
                                    <!-- <option value="tiny.one" <?php //selected($domains, 'tiny.one'); ?>>tiny.one</option> -->
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="domains">Page Slugs</label></th>
                            <td>
                                <?php $page_slugs = get_option('page_slugs'); ?>
                                <textarea name="page_slugs" rows="10" class="regular-text"><?php echo $page_slugs; ?></textarea>
                                <p class="description">Enter your page slug with each page separated by commas(,)</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="usage">Usage</label></th>
                            <td>
                                <p class="description">Use shortcode <strong>[wptiny_form]</strong> in content page to show layout.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
            </form>
			<h2 class="title">Your recent TinyURLs</h2>
			<?php 
				$api_token = get_option('api_token');
				$api = 'https://api.tinyurl.com/urls/available?api_token=' .  $api_token;
				$headers = array(
					'Content-Type: application/json', 
					'accept: application/json'
				);

				$ch = curl_init($api);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$data = curl_exec($ch);
	
				if ($data === false) {
					// throw new Exception('Curl error: ' . curl_error($crl));
					print_r('Curl error: ' . curl_error($ch));
				}
				$data_decode = json_decode($data);
				$data_decode_arr = $data_decode->data;
				$my_urls = [];
				foreach ($data_decode_arr as $key => $value) {
					$my_urls[] = $value->tiny_url;
				}
				
				curl_close($ch);
			?>
			<textarea name="my-urls" id="my-urls" class="large-text" rows="3"><?php echo implode("\n", $my_urls); ?></textarea>
        </div>
        <?php
    }
}

if (!function_exists('wptiny_save_data_settings')) {
    add_action('admin_notices', 'wptiny_save_data_settings');
    function wptiny_save_data_settings()
    {
        $admin_page = get_current_screen();
        if ($admin_page->base == 'settings_page_wptiny-settings') {
            if (isset($_POST['submit'])) {
                if (!empty($_POST['api_token'])) {
                    update_option('api_token', $_POST['api_token']);
                }

                update_option('domains', $_POST['domains']);

                if (!empty($_POST['api_token'])) {
                    update_option('page_slugs', $_POST['page_slugs']);
                }
                echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
            }
        }
    }
}

if (!function_exists('wptiny_form')) {
    function wptiny_form()
    {
        $domains = get_option('domains');
        ob_start();
        ?>
            <div class="container">
               <div class="form-element">
                    <form action="" id="shorten-form" method="post">
                        <div class="form-group">
                            <label for="url">Shorten a long URL</label>
                            <input type="text" name="url" id="url" class="form-control" placeholder="Enter long link here">
                        </div>
                        <div class="form-group">
                            <label for="url">Customize your link</label>
                            <div class="input-group">
                                <strong><?php echo $domains; ?>/</strong><input type="text" name="alias" id="alias" class="form-control" placeholder="Enter alias">
                            </div>
                        </div>
                        <div class="form-group text-center">
                            <input type="submit" name="create_link" value="Shorten URL">
                        </div>
                    </form>
               </div>
            </div>
        <?php
        return ob_get_clean();
    }
    add_shortcode('wptiny_form', 'wptiny_form');
}

add_action('wp_ajax_short_link', 'short_link');
add_action('wp_ajax_nopriv_short_link', 'short_link');
function short_link()
{
    if ($_POST['action'] != 'short_link') {
        return;
    }
    //$domain = get_option('domains');
    $api_token = get_option('api_token');

    $result = [];
    $url = $_POST['url'];
    $alias = $_POST['alias'];

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $api = 'https://api.tinyurl.com/create?api_token=' .  $api_token;
    
        $data_shorten = array(
            'url' => $url,
            'domain' => 'tinyurl.com',
            'alias' => $alias
        );

        $headers = array(
            'Content-Type: application/json', 
            'accept: application/json'
        );

        $ch = curl_init($api);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_shorten));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        if ($data === false) {
            // throw new Exception('Curl error: ' . curl_error($crl));
            print_r('Curl error: ' . curl_error($ch));
        }
  
        curl_close($ch);
        $result['status'] = 'success';
        $result['data'] = $data;
    } else {
        $result['status'] = 'error';
        $result['data'] = 'Your url is not a valid URL';
    }
    
    die(json_encode($result));
}

// Donate link on manage plugin page
add_filter('plugin_row_meta', 'wptiny_pluginspage_links', 10, 2);
function wptiny_pluginspage_links($links, $file)
{
    $plugin = plugin_basename(__FILE__);
    if ($file == $plugin) {
        return array_merge(
            $links,
            array(
                '<a href="'.get_admin_url().'options-general.php?page=wptiny-settings" title="WP Tiny ShortURL Settings Page"><span class="dashicons dashicons-admin-generic"></span>'.__('Settings').'</a>',
                '<a href="https://www.buymeacoffee.com/codedaokysu" target="_blank" title="Donate for this plugin via Buy me a coffee">☕ Buy me a coffee</a>',
                '<a href="https://twitter.com/quangpro1610" target="_blank" title="Follow me on Twitter!"><span class="dashicons dashicons-twitter"></span> Twitter</a>'
            )
        );
    }
    return $links;
}


add_filter('body_class', 'wptiny_add_body_class_page_shortlink');
function wptiny_add_body_class_page_shortlink($classes)
{
    $page_slugs = get_option('page_slugs');
    $page_arr = explode(',', $page_slugs);
    $current_slug = get_post_field('post_name', get_the_ID());

    if (in_array($current_slug, $page_arr)) {
        $classes[] = 'wptiny-page';
    }

    return $classes;
}