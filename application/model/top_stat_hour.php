<?php
namespace application\model;

use \application\extend\model;

class top_stat_hour extends model
{
    function __config()
    {
        $db = $this->getConfig('db');
        return $db['ordoac'];
    }
}
