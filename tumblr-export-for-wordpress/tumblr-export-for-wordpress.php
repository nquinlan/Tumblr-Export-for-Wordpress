<?php
/*
Plugin Name: Tumblr Export for Wordpress
Plugin URI: http://nicholasquinlan.com/tumblr-export-for-wordpress.html
Description: Export your WordPress blog to Tumblr. Very useful for migration to Tumblr. <a href="tools.php?page=tumblr-export">Export options can be found here.</a>
Version: 0.5
Author: Nick Quinlan
Author URI: http://nicholasquinlan.com
License: GPL2
*/

$tmbex_version = "0.5";

add_action('admin_menu', 'tmbex_add_to_tools');

function tmbex_add_to_tools() {
	add_management_page("Tumblr Export For WordPress", "Export to Tumblr", "export", "tumblr-export", "tmbex_admin_page" );
}

function tmbex_admin_page() {
	global $tmbex_version;
	if (!current_user_can('export'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	if(!$_GET['resp']){
		?>
<div class="wrap">
<!--
Hi There <?php 
global $current_user;
echo $current_user->display_name;
?>!
You're reading the code, that means I think you're pretty awesome. <?php /* Especially if you're reading the PHP code. */ ?>
This plugin sends all your blog posts through a page on my site, which acts as a wrapper to the Tumblr API in an effort to preserve my API key.
If you have a better way of doing this or anything else, or want to talk WordPress, PHP, or similarly nerdy things drop me an email: <nick@nicholasquinlan.com>.
Enjoy The Plugin!
--
Nick
-->
	<div id="icon-tools" class="icon32"><br /></div><h2>Tumblr Export For WordPress</h2>
	<p>When you click the Export button the export process will begin, you will be asked to give the plugin permission to Read & Write to your Tumblr.</p>
	<form action="http://etc.nicholasquinlan.com/Tumblr-Export-for-Wordpress/api/token.php" method="POST">
		<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="tumblr_blog_hostname">Tumblr Hostname</label></th>
			<td>
				<input name="tumblr_blog_hostname" type="text" id="tumblr_blog_hostname" class="regular-text" />
				<span class="description">This is where your Tumblr is located. (e.g. nquinlan.tumblr.com or example.com)</span>
			</td>
		</tr>
		<tr>
			<th scope="row">Attribution</th>
			<td>
				<fieldset><legend class="screen-reader-text"><span>Attribution</span></legend>
				<label title='none'><input type='radio' name='attribution' value='none' checked='checked' /> <span>None<br /><span class="description" style="margin-left: 25px;">Attribution is in no way required, although it is greatly appreciated.</span></span></label><br />
				<label title='eoe'><input type='radio' name='attribution' value='eoe' /> <span>Post After Export<br /><span class="description" style="margin-left: 25px;">When the export completes a post will be placed on your Tumblr saying "I just exported all my WordPress blog posts to Tumblr using <a href="http://nicholasquinlan.com/tumblr-export-for-wordpress.html" target="_blank">Tumblr Export for Wordpress</a> by <a href="http://nicholasquinlan.com" target="_blank">Nicholas Quinlan</a>."</span></span></label><br />
				<label title='eop'><input type='radio' name='attribution' value='eop' /> <span>At The End of Each Post<br /><span class="description" style="margin-left: 25px;">A line will be placed at the end of each post: "<a href="http://nicholasquinlan.com/tumblr-export-for-wordpress.html" target="_blank">Exported Using Tumblr Export for Wordpress</a>"</span></span></label><br />
				</fieldset>
			</td>
		</tr>
		</table>
		<input type="hidden" name="version" value="<?php echo $tmbex_version; ?>" />
		<input type="hidden" name="js" id="js-test" value="" />
		<input type="hidden" name="wpurl" value="<?php echo site_url(); ?>" />
		<input type="hidden" name="plugin_url" value="<?php echo $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]; ?>" />
		<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Export"  /></p>
	</form>
	<script type="text/javascript">
		jQuery(function () {
			jQuery("#js-test").val("yes");
		});
	</script>
</div>
		<?php
	}else{
		if($_GET['resp'] == 'error'){
			echo '<div class="wrap">';
			echo '<div id="icon-tools" class="icon32"><br /></div><h2>Tumblr Export For WordPress</h2>';
			echo strip_tags($_GET['error'],"<p><a><em><strong>");
			echo '</div>';
		}elseif($_GET['resp'] == 'begin'){
			if($_GET['js'] == "no" || TRUE){
				?>
<div class="wrap">
	<div id="icon-tools" class="icon32"><br /></div><h2>Tumblr Export For WordPress</h2>
	<p>Exporting post please wait...</p>
	<ul>
				<?php
					$published_posts = wp_count_posts()->publish;
					$allposts = get_posts(array(
						'numberposts' => $published_posts
					));
					$body_append = "";
					if($_GET['attr'] == "eop"){
						$body_append = '<div style="font-size: 10px;"><a href="http://nicholasquinlan.com/tumblr-export-for-wordpress.html" target="_blank">Exported Using Tumblr Export for Wordpress</a></div>';
					}
					foreach($allposts as $post){
						set_time_limit(15);
						
						
						$idents = array();
						$tag_string = "";
						$posttags = get_the_tags($post->ID);
						if($posttags){
							foreach($posttags as $tag){
								$idents[] = $tag->name;
							}
							
						}
						$postcat = get_the_category($post->ID);
						if($postcat){
							foreach($postcat as $cat){
								if($cat->name != "Uncategorized"){
									$idents[] = $cat->name;
								}
							}
						}
						$tag_string = implode(",",$idents);
						$prams = Array(
							"title" => $post->post_title,
							"body" => preg_replace("/<img[^>]+\>/i", "",$post->post_content) . $body_append,
							"slug" => $post->post_name,
							"date" => $post->post_date_gmt,
							"tags" => $tag_string,

							"key" => $_GET['key'],
							'plugin_url' => $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"],
							'wpurl' => site_url(),
							'version' => $tmbex_version
						);

						$curl_handle=curl_init();
						curl_setopt($curl_handle,CURLOPT_URL,'http://etc.nicholasquinlan.com/Tumblr-Export-for-Wordpress/api/post.php');
						curl_setopt ($curl_handle, CURLOPT_POST, 1);
						curl_setopt ($curl_handle, CURLOPT_POSTFIELDS, $prams);
						curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,4);
						curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
						$result = curl_exec($curl_handle);
						curl_close($curl_handle);
						$response = json_decode($result,TRUE);
						$tumblr_r = $response['tumblr_r'];
						if($tumblr_r['meta']['msg'] == "Created"){
							echo '<li><strong>' . $prams['title'] . '</strong> Created</li>';
						}
					}
					if($_GET['attr'] == "eoe"){
						set_time_limit(15);

						$prams = Array(
							"title" => "I Exported My Blog Using Tumblr Export for Wordpress",
							"body" => 'I just exported all my WordPress blog posts to Tumblr using <a href="http://nicholasquinlan.com/tumblr-export-for-wordpress.html" target="_blank">Tumblr Export for Wordpress</a> by <a href="http://nicholasquinlan.com" target="_blank">Nicholas Quinlan</a>.',
							"slug" => 'tumblr-export-for-wordpress',
							"tags" => "#TumblrExportForWordPress",

							"key" => $_GET['key'],
							'plugin_url' => $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"],
							'wpurl' => site_url(),
							'version' => $tmbex_version
						);

						$curl_handle=curl_init();
						curl_setopt($curl_handle,CURLOPT_URL,'http://etc.nicholasquinlan.com/Tumblr-Export-for-Wordpress/api/post.php');
						curl_setopt ($curl_handle, CURLOPT_POST, true);
						curl_setopt ($curl_handle, CURLOPT_POSTFIELDS, $prams);
						curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,4);
						curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
						$result = curl_exec($curl_handle);
						curl_close($curl_handle);
					
						$response = json_decode($result);
						$tumblr_r = $response['tumblr_r'];
						if($tumblr_r['meta']['msg'] == "Created"){
							echo '<li><strong>' . $prams['title'] . '</strong> Created</li>';
						}
					}
				?>
	</ul>
</div>
				<?php
			}
		}
	}
}

?>