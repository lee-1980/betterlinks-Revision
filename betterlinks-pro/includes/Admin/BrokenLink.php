<?php
namespace BetterLinksPro\Admin;

class BrokenLink extends \WP_Background_Process
{
    use \BetterLinksPro\Traits\BrokenLinks;
    private static $instances = [];
    protected $action = 'betterlinkspro_background_brokenlink_checker';

    public function __construct()
    {
        parent::__construct();
    }

    public static function getInstance()
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }
        return self::$instances[$cls];
    }

    public function start_dispatch()
    {
        if (get_option($this->action)) {
            delete_option($this->action);
            return true;
        }
        return false;
    }
    public function doing_dispatch()
    {
        return get_option($this->action);
    }
    public function init()
    {
        add_option($this->action, true);
    }

    public function send_report_to_email()
    {
        $mail = $this->send_mail();
        return $mail;
    }

    /**
     * Task
     *
     * Override this method to perform any actions required on each
     * queue item. Return the modified item for further processing
     * in the next pass through. Or, return false to remove the
     * item from the queue.
     *
     * @param mixed $item Queue item to iterate over
     *
     * @return mixed
     */
    protected function task($item)
    {
        sleep(30);
        if (is_array($item)) {
            $this->check_broken_link($item);
        } elseif (is_string($item) && method_exists($this, $item)) {
            try {
                $this->$item();
            } catch (\Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    trigger_error('BetterLinks Pro background task triggered fatal error for callback ' . esc_html($item), E_USER_WARNING); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
                }
            }
        }
        return false;
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete()
    {
        parent::complete();
        // Show notice to user or perform some other arbitrary task...
    }
}
