<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class archive_ctl_order extends desktop_controller{
    var $workground = "order_center";
    /**
     * 归档设置
     * @param  
     * @return 
     * @access  
     * @author sunjing@shopex.cn
     */
    function index()
    {
        
        $this->workground = 'setting_tools';
        $this->page("set.html");
    }
    
    /**
     * 归档查询
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function search()
    {
    
        if (empty($_POST['time_from'])) {
            $time_from = date("Y-m-1"); // 当月第一天
            $time_to   = date("Y-m-d", strtotime("-1 day")); // 昨天
        
            // 如果昨天早于当月第一天，则将结束时间设置为今天
            if (strtotime($time_to) < strtotime($time_from)) {
                $time_to = date("Y-m-d");
            }
        
            $_POST['time_from'] = $time_from;
            $_POST['time_to']   = $time_to;
        }
        //$_POST['flag'] = isset($_POST['flag']) ? $_POST['flag'] : 0;
        
        //check shop permission
        // $organization_permissions = kernel::single('desktop_user')->get_organization_permission();
        // if($organization_permissions){
        //     $_POST['org_id'] = $organization_permissions;
        // }

        kernel::single('archive_data')->set_params($_POST)->display();
    }

    
    
    
    function ajaxGetArchiveData()
    {
        $archivelib = kernel::single('archive_order');
        $orderfilter['status'] = $_GET['status'];
        $orderfilter['archive_time'] = $_GET['archive_time'];
        $this->pagedata['currentTime'] = time();
        $total = $archivelib->get_total($orderfilter);
        $this->pagedata['total'] = $total;
        $log_content = '开始归档:共计:'.$total.'条';
        $operObj = app::get('archive')->model('operation_log');
        $operObj->write_log($log_content,$archivelib->archivetimeFilter($_GET['archive_time']));
        $this->pagedata['params'] = http_build_query($orderfilter);
        $this->pagedata['pagenum'] = ceil($total/500);
        $activehouse = date('H');
        
        if ($activehouse<21 && $activehouse>9) {
           echo "当前时间不可以操作归档";
           exit;
        }
        $this->display('archive.html');
    }

    function saveset()
    {
      
        set_time_limit(0);
        $orderfilter = $_POST;
        $activehouse = date('H');
        $rs  = array('rsp'=>'succ','msg'=>'归档完成');
        
        $archivelib = kernel::single('archive_order');
        $data = $archivelib->get_order($orderfilter);
        echo json_encode($rs);
        
        
    }

    
    /**
     * 执行归档
     * @param  
     * @return 
     * @access  public
     * @author sunjing@shopex.cn
     */
    function ajaxDoAuto()
    {
        set_time_limit(0);
        $params = $_POST;
        $archivelib = kernel::single('archive_order');
        list($res,$msg) = $archivelib->process($params);
        echo $res;
    }
    
   
    
    /**
     * 格式化归档时间.
     * @param  
     * @return  
     * @access  public
     * @author sunjng@shopex.cn
     */
    function format_archivetime()
    {
        $archive_time = $_POST['archive_time'];
        
        switch($archive_time){
            case '1':
                $archive_time =  strtotime("-1 month");
            break;
            case '2':
                $archive_time =  strtotime("-2 month");
            break;
             case '3':
                $archive_time =  strtotime("-3 month");
            break;
            case '6':
                $archive_time =  strtotime("-6 month");
            break;
            case '9':
                $archive_time =  strtotime("-9 month");
            break;
            default:
                $archive_time =  strtotime("-12 month");
                break;
        }
        echo date('Y-m-d H:i:s',$archive_time);
    }

    
    function testshow(){
        $params = array(
                'title'=>'归档列表',
       
                'use_buildin_new_dialog' => false,
                'use_buildin_set_tag'=>false,
                'use_buildin_recycle'=>false,
                'use_buildin_export'=>false,
                'use_buildin_import'=>false,
                'use_buildin_filter'=>true,
                'use_view_tab'=>true,

                'finder_cols' => 'order_bn,shop_id,total_amount,column_print_status,process_status,is_cod,pay_status,ship_status,payment,shipping,logi_id,logi_no,createtime,paytime,mark_type',

           );

       $this->finder('archive_mdl_orders',$params);
   }


}

?>