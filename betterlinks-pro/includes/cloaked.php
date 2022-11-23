<?php if(!defined('ABSPATH')) { wp_die('You are forbidden to visit this page.'); } ?>
<!DOCTYPE html>
<html>
  <head>
    <title><?php echo esc_html(wp_unslash($curr_item['link_title'])); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="description" content="<?php echo esc_attr(wp_unslash($curr_item['link_note'])); ?>" />
    <meta name="robots" content="noindex" />
    <?php wp_site_icon(); ?>
  </head>
  <body style="margin:0;" >
    <iframe class="btl-cloaked-url-root-body" style="display:block;border:none;height:100vh;width:100vw;" src="<?php echo esc_url($target_url); ?>"></iframe>
  <script>
    const siteUrl = "<?php echo site_url(); ?>";
    const targetUrl = "<?php echo esc_url($target_url); ?>";
    const cleanedSiteUrl = siteUrl.replace(/http\:\/\/s?/i, "").replace(/www\./i, "");
    const cleanedTargetUrl = targetUrl.replace(/http\:\/\/s?/i, "").replace(/www\./i, "");
    const isSameSite = cleanedTargetUrl.toLowerCase().includes(cleanedSiteUrl.toLowerCase());
    const rootFavicon = document.querySelector("link[rel='icon']");
    window.addEventListener("DOMContentLoaded", () => {
      if (!isSameSite || rootFavicon) {
        return false;
      }
      const iframe = document.querySelector("iframe.btl-cloaked-url-root-body");
      let x = 0;
      const intervalId = setInterval(() => {
        x++;
        const insideHeadHtml = iframe?.contentWindow?.document?.head?.innerHTML;
        console.log({
          insideHeadHtml
        });
        if (insideHeadHtml || x > 1000) {
          const favicon = iframe?.contentWindow?.document?.head?.querySelector("link[rel='icon']");
          document.head.append(favicon);
          clearInterval(intervalId);
        }
      }, 100);
    })
  </script>
  </body>
</html>
