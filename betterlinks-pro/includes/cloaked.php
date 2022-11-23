<?php if(!defined('ABSPATH')) { wp_die('You are forbidden to visit this page.'); } ?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo esc_html(wp_unslash($curr_item['link_title'])); ?></title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="description" content="<?php echo esc_attr(wp_unslash($curr_item['link_note'])); ?>" />
    <meta name="robots" content="noindex" />
  </head>
  <body style="margin:0;" >
    <iframe style="display:block;border:none;height:100vh;width:100vw;" src="<?php echo esc_url($target_url); ?>"></iframe>
  </body>
</html>
