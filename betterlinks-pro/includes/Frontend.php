<?php
namespace BetterLinksPro;

class Frontend
{
    public static function init()
    {
        $self = new self();
        $self->load_autolink_by_keywords();
    }
    public function load_autolink_by_keywords()
    {
        Frontend\AutoLinks::init();
    }
}
