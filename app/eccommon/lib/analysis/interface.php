<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


interface eccommon_analysis_interface
{

    public function get_logs($time);

    public function set_params($params);

    public function set_extra_view($array);

    public function get_type();

    public function graph();

    public function rank();

    public function detail();

    public function finder();

    public function fetch();

    public function display($fetch=false);

}//End Class