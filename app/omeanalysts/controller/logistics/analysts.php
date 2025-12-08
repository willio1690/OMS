<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class omeanalysts_ctl_logistics_analysts extends desktop_controller{
    
    /**
     * index
     * @return mixed 返回值
     */
    public function index(){
		

        if('false' == app::get('ome')->getConf('ome.delivery.hqepay')) {
            echo '<p>此功能需要开启物流跟踪</p>';return;
        }
        
        $typeObj = app::get('omeanalysts')->model('ome_type');
        
        
        $branch_list = $typeObj->get_branch();
       
        $this->pagedata['branch_list']= $branch_list;
    
        if(empty($_POST)){
            $this->pagedata['time_from'] = strtotime('-1 day');
            $this->pagedata['time_to'] = time();
            
        }else{
            $this->pagedata['time_from'] = strtotime($_POST['time_from']);
            $this->pagedata['time_to'] = strtotime($_POST['time_to']);
            $branch_id = $_POST['branch_id'];
            $this->pagedata['branch_id'] = $branch_id;
        }
        $this->pagedata['form_action'] = 'index.php?app=omeanalysts&ctl=logistics_analysts&act=index';
        $this->pagedata['path']= '仓储物流配送统计';
        $this->page('logistics/frame.html');
    }

    /**
     * 获取_map_data
     * @return mixed 返回结果
     */
    public function get_map_data() {
        $data = $_GET;
        $logisticsObj = app::get('omeanalysts')->model('logistics_analysts');

        $analysts_data = $logisticsObj->analysts_data($data);
        $analysts_map = array();
        $dlyCorpObj = &app::get('ome')->model('dly_corp');
        $corp_list  = $dlyCorpObj->getList('corp_id,name');
        foreach ($corp_list as $corp){
            $corp_list[$corp['corp_id']] = $corp['name'];
        }
        $tmp = array();
        $delivery_num_rows = array();
        $embrace_num_rows = array();
        $sign_num_rows = array();
        $problem_num_rows = array();
        $timeout_num_rows = array();
        $logi_rows  = array();
        foreach($analysts_data as $analysts){
            $logi_id = $analysts['logi_id'];
            $logi_rows[$logi_id] = $logi_id;
            $delivery_num_rows[] = $analysts['delivery_num'];
            $embrace_num_rows[] = $analysts['embrace_num'];
            $sign_num_rows[] = $analysts['sign_num'];
            $problem_num_rows[] = $analysts['problem_num'];
            $timeout_num_rows[] = $analysts['timeout_num'];
        }
        foreach($logi_rows as $row){
            $analysts_map[] = $corp_list[$row];
        }
        $tmp[] = '{name:"发货数",data:['.@join(',', $delivery_num_rows).']}';
        $tmp[] = '{name:"揽收数",data:['.@join(',', $embrace_num_rows).']}';
        $tmp[] = '{name:"签收数",data:['.@join(',', $sign_num_rows).']}';
        $tmp[] = '{name:"退件/问题件",data:['.@join(',', $problem_num_rows).']}';
        $tmp[] = '{name:"超时配送数",data:['.@join(',', $timeout_num_rows).']}';
        $this->pagedata['data'] = '['.@join(',', $tmp).']';
        $this->pagedata['categories']='["' . @join('","', $analysts_map) . '"]';
        $this->pagedata['data'] = '['.@join(',', $tmp).']';
        $this->display("logistics/chart_type_column.html");
    }

    private function getOperBranches(){
        $oBranch = app::get('ome')->model('branch');
        $is_super = kernel::single('desktop_user')->is_super();
        if (!$is_super) {
            $branch_ids = $oBranch->getBranchByUser(true);
            if (count($branch_ids)>0) {
                return $branch_ids;
            } else {
                return array(0);
            }
        }
    }
}