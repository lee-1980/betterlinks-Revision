<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e('BetterLinks', 'betterlinks-pro'); ?></title>
</head>
<body>
    <div class="betterlinks-email-report" style="background-color:#f3f7fa; margin:0;padding:50px;">
        <div style="width: 600px; margin: auto; background: #fff; padding: 30px;">
            <table style="border-collapse: collapse; width: 100.577%; height: 36px;" border="0">
                <tbody>
                    <tr style="height: 36px;">
                        <td style="width: 50%; height: 36px;">
                            <img class="betterlinks-email-logo" src="<?php echo esc_url($logo); ?>"  alt="BetterLinks" style="display: block"/>
                        </td>
                        <td style="width: 50%; height: 36px;" align="right">
                            <span style="color: #848484;"><?php esc_html_e('Your Broken Link Checker Report', 'betterlinks-pro'); ?></span><br />
                            <span style="color: #444444;"><?php echo $report_time; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <p style="margin: 50px 0 15px; color: #444444;">
                                <?php
                                    esc_html_e(sprintf('As of %1$s, you have a total of %2$s broken links on your website.', $report_time, $issue_found), 'betterlinks-pro')
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <table style="border-collapse: collapse; width: 100%;" border="0">
                                <tbody>
                                    <tr>
                                        <td style="width: 33.3333%; background-color:#e5ecf2;text-transform:uppercase;padding:10px 0px;font-size:14px; color: #444444; border: 1px solid #e5ecf2;" align="center"><strong><?php esc_html_e('Total Links', 'betterlinks-pro'); ?></strong></td>
                                        <td style="width: 33.3333%; background-color:#e5ecf2;text-transform:uppercase;padding:10px 0px;font-size:14px; color: #444444; border: 1px solid #e5ecf2;" align="center"><strong><?php esc_html_e('Links Scanned', 'betterlinks-pro'); ?></strong></td>
                                        <td style="width: 33.3333%; background-color:#e5ecf2;text-transform:uppercase;padding:10px 0px;font-size:14px; color: #444444; border: 1px solid #e5ecf2;" align="center"><strong><?php esc_html_e('Broken Links Found', 'betterlinks-pro'); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td style="width: 33.3333%; padding:10px 5px;font-size:26px color: #444444; border: 1px solid #e5ecf2;" align="center"><?php echo $total_links; ?></td>
                                        <td style="width: 33.3333%; padding:10px 5px;font-size:26px color: #444444; border: 1px solid #e5ecf2;" align="center"><?php echo $scan_links; ?></td>
                                        <td style="width: 33.3333%; padding:10px 5px;font-size:26px color: #444444; border: 1px solid #e5ecf2;" align="center"><?php echo $issue_found; ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="<?php echo esc_url($admin_brokenLink_url); ?>" style="display: inline-block; padding: 15px 30px; color: #fff; text-decoration: none; background: linear-gradient(202deg,#2961ff 0%,#003be2 100%); border-radius: 4px;" target="_blank"><?php esc_html_e('View Broken Link Checker', 'betterlinks-pro'); ?></a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="betterlinks-info">
            <p style="color: #444444;text-align: center;width: 70%;margin: auto;margin-top: 15px;">If you have any suggestion regarding BetterLinks Broken Link Checker, do not hesitate to reply to this mail.</p>
        </div>
    </div>
</body>
</html>