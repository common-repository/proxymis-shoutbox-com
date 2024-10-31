<?php
/*
Plugin Name: GetShoutbox
Description: Integrate a realtime shoutbox to your page or your post
Version: 1.0.0
Requires at least: 5.2
Requires PHP:      5.4
Author: Proxymis
Author URI: contact@proxymis.com
*/
function getShoutbox_getMenu()
{
	add_menu_page(
		'GetShoutbox',
		'GetShoutbox',
		'manage_options',
		'getshoutbox',
		'get_shoutbox_page_content',
		'dashicons-format-status',
		80 // Position in the menu
	);
}

add_action('admin_menu', 'getShoutbox_getMenu');

function get_shoutbox_page_content()
{
	$domain = parse_url(get_site_url())['host'];
	$token = hash('md5', $domain);
	$email = get_bloginfo('admin_email');
	$password = get_user_meta(get_current_user_id(), 'getshoutbox_password', true);
	ob_start(); ?>
	<h1>GetShoutbox</h1>
	<p>The getShoutbox is bound with your domain name: <b><a target="_blank" href="<?php echo esc_html($domain) ?>"><?php echo  esc_html($domain) ?></a></b></p>
	<p>Your Token is: <b><?php echo esc_html($token) ?></b></p>
	<p>Email account: <b><?php echo esc_html($email) ?></b></p>
	<p>Password account: <b><?php echo esc_html($password) ?></b></p>
	<hr>
	<p>
		Just insert the shortcode <input style="width: 300px;" title="copy to clipboard" onclick="copyToClipBoardShoutbox(event)" value="[getshoutbox width=240px height=320px]"> to your page/post (you can change the width and height)
	</p>
	<script>
		function copyToClipBoardShoutbox(e) {
			jQuery(e.currentTarget).select();
			document.execCommand('copy');
		}
	</script>
	<?php echo ob_get_clean();
}


class GetShoutbox
{
	private static $registerAccountUrl = 'https://www.shoutbox.com/chat/ajax.php';
	private static $noticeName = 'shoutbox-notice';

	public function __construct()
	{
		add_shortcode('getshoutbox', [$this, 'shortcode']);
		register_activation_hook(__FILE__, [$this, 'pluginActivated']);
	}

 function display_notice() {
	 $jsonString = get_transient(self::$noticeName);
	 if ($jsonString) {
		 $json = json_decode($jsonString);
		 $notice = $json->message . "<br>You can now access the plugin <a href='admin.php?page=getshoutbox'>GetShoutbox plugin</a>";
		 $class = ($json->status === 'ko') ? 'notice-error' : 'notice-success';
		 echo "<div id='message' class='notice". esc_html($class)." is-dismissible'>".esc_html($notice)."</div>";
		 update_user_meta( get_current_user_id(), 'getshoutbox_password', $json->password);
		 delete_transient(self::$noticeName);
	 }
	}

	public static function pluginActivated()
	{
		$domain = parse_url(get_site_url())['host'];
		$token = hash('md5', $domain);
		$email = get_bloginfo('admin_email');
		$params = [
			'a' => 'createAccountWP',
			'email' => $email,
			'token' => $token,
			'url' => $domain
		];
		$response = wp_remote_post( self::$registerAccountUrl,['body' => $params]);

		if ( is_wp_error( $response ) ) {
			 exit("ERROR".$response->get_error_message());
		} else {
			$json = json_decode($response['body']);
			if ($json->status==='ko') {
				exit($json->message);
			}
			set_transient(self::$noticeName, $response['body'], 5);
		}
	}

	public function shortcode($attributes)
	{
		$domain = parse_url(get_site_url())['host'];
		$token = hash('md5', $domain);
		$width = (isset($attributes['width'])) ? $attributes['width'] : '240px';
		$height = (isset($attributes['height'])) ? $attributes['height'] : '320px';
		ob_start(); ?>
		<iframe  style="border:none;width: <?php echo esc_html($width)?>;height:<?php echo esc_html($height)?>;" src="https://www.shoutbox.com/iframe.php?token=<?php echo esc_html($token)?>"></iframe>
		<?php return ob_get_clean();
	}
}

add_action('admin_notices', ['GetShoutbox', 'display_notice']);
new GetShoutbox();