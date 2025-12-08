<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * ABSTRACT
 *
 * @category
 * @package
 * @author chenping<chenping@shopex.cn>
 * @version $Id: Z
 */
abstract class erpapi_wms_request_abstract
{
    
    protected $__channelObj;

    protected $__resultObj;
    /**
     * @var erpapi_caller
     */
    protected $__caller;
    protected $outSysProductField = 'item_id';

    final public function init(erpapi_channel_abstract $channel, erpapi_config $config, erpapi_result $result)
    {
        $this->__channelObj = $channel;

        $this->__resultObj = $result;

        // 默认以JSON格式返回
        $callerObj = new erpapi_caller();
        $this->__caller = $callerObj
                            ->set_config($config)
                            ->set_channel($channel)
                            ->set_result($result);
    }

    /**
     * 成功输出
     *
     * @return void
     * @author
     **/
    final public function succ($msg = '', $msgcode = '', $data = null)
    {
        return array('rsp' => 'succ', 'msg' => $msg, 'msg_code' => $msgcode, 'data' => $data);
    }

    /**
     * 失败输出
     *
     * @return void
     * @author
     **/
    final public function error($msg, $msgcode='', $data = null)
    {
        return array('rsp' => 'fail', 'msg' => $msg, 'err_msg' => $msg, 'msg_code' => $msgcode, 'data' => $data);
    }

    /**
     * 生成唯一键
     *
     * @return void
     * @author
     **/
    final public function uniqid()
    {
        $microtime  = utils::microtime();
        $unique_key = str_replace('.', '', strval($microtime));
        $randval    = uniqid('', true);
        $unique_key .= strval($randval);
        return md5($unique_key);
    }

    /**
     * 获取仓库售达方
     *
     * @return void
     * @author
     **/
    final public function get_warehouse_code($wms_id, $branch_bn)
    {
        $branch_relationObj = app::get('wmsmgr')->model('branch_relation');
        $branch_relation    = $branch_relationObj->dump(array('wms_id' => $wms_id, 'sys_branch_bn' => $branch_bn));

        return $branch_relation['wms_branch_bn'] ? $branch_relation['wms_branch_bn'] : $branch_bn;
    }

    /**
     * 获取物流公司售达方
     *
     * @return void
     * @author
     **/
    final public function get_wmslogi_code($wms_id, $logi_code)
    {
        $logistics_code = kernel::single('wmsmgr_func')->getWmslogiCode($wms_id, $logi_code);

        return $logistics_code ? $logistics_code : $logi_code;
    }

    /**
     * 回调
     *
     * @return void
     * @author
     **/
    public function callback($response, $callback_params)
    {

        $rsp     = $response['rsp'];
        $err_msg = $response['err_msg'];
        $data    = $response['data'];
        $msg_id  = $response['msg_id'];
        $res     = $response['res'];

        $status = 'fail';
        $msg    = $err_msg . '(' . $res . ')';
        if ($rsp == 'succ') {
            $msg    = '成功';
            $status = 'success';
        }

        // 记录失败
        $obj_type = $callback_params['obj_type'];
        $obj_bn   = $callback_params['obj_bn'];
        $method   = $callback_params['method'];
        $log_id   = $callback_params['log_id'];
        
        if($status == 'fail') {
            kernel::single('monitor_event_notify')->addNotify('rpc_warning', [
                'title'     => $callback_params['request_title'],
                'bill_bn'   => $callback_params['obj_bn'],
                'method'    => $method,
                'errmsg'    => '【回调】' . $msg,
            ]);
        }

        //@todo：请求WMS之前就已经加了erpapi_api_fail失败日志,所以这里注释掉,否则重复调用;
        //$failApiModel = app::get('erpapi')->model('api_fail');
        //$failApiModel->publish_api_fail($method, $callback_params, $response);
        
        
        if ($log_id) {
            $logModel = app::get('ome')->model('api_log');
            $logModel->update_log($log_id, $msg, $status, null, null);
        }

        return array('rsp' => $rsp, 'res' => '', 'msg' => $msg, 'msg_code' => '', 'data' => $data);
    }

    final protected function _formate_receiver_province($province,$district='')
    {
        $mapping = array(
            '新疆' => '新疆维吾尔自治区',
            '宁夏' => '宁夏回族自治区',
            '广西' => '广西壮族自治区',
        );

        if ($mapping[$province]) return $mapping[$province];

        $zhixiashi = array('北京','上海','天津','重庆');
        $zizhiqu = array('内蒙古','宁夏回族','新疆维吾尔','西藏','广西壮族');

        if (in_array($province,$zhixiashi) && !$district) { // 如果三级不存在，直接将省提升为市
            $province = $province.'市';
        } elseif (in_array(rtrim($province, '市'),$zhixiashi)) {
            $province = rtrim($province, '市');
        }elseif (in_array($province,$zizhiqu)) {
            $province = $province.'自治区';
        }elseif(!preg_match('/(.*?)省/',$province)){
            $province = $province.'省';
        }

        return $province;
    }

    final protected function _formate_receiver_citye($receiver_city)
    {
        $zhixiashi = array('北京', '上海', '天津', '重庆');
        $zizhiqu   = array('内蒙古', '宁夏回族', '新疆维吾尔', '西藏', '广西壮族');

        if (in_array($receiver_city, $zhixiashi)) {
            $receiver_city = $receiver_city . '市';
        } else if (in_array($receiver_city, $zizhiqu)) {
            $receiver_city = $receiver_city . '自治区';
        } elseif (!preg_match('/(.*?)省/', $receiver_city)) {
            $receiver_city = $receiver_city . '省';
        }
        return $receiver_city;
    }

    protected function transfer_inventory_type($type_id)
    {
        $inventory_type = array(
            '5'   => '101', //残次品
            '50'  => '101',
            '300' => '401', //样品
            '400' => '501', //新品
        );

        return isset($inventory_type[$type_id]) ? $inventory_type[$type_id] : '1';
    }

    /**
     * 获取虚拟仓编号
     *
     * @return void
     * @author
     **/
    final public function get_wms_branch_bn($wms_id, $branch_bn)
    {
        $branch_relationObj = app::get('wmsmgr')->model('branch_relation');
        $branch_relation    = $branch_relationObj->dump(array('wms_id' => $wms_id, 'sys_branch_bn' => $branch_bn));

        return $branch_relation['wms_branch_bn'] ? $branch_relation['wms_branch_bn'] : '';
    }

    protected function _getShopCode($shopInfo)
    {

        $shop_code = kernel::single('wmsmgr_func')->getWmsShopCode($this->__channelObj->wms['channel_id'], $shopInfo['shop_bn']);
        return $shop_code ? $shop_code : $shopInfo['shop_bn'];
    }

    protected function _getSupplierCode($supplierInfo) {
        $supplier_relationObj = app::get('wmsmgr')->model('supplier_relation');
        $supplier_relation = $supplier_relationObj->db_dump(array('wms_id'=>$this->__channelObj->channel['channel_id'],'supplier_id'=>$supplierInfo['supplier_id']));
        return $supplier_relation['wms_supplier_bn'] ? $supplier_relation['wms_supplier_bn'] : $supplierInfo['supplier_bn'];
    }

    /**
     * 获取与WMS的物流映射关系
     *
     * @return void
     * @author 
     **/
    protected function _getCpCode($corp)
    {
        $mdl = app::get('wmsmgr')->model('express_relation');

        $wmsCp = $mdl->dump(['wms_id' => $this->__channelObj->wms['channel_id'], 'logi_id' => $corp['corp_id']]);

        if ($wmsCp) {
            return $wmsCp['wms_express_bn'] ?: $wmsCp['sys_express_bn'];
        }

        return $corp['type'];
    }

    protected function _formate_receiver_district($province, $city, $district)
    {
        $province = preg_replace('/省|市/', '', $province);

        if ($province == '广东' && in_array($city, array('东莞市', '中山市'))) {
            $district = '';
        }

        return $district;
    }

    /**
     * 取普通物料的映射关系
     * @param  [type] $arrProductId [description]
     * @return [type]               [description]
     */
    protected function _getOutSysProductBn($arrProductId)
    {
        $sku             = app::get('console')->model('foreign_sku')->getList('inner_product_id, outer_sku', array('wms_id' => $this->__channelObj->wms['channel_id'], 'inner_product_id' => $arrProductId, 'inner_type' => '0'));
        $outSysProductBn = array();
        foreach ($sku as $val) {
            $outSysProductBn[$val['inner_product_id']] = $val['outer_sku'];
        }
        return $outSysProductBn;
    }

    /**
     * 过滤掉除中文英文数字以后的字符
     *
     * @return void
     * @author 
     */
    protected function _filter_spechars($str)
    {
        // $str = preg_replace('/[^\x{4e00}-\x{9fa5}A-Za-z0-9_#-—()\[\]]/u','',$str);

        $str = str_replace(array("<",">","&","'",'"','','+','\\'),'',$str);

        return $str;
    }
    
    /**
     * 新加入这个公共方法(修复:很多业务类中没有此方法,但却调用了会报错)
     */
    protected function _getNextObjType()
    {
        return '';
    }

    
    protected function get_logistics_code($return_logi_name) {
        if(empty($return_logi_name)) {
            return 'OTHER';
        }
        $arr = [
            'SF'=>'顺丰',
            'YTO'=>'圆通',
            'ZTO'=>'中通',
            'HTKY'=>'百世汇通',
            'POSTB'=>'邮政小包',
            'YUNDA'=>'韵达',
            'JD'=>'京东',
        ];
        $lc = 'OTHER';
        foreach ($arr as $k => $v) {
            if (strpos($return_logi_name, $v) !== false) {
                $lc = $k;
                break;
            }
        }
        return $lc;
    }
}
