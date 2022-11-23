<?php

namespace BetterLinks\Abstracts;

abstract class MigrationNotice
{
    /**
     * Initialize Hooks
     *
     * @since 1.2.4
     * @return void
     */
    abstract public static function init();

    /**
     * Showing Notice Output
     *
     * @since 1.2.4
     * @return void
     */
    abstract public function migration_notice();
    
    /**
     * Showing Notice Output
     *
     * @since 1.2.4
     * @return void
     */
    abstract public function deactive_notice();

    /**
     * Load Javascript for send ajax request
     *
     * @since 1.2.4
     * @return void
     */
    abstract public function admin_scripts();
}
