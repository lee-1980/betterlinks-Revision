<?php
namespace BetterLinks\Tools\Migration;

use BetterLinks\Interfaces\ImportOneClickInterface;

class S301ROneClick extends S30RBase implements ImportOneClickInterface
{
    public function run_importer($data)
    {
        return $this->process_links_data($data);
    }
}
