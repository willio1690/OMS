<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */


class finance_mdl_bill extends dbeav_model{
    var $defaultOrder = array('create_time',' desc');
    var $has_export_cnf = true;
    var $export_name = '实收实退报表';
    
    /**
     * import_params
     * @param mixed $public public
     * @return mixed 返回值
     */
    public function import_params($public){
        return kernel::single('finance_io_bill_rule')->type( $public['importFiletype'] )->getParams($public);
    }

    /**
     * import_title
     * @return mixed 返回值
     */
    public function import_title(){
         return kernel::single('finance_io_bill_rule')->type( $this->get_import_filetype() )->getTitle();
    }

    /**
     * export_template
     * @return mixed 返回值
     */
    public function export_template(){
        return kernel::single('finance_io_bill_rule')->type('normal')->getTitle();
    }

    /**
     * import_line_rule
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function import_line_rule($row,$line){
        return kernel::single('finance_io_bill_rule')->type( $this->get_import_filetype() )->isFilterLine($row,$line);
    }

    /**
     * import_title_rule
     * @param mixed $row row
     * @param mixed $line line
     * @return mixed 返回值
     */
    public function import_title_rule($row,$line){
        return kernel::single('finance_io_bill_rule')->type( $this->get_import_filetype() )->isTitle($row,$line);
    }

    /**
     * structure_import_isGetItems
     * @return mixed 返回值
     */
    public function structure_import_isGetItems(){
        return true;
    }

    public function structure_import_data($row,&$format_row=array(),&$result){
        kernel::single('finance_io_bill_process')->type( $this->get_import_filetype() )->structure_import_data($this,$row,$format_row,$result);
    }

    /**
     * 检查ing_import_data
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回验证结果
     */
    public function checking_import_data($row,&$result){
        kernel::single('finance_io_bill_process')->type( $this->get_import_filetype() )->checking_import_data($this,$row,$result);
    }

    public $is_transaction = true; 
    /**
     * finish_import_data
     * @param mixed $row row
     * @param mixed $result result
     * @return mixed 返回值
     */
    public function finish_import_data($row,&$result){
        kernel::single('finance_io_bill_process')->type( $this->get_import_filetype() )->finish_import_data($this,$row,$result);
    }

    private function get_import_filetype(){
        return '';
    }
    
    /**
     * count
     * @param mixed $filter filter
     * @return mixed 返回值
     */
    public function count($filter=null){
         $filter_sql = $this->_filter($filter);
         $sql = 'SELECT count(*) FROM sdb_finance_bill WHERE '.$filter_sql;
         $tmp = $this->db->count($sql);
         return $tmp;
    }

    public function getList($cols='*', $filter=array(), $offset=0, $limit=-1, $orderType=null){

        $orderType = $orderType ? $orderType : $this->defaultOrder; 
        $filter_sql = $this->_filter($filter);
        $sql = 'SELECT '.$cols.' FROM sdb_finance_bill WHERE '.$filter_sql;
        if ($orderType){
            $sql .= ' ORDER BY '.(is_array($orderType) ? implode(' ', $orderType) : $orderType);
        }
        $data = $this->db->selectLimit($sql,$limit,$offset);
        return $data;
    }

    /**
     * 获取_Schema
     * @return mixed 返回结果
     */
    public function get_Schema()
    {
        #费用账单去除 核销状态 已核销金额 未核销金额
        if($_GET['app'] == 'finance' && $_GET['ctl']=='bill' && $_GET['act'] == 'index'){
            $data = parent::get_Schema();
            $data['columns']['status']['filtertype'] = '';
            $data['columns']['status']['filterdefault'] = false;

            foreach($data['in_list'] as $k=>$v){
                if(in_array($v,array('status','unconfirm_money','confirm_money'))){
                    unset($data['in_list'][$k]);
                }
            }
            foreach($data['deafult_in_list'] as $k1=>$v1){
                if(in_array($v1,array('status','unconfirm_money','confirm_money'))){
                    unset($data['deafult_in_list'][$k1]);
                }
            }
            return $data;
        }else{
            return parent::get_Schema();
        }
    }

    /**
     * _filter
     * @param mixed $filter filter
     * @param mixed $tableAlias tableAlias
     * @param mixed $baseWhere baseWhere
     * @return mixed 返回值
     */
    public function _filter($filter,$tableAlias=null,$baseWhere=null){

        #发货时间
        if(isset($filter['time_from']) && $filter['time_from']){
            $where .= ' AND trade_time >='.strtotime($filter['time_from']);
            unset($filter['time_from']);
        }
        if(isset($filter['time_to']) && $filter['time_to']){
            $where .= ' AND trade_time <'.(strtotime($filter['time_to'])+86400);
            unset($filter['time_to']);
        }
        #费用项ID
        if(isset($filter['fee_item_id'])){
            if(!empty($filter['fee_item_id'])){
                unset($filter['fee_type_id']);
            }else{
                unsest($filter['fee_item_id']);
                if(isset($filter['fee_type_id']) && empty($filter['fee_type_id'])){
                    unset($filter['fee_type_id']);
                }else{
                    unset($filter['fee_item_id']);
                    if(isset($filter['fee_type_id']) && empty($filter['fee_type_id'])){
                        unset($filter['fee_type_id']);
                    }
                }
            }
        }

        if(isset($filter['order_bn']) and 'unmatch' == $filter['order_bn'])
        {
            $where .= " AND order_bn = '' ";
            unset($filter['order_bn']);
        }

        return parent::_filter($filter,$tableAlias,$baseWhere).$where;
    }
    
    //配置信息
    /**
     * export_params
     * @return mixed 返回值
     */
    public function export_params(){
        $export_filter = $this->export_filter;
        if ($filter = unserialize($_POST['params'])) {
            $dates = $filter['time_from'].'至'.$filter['time_to'];
        }
        if($export_filter['order_bn']) $filter['order_bn'] = $export_filter['order_bn'];
        if($export_filter['credential_number']) $filter['credential_number'] = $export_filter['credential_number'];
        if(empty($export_filter['_params']['fee_item_id'])) unset($filter['fee_item_id']);
        if(empty($export_filter['_params']['fee_type_id'])) unset($filter['fee_type_id']);
        $params = array(
            'filter' => $filter,
            'limit' => 2000,
            'get_data_method' => 'get_bill_data',
            'single'=> array(
                'bill'=> array(
                    'filename' => $dates.'账单明细导出',
                ),
            ),
        );
        return $params;
    }

    //商品销售汇总
    /**
     * 获取_bill_data
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $limit limit
     * @param mixed $data 数据
     * @return mixed 返回结果
     */
    public function get_bill_data($filter,$offset,$limit,&$data){
        $billObj = $this->app->model('bill');
        $billdata = $billObj->getList('*',$filter,$offset,$limit);
        if(!empty($billdata)){
            foreach($billdata as $v){
                if($v['money'] > 0){
                    $v['money_in'] = $v['money'];
                }else{
                    $v['money_out'] = $v['money'];
                }
                $data['bill'][] = array(
                    '*:订单号' => $v['order_bn'],
                    '*:日期' => date("Y-m-d H:i:s",$v['trade_time']),
                    '*:费用类' => $v['fee_type'],
                    '*:费用项' => $v['fee_item'],
                    '*:费用对象' => $v['fee_obj'],
                    '*:收入金额' => $v['money_in'] ? $v['money_in'] : '',
                    '*:支出金额' => $v['money_out'] ? $v['money_out'] : '',
                    '*:凭据号' => $v['credential_number'],
                );
            }
        }
    }

    //重载记账状态，展示
    function modifier_charge_status($row){
        return kernel::single('finance_bill')->get_name_by_charge_status($row);
    }

    //重载月结状态，展示
    function modifier_monthly_status($row){
        return kernel::single('finance_bill')->get_name_by_monthly_status($row);
    }

    //重载核销状态，展示
    function modifier_status($row){
        return kernel::single('finance_bill')->get_name_by_status($row);
    }

    //重载单据类型
    function modifier_bill_type($row){
        return kernel::single('finance_bill')->get_name_by_bill_type($row);
    }


    /*
    **获取费用类费用项的关联关系
    **@params $flag 标识，空为所有关系，sale是销售相关，unsale为非销售相关
    **@return array('fee_type_id'=>array('name'=>'费用类的名称','fee_item'=>array('fee_item_id'=>'费用项名称')));
    */

    public function get_fee_type_item_relation($flag = 'all'){
        $db = kernel::database();
        if($flag == 'sale'){
            $sql = "select fi.fee_item_id,fi.fee_item,ft.fee_type,ft.fee_type_id from sdb_finance_bill_fee_item as fi left join sdb_finance_bill_fee_type as ft on ft.fee_type_id = fi.fee_type_id where ft.fee_type_id = '1' and fi.delete = 'false'";
        }else if($flag == 'unsale'){    
            $sql = "select fi.fee_item_id,fi.fee_item,ft.fee_type,ft.fee_type_id from sdb_finance_bill_fee_item as fi left join sdb_finance_bill_fee_type as ft on ft.fee_type_id = fi.fee_type_id where ft.fee_type_id != '1' and fi.delete = 'false'";
        }else{
            $sql = "select fi.fee_item_id,fi.fee_item,ft.fee_type,ft.fee_type_id from sdb_finance_bill_fee_item as fi left join sdb_finance_bill_fee_type as ft on ft.fee_type_id = fi.fee_type_id where fi.delete = 'false'";
        }
        $data = $db->select($sql);
        $res = array();
        foreach ($data as $key => $value) {
            $res[$value['fee_type_id']]['name'] = $value['fee_type']; 
            $res[$value['fee_type_id']]['fee_item'][$value['fee_item_id']] = $value['fee_item'];
        }
        return $res;
    }

    /*
    **通过实收账单id获取单号相同，或关联单号等于单号，或交易对方（member）相同的实收单据
    **@params $bill_id 实收账单单据ID
    **@params $flag 空 表示三种单据  order_bn表示只有单据号相同  member 表示交易对方相同 bill_id 表示单个
    **@return array() 应收单据的相关信息
    */

    public function get_bill_by_bill_id($bill_id,$flag='all'){
        $db = kernel::database();
        $cols = $flag == 'bill_id' ? 'credential_number,fee_type,order_bn,member,fee_type_id,money,trade_time,unique_id,channel_id,fee_item,bill_id' : 'member,crc32_order_bn,order_bn';
        $data = $this->getList($cols,array('bill_id'=>$bill_id));
        if(empty($data)) return false;
        $base_filter = " and fee_type_id = 1 and status != 2";
        $member = $data[0]['member'];
        $crc32_order_bn = $data[0]['crc32_order_bn'];
        $cols = 'bill_id,bill_bn,member,order_bn,trade_time,credential_number,fee_obj,fee_item,money,unconfirm_money,confirm_money,charge_status,crc32_order_bn';
        if($flag == 'order_bn'){
            $sql = "select ".$cols." from sdb_finance_bill where crc32_order_bn = '".$crc32_order_bn."'".$base_filter;
            $data = $db->select($sql);
            $res = array();
            foreach($data as $k=>$v){
                if($data[0]['order_bn'] == $v['order_bn']){
                    $res[] = $v;
                }
            }
        }else if($flag == 'member'){
            $sql = "select ".$cols." from sdb_finance_bill where member = '".$member."'".$base_filter;
            $res = $db->select($sql);
        }else if($flag == 'bill_id'){
            $mdlBase = app::get('financebase')->model('bill');
            foreach ($data as $k => $v) {
                $base_info = $mdlBase->getOneByBase($v['channel_id'],$v['unique_id']);
                $data[$k]['remarks'] = $base_info['remarks'];
            }
            $res = $data;
        }else{
            $sql = "select ".$cols." from sdb_finance_bill where (crc32_order_bn = '".$crc32_order_bn."' or member = '".$member."')".$base_filter;
            $res = $db->select($sql);
        }
        return $res;
    }

    /*
    **实收账单核销更新
    **@params $data array('0'=>array('bill_id'=>'','unconfirm_money'=>''))
    **@params $money 核销金额（整笔交易核销总金额）
    **@return array() 应收单据的相关信息
    */

    public function do_verificate($data,$money){
        $tmp = array();
        $db = kernel::database();
        foreach ($data as $key=>$value) {
          $tmp[$value['bill_id']] = abs($value['unconfirm_money']);
          if($value['unconfirm_money'] < 0) $tmp_flag = true;
        }
        asort($tmp);
        $standard_money = abs($money);
        foreach($tmp as $b_id=>$b_money){
            if($b_money >= $standard_money){
                #金额全为正 或者 全为负 操作符判断
                if($tmp_flag == true){
                  $operator_con = "-";
                  $operator_uncon = "+";
                }else{
                  $operator_con = '+';
                  $operator_uncon = '-';
                }
                $update_bill = "update sdb_finance_bill set confirm_money = (confirm_money ".$operator_con."'".$standard_money."'),unconfirm_money = (unconfirm_money ".$operator_uncon." '".$standard_money."'),status = 1 where bill_id = '".$b_id."'";
                if(!$db->exec($update_bill)){
                    $rs_flag = true;
                    break;
                }
                break;
            }else{
                $update_bill = "update sdb_finance_bill set confirm_money = money,unconfirm_money =0 ,status = 2,verification_time=".time()." where bill_id = '".$b_id."'";
                if(!$db->exec($update_bill)){
                    $rs_flag = true;
                    break;
                }
                $standard_money = abs($standard_money-$b_money);
            }
        }
        if($rs_flag == true){
            return false;
        }
        return true;
    }

    /*
    **撤销核销
    **@params $id 主键id
    **@params $money 撤销金额
    **@return ture/false 字符串
    */
    /**
     * do_cancel
     * @param mixed $id ID
     * @param mixed $money money
     * @return mixed 返回值
     */
    public function do_cancel($id,$money){
        $data = $this->getList('confirm_money,unconfirm_money',array('bill_id'=>$id));
        $update['confirm_money'] = $data[0]['confirm_money'] - $money;
        $update['unconfirm_money'] = $data[0]['unconfirm_money'] + $money;
        $update['verification_time'] = 0;#完全核销时间 置为0
        if($update['confirm_money'] == 0){
            $update['status'] = 0;#未核销
        }else{
            $update['status'] = 1;#部分核销
        }
        $filter= array('bill_id'=>$id);
        if(!$this->update($update,$filter)){
            return 'false';
        }
        return 'true';
    }

    /*
    **通过order_bn获取应收账单数据
    **@params $order_bn 订单号
    **@return array()
    */

    public function get_bill_by_order_bn($order_bn){
        if(empty($order_bn)) return false;
        $crc32_order_bn = sprintf('%u',crc32($order_bn));
        $rs = $this->getList('*',array('crc32_order_bn'=>$crc32_order_bn));
        $data = array();
        if($rs){
            foreach($rs as $k=>$v){
                if($v['order_bn'] == $order_bn){
                    $data[] = $v;
                }
            }
            return $data;
        }else{
            return false;
        }
    }

    /**
     * undocumented function
     * 
     * @return void
     * @author 
     * */
    public function exportName(&$filename,$filter)
    {
        return $filename['name'] = "实收实退单";
    }

    /**
     * 导出模板额外的选项
     * 
     * @return void
     * @author 
     * */
    public function export_input()
    {
        $ui = kernel::single('base_component_ui');
        $input[0]['label'] = '导出模版';
        $input[0]['params'] = array('type'=>array('ar'=>'销售应收账单','normal'=>'销售实收账单'),'name'=>'filter[salesExportType]','required'=>true);
        $input[0]['input'] = $ui->input($input[0]['params']);

        return $input;
    }

    /**
     * 导入数据额外选项
     * 
     * @return void
     * @author 
     * */
    public function import_input()
    {
        $ui = kernel::single('base_component_ui');
        $input[0]['label'] = '文件类型';
        $input[0]['params'] = array('type'=>array(
                                        'normal' => '销售实收账单',
                                        'ar'=>'销售应收账单',
                                        'jingdong_tuotou' => '京东妥投账单',
                                        'jingdong_tuihuo' => '京东退货账单',
                                        'jingdong_jushou' => '京东拒收账单',
                                        'yihaodian' => '1号店账单',
                                        'zhifubao' => '支付宝账单',
                                        ),
                                    'name'=>'filter[importFiletype]',
                                    'required' => true,
                                    'onchange' => "javascript:if(this.value=='ar'){this.form.action='index.php?app=omecsv&ctl=admin_to_import&act=treat&ctler=finance_mdl_ar&add=finance'}else{this.form.action='index.php?app=omecsv&ctl=admin_to_import&act=treat&ctler=finance_mdl_bill&add=finance'}",
                                    );
        $input[0]['input'] = $ui->input($input[0]['params']);

        return $input;
    }


      function io_title( $filter=null,$ioType='csv' ){
        $new_titles['main'] = array(
                'bill_bn' => '*:单据编号',
                'channel_name' => '*:渠道名称',
                'trade_time'=> '*:账单日期',
                'order_bn'=> '*:业务订单号',
                'credential_number'=> '*:凭据号',
                'member'=> '*:客户/会员',
                'fee_type'=> '*:费用项',
                'money'=> '*:费用金额',
                'confirm_money'=> '*:已核销金额',
                'unconfirm_money'=> '*:未核销金额',
                'verification_status'=> '*:核销状态',
            );

            return $new_titles;
     }

         /**
     * fgetlist_csv
     * @param mixed $data 数据
     * @param mixed $filter filter
     * @param mixed $offset offset
     * @param mixed $export_type export_type
     * @return mixed 返回值
     */
    public function fgetlist_csv(&$data,$filter,$offset,$export_type){
         $this->export_flag = true;
         $titles = $this->io_title();
       
        set_time_limit(0); // 30分钟
        $max_offset = 1000; // 最多一次导出10w条记录
        if ($offset>$max_offset) return false;// 限制导出的最大页码数

        if( !$data['title']['main'] ){
            $title = array();
            foreach(  $titles['main'] as $k => $v ){
                $title[] = $this->charset->utf2local($v);
            }
            $data['title']['main'] = '"'.implode('","',$title).'"';
        }

        $verification_status_ref = array(0=>'等待核销',1=>'正常核销',2=>'差异核销',3=>'强制核销');

        $limit = 100;
        if( !$list=$this->getList('bill_id',$filter,$offset*$limit,$limit) )return false;
        $rowDate = array();
        foreach($list as $v){
            $data_ar = $this->dump($v['bill_id']);
            // $addon = unserialize($data_ar['addon']);
            // $type = $obj_ar->get_name_by_type($data_ar['type']);
            // $status = $obj_ar->get_name_by_status($data_ar['status']);
            // $monthly_status = $obj_ar->get_name_by_monthly_status($data_ar['monthly_status']);
            // $charge_status = $obj_ar->get_name_by_charge_status($data_ar['charge_status']);
            

            
            $rowDate['*:单据编号'] = $data_ar['bill_bn'];
            $rowDate['*:渠道名称'] = $data_ar['channel_name'];
            $rowDate['*:账单日期'] = date('Y-m-d',$data_ar['trade_time']);
            $rowDate['*:业务订单号'] = "\t".$data_ar['order_bn'];
            $rowDate["*:客户/会员"] = $data_ar['member'];
            $rowDate['*:费用项'] = $data_ar['fee_type'];
            $rowDate['*:费用金额'] = number_format($data_ar['money'],2);
            $rowDate['*:已核销金额'] = number_format($data_ar['confirm_money'],2);
            $rowDate['*:未核销金额'] = number_format($data_ar['unconfirm_money'],2);
            $rowDate['*:核销状态'] = $verification_status_ref[$data_ar['verification_status']];
            // $rowDate['*:运费收入'] = number_format($addon['fee_money'],2);
            // $rowDate['*:应收金额'] = $data_ar['money'];
            // $rowDate['*:核销状态'] = $status;
            // $rowDate['*:已核销金额'] = $data_ar['confirm_money'];
            // $rowDate['*:未核销金额'] = $data_ar['unconfirm_money'];
            // $rowDate['*:记账状态']=  $charge_status;
            // $rowDate['*:记账日期'] = date('Y-m-d',$data_ar['charge_time']);
            // $rowDate['*:月结状态'] = $monthly_status;
            // $rowDate['*:业务流水号'] = "\t".$data_ar['serial_number'];
            

            $data['content']['main'][] = $this->charset->utf2local('"'.implode( '","', $rowDate ).'"');
        }
        return true;
    } 

    function export_csv($data,$exportType = 1 ){
        $output = array();
        foreach( $data['title'] as $k => $val ){
            $output[] = $val."\n".implode("\n",(array)$data['content'][$k]);
        }
        echo implode("\n",$output);
    }  

    /**
     * 导出数据
     *
     * @param Array $data 导出数据
     * @param Array $filter 过滤条件
     * @param Int $offset 下标
     * @param Int $export_type 导出类型 (用途不名)
     * @return void
     * @author 
     **/
    public function fgetlist(&$data,$filter,$offset,$export_type) 
    {
        if ($offset == 0 ) {
            #$title = $this->io_title('main');
            #$data['main'][] = $title;
            $titles = kernel::single('finance_io_bill_rule')->type($filter['salesExportType'])->getTitle();
            foreach ((array)$titles as $key => $title) {
                $data[$key][] = array_values($title);
            }

            return true;
        }
        if ($filter['template'] == 1) {
            return false;
        }

        return false;
    }

    /**
     * 导入前初始化
     *
     * @return void
     * @author 
     **/
    public function prepared_import_csv()
    {

    }

    /**
     * 表面数据验证
     *
     * @param Array $data 一行数据
     * @param Array $title 标题
     * @param mixed $tmpl 未知
     * @param String $mark 类型 such as title or contents
     * @param Bool $newObjFlag 是否数据库层验证
     * @param Array $msg 错误信息
     * @return void
     * @author 
     **/
    public function prepared_import_csv_row($row,&$title,&$tmpl,&$mark,&$newObjFlag,&$msg)
    {
        if (empty($row)) return false;

        static $rowLimit;

        $rowLimit++;
        
        # 数量限制
        if((int)$rowLimit > 5000){
            $msg['error'] = "导入的数据量过大，请减少到5000单以下！"; $newObjFlag = true;
            return false;
        }

        $initTime = app::get('finance')->getConf('finance_setting_init_time');
        if ($initTime['flag'] == false) {
            $msg['error'] = '请先设置帐期！';$newObjFlag = true;
            return false;
        }
       
        $importFiletype = $this->import_filter['importFiletype'];
        
        # 返回的是一个二维数组
        $obj = kernel::single('finance_io_bill_process')->type( $importFiletype );
   
        if (!$obj) {
            $msg['error'] = "导入文件类型错误！"; $newObjFlag = true;
            return false;
        }

        $row = array_map('trim', $row);

        $sdf = $obj->getSDf($this,$row,$mark);
        if (!$sdf){
            if ($this->import_totalRows == $rowLimit && !$mark) {
                $msg['error'] = '导入文件模板错误！';
            }

            return false; 
        }

        if ($mark == 'title') {
            return $sdf; 
        }

        static $unique_id_list;
        
        foreach ($sdf as $k => $s) {
            if (is_numeric($s['money'])) {
                $s['money'] = $s['money'] ? round($s['money'],2) : 0; 
            }
            if (false !== array_search($s['unique_id'],(array)$unique_id_list)) {
                $msg['error'] = "同一时间存在多张相同订单"; $newObjFlag = true;
                return false;
            }
            $unique_id_list[] = $s['unique_id'];

            # BEGIN 订单、金额格式、时间格式、帐期验证 BEGIN #
            $result = finance_io_bill_verify::checkEmpty($s);
            if ($result['status'] == 'fail') {
                $msg['warning'][] = 'LINE '.$rowLimit.'：'.$result['msg'];

                unset($sdf[$k]); continue;
            }

            $result = finance_io_bill_verify::checkOrder($s['order_bn']);
            if ($result['status'] == 'fail') {
                if (!$tmpl['msg']['orderError'][$rowLimit]) {
                    $msg['warning'][] = 'LINE '.$rowLimit.'：单号【'.$s['order_bn'].'】不存在！';
                    $tmpl['msg']['orderError'][$rowLimit] = 1;
                }

                unset($sdf[$k]); continue;
            }
            $sdf[$k]['channel_id'] = $result['order']['shop_id'];
            $sdf[$k]['channel_name'] = $result['order']['shop_name'];

            $result = finance_io_bill_verify::isPrice($s['money']);
            if ($result['status'] == 'fail') {
                $msg['warning'][] = 'LINE '.$rowLimit.'：单号【'.$s['order_bn'].'】的'.$s['fee_item'].'格式错误！';

                unset($sdf[$k]); continue;
            }
            $sdf[$k]['money'] = $s['money'] ? round($s['money'],2) : 0; 
            
            $trade_time = strtotime($s['trade_time']);
            $s['trade_time'] = $trade_time ? date('Y-m-d H:i:s') : '';
            $result = finance_io_bill_verify::isDate($s['trade_time']);
            if ($result['status'] == 'fail') {
                if (!$tmpl['msg']['tradeTimeError'][$rowLimit]) {
                    $msg['warning'][] = 'LINE '.$rowLimit.'：单号【'.$s['order_bn'].'】的交易时间格式错误！';
                    $tmpl['msg']['tradeTimeError'][$rowLimit] = 1;
                }
                

                unset($sdf[$k]); continue;
            }

            $result = finance_io_bill_verify::checkInitTime($s['trade_time'],$this->import_filter['checkTime']);
            if ($result['status'] == 'fail') {
                if (!$tmpl['msg']['initTimeError'][$rowLimit]) {
                    $msg['warning'][] = 'LINE '.$rowLimit.'：'.$result['msg'];
                    $tmpl['msg']['initTimeError'][$rowLimit] = 1;
                }
                
                unset($sdf[$k]); continue;
            }

            $result = finance_io_bill_verify::checkFee($s['fee_item']);
            if ($result['status'] == 'fail') {
                $msg['warning'][] = 'LINE '.$rowLimit.'：费用项【'.$s['fee_item'].'】不存在！';
                
                unset($sdf[$k]); continue;
            }
            $sdf[$k]['fee_item_id'] = $result['fee']['fee_item_id'];
            $sdf[$k]['fee_type_id'] = $result['fee']['fee_type_id'];
            $sdf[$k]['fee_type']    = $result['fee']['fee_type'];

            $result = finance_io_bill_verify::checkUniqueId($s['unique_id']);
            if ($result['status'] == 'fail') {
                $msg['warning'][] = 'LINE '.$rowLimit.'：【'.$s['order_bn'].'】该单据号已经存在！';

                unset($sdf[$k]); continue;
            }
            # END 订单、金额格式、帐期验证 END #　

            # BEGIN 数据整理 BEGIN #
            $fee_obj_id = kernel::single('finance_bill')->set_fee_obj_id($s['fee_obj']);
            $sdf[$k]['fee_obj_id'] = $fee_obj_id;

            $sdf[$k]['bill_bn']         = kernel::single('finance_bill')->gen_bill_bn();
            $sdf[$k]['create_time']     = time();
            $sdf[$k]['crc32_order_bn']  = sprintf('%u',crc32($s['order_bn']));
            $sdf[$k]['memo']            = serialize($s['memo']);
            $sdf[$k]['unconfirm_money'] = $s['money'];
            $sdf[$k]['trade_time']      = $s['trade_time'] ? strtotime($s['trade_time']) : time();
            # END 数据整理 END #
        }

        if(empty($sdf)) return false;

        $this->import_data = array_merge((array)$this->import_data,$sdf);

        return true;
    }

    /**
     * 数据库层验证
     *
     * @return void
     * @author 
     **/
    public function prepared_import_csv_obj($data,$mark,$tmpl,&$msg)
    {
        return NULL;
    }

    /**
     * 导入结束操作
     *
     * @return void
     * @author 
     **/
    public function finish_import_csv()
    {
        $oQueue = app::get('base')->model('queue');

        $queueData = array(
            'queue_title'=>'单据导入',
            'start_time'=>time(),
            'params'=>array(
                'sdfdata'=>$this->import_data,
                'app' => 'finance',
                'mdl' => 'bill',
            ),
            'worker'=>'finance_mdl_bill.import_run',
        );
        $oQueue->save($queueData);

        $oQueue->flush();
    }

    /**
     * 实际导入操作
     *
     * @return void
     * @author 
     **/
    public function import_run(&$cursor_id,$params)
    {
        #$model = app::get($params['app'])->model($params['mdl']);
        $count = count($params['sdfdata']);

        if ($count > 0) {
            # 一次导入600条
             $offset = 0; $limit = 600;
            do {
                if($offset>=$count) break;
                
                $arr = array_slice($params['sdfdata'], $offset,$limit);
                foreach ($arr as &$value) {
                    #用费用对象名称做crc32 维护Kv 以便查询
                    $fee_obj_id = kernel::single('finance_bill')->set_fee_obj_id($value['fee_obj']);
                    $value['fee_obj_id'] = $fee_obj_id;

                    $value['bill_bn'] = $this->gen_bill_bn();
                }

                $sql = ome_func::get_insert_sql($this,$arr);

                $this->db->exec($sql);

                $offset += $limit;
            } while ( true );
        }

        return false;
    }

     /*
     *生成账单bn
     */

    public function gen_bill_bn(){
        $prefix = "RB".date("YmdHis");
        $sign = kernel::single('eccommon_guid')->incId('finance_bill', $prefix, 7, true);
        return $sign;
        /*$i = rand(0,99999);
        do{
           if(99999==$i){
                $i=0;
           }
           $i++;
           $bill_bn="RB".date('Ymd').str_pad($i,5,'0',STR_PAD_LEFT);
           $row = $this->getlist('bill_id',array('bill_bn'=>$bill_bn));
        }while($row);
        return $bill_bn;*/
    }

    /**
     * 获得日志类型(non-PHPdoc)
     * @see dbeav_model::getLogType()
     */
    public function getLogType($logParams) {
        $type = $logParams['type'];
        $logType = 'none';
        if ($type == 'export') {
            $logType = $this->exportLogType($logParams);
        }
        elseif ($type == 'import') {
            $logType = $this->importLogType($logParams);
        }
        return $logType;
    }
    /**
     * 导出日志类型
     * @param Array $logParams 日志参数
     */
    public function exportLogType($logParams) {
        $params = $logParams['params'];
        $type = 'finance';
        if ($logParams['app'] == 'finance' && $logParams['ctl'] == 'bill') {
            $type .= '_saleReceipts_return';
        }
        elseif ($logParams['app'] == 'omecsv' && $logParams['ctl'] == 'admin_export') {
            if ($params['add'] == 'finance' && $params['filter']['template'] == 1) {
//                $type .= '_revExpen_bill_template';
                $type .= '_billTemplate';
            }
        }
        $type .= '_export';
        return $type;
    }
    /**
     * 导入操作日志类型
     * @param Array $logParams 日志参数
     */
    public function importLogType($logParams) {
        $params = $logParams['params'];
        $type = 'finance';
        if ($params['add'] == 'finance' && $params['filter']['checkTime'] == 'after') {
            //销售应收单
            $type .= '_revExpen_bill_billConfirm';
        }
        elseif ($params['add'] == 'finance' && $params['filter']['checkTime'] == 'before') {
            $type .= '_financeSet_startInit_bill';
        }
        $type .= '_import';
        return $type;
    }


    /**
     * 获取分块导出数据
     * @Author YangYiChao
     * @Date   2019-07-01
     */
    public function getExportData($filter=array(),$page_size=500,&$id){
        $mdlShopSettlementBasic = app::get('financebase')->model('bill_base');

        $res = array();

        $filter['bill_id|than'] = $id;
        $data = $this->getList('unique_id,bill_id,channel_id as shop_id,fee_item,bill_bn',$filter,0,$page_size,'bill_id');

        
        
        if($data){
            $array_bill_category = array();
            foreach ($data as $v) {
                $array_bill_category[$v['unique_id']] = array('bill_category'=>$v['fee_item'],'bill_bn'=>$v['bill_bn']);
                $id = $v['bill_id'];
            }
            unset($data);

            $base_filter = array('unique_id|in'=>array_keys($array_bill_category));
            isset($filter['shop_id']) and $base_filter['shop_id'] = $filter['shop_id'];
            $data = $mdlShopSettlementBasic->getList('unique_id,content,shop_id',$base_filter);

            foreach ($data as &$v) {
                $v['content'] = json_decode($v['content'],1);
                $v['content']['shop_id'] = $v['shop_id'];
                $v['content']['bill_bn'] = $array_bill_category[$v['unique_id']]['bill_bn'];
                $v['content']['bill_category'] = $array_bill_category[$v['unique_id']]['bill_category'];
                array_push($res, $v['content']);
            }
        }

        return $res;
    }

}
