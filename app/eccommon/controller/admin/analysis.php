<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class eccommon_ctl_admin_analysis extends desktop_controller
{

    /**
     * chart_view
     * @return mixed 返回值
     */
    public function chart_view()
    {
         $show = $_GET['show'];

         //todo 这里需要根据不同的需求读取数据
         if($_GET['callback']){
             $data = kernel::single($_GET['callback'])->fetch_graph_data($_GET);
         }else{
             $data = kernel::single('eccommon_analysis_base')->fetch_graph_data($_GET);
         }

         $this->pagedata['categories']='["' . @join('","', $data['categories']) . '"]';
         $tmp = [];
         foreach($data['data'] AS $key=>$val){
             $tmp[] = '{name:"'.addslashes($key).'",data:['.@join(',', $val).']}';
         }
         $this->pagedata['data'] = '['.@join(',', $tmp).']';

         switch($show){
            case 'line':
                $this->display("analysis/chart_type_line.html");
                break;
            case 'column':
                $this->display("analysis/chart_type_column.html");
                break;
            default :
                $this->display("analysis/chart_type_default.html");
                break;
        }
    }//End Function

}//End Class