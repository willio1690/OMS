<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 店铺商品同步
 */
class erpapi_shop_response_goods extends erpapi_shop_response_abstract 
{
    /**
     * 同步店铺商品
     * 
     * @return void
     * @author 
     */

    public function add($params)
    {
        $item = $this->_formatAddParams($params);

        $this->__apilog['title']       = '平台商品同步';
        $this->__apilog['original_bn'] = $item['iid'];
        $this->__apilog['result']      = array('data'=>array('tid'=>$item['iid']),'msg'=>'');
        
        if (!$item) {
            $this->__apilog['result']['msg'] = 'item_push数据结构ERROR';
            return false;
        }
        
        if (!$item['iid']) {
            $this->__apilog['result']['msg'] = '缺少iid';
            return false;
        }

        // 如果最后更新时间没变，不再接收
        $item_id = md5($this->__channelObj->channel['shop_id'].$item['iid']);
        
        $oldItem = app::get('inventorydepth')->model('shop_items')->db_dump($item_id,'outer_lastmodify');
        
        // 多sku矩阵分30个一页请求
        $modified = strtotime($item['modified']) + intval($item['page_no']);
        if ($modified <= $oldItem['outer_lastmodify']) {
            $this->__apilog['result']['msg'] = '商品无需更新';
            return false;
        }
        
        $this->__apilog['title'] .= $item['jdp_delete'] == '1' ? 'DEL' : 'SET';
        
        $sdf = array(
            'item' => $item,
            'shop' => $this->__channelObj->channel,
        );
        
        return $sdf;
    }
    
    /**
     * 格式化参数
     * 
     * @param array $params
     * @return array
     */
    protected function _formatAddParams($params)
    {
        $item = is_string($params['data']) ? @json_decode($params['data'], true) : $params['data'];
        
        $item['iid']  = $item['iid'] ? $item['iid'] : $item['num_iid']; unset($item['num_iid']);
        
        if ($item['skus']) {
            foreach ($item['skus']['sku'] as $k => $sku) {
                $shop_properties_name = '';
                
                if ($sku['properties_name']) {
                    $properties = explode(';', $sku['properties_name']);
                    foreach ($properties as $p) {
                        list($pid,$vid,$pid_name,$vid_name) = explode(':', $p);
                        $shop_properties_name .= $pid_name.':'.$vid_name.';';
                    }
                }
                
                $item['skus']['sku'][$k]['properties_name'] = $shop_properties_name;
            }
        }
        
        return $item;
    }
    
    /**
     * 删除店铺商品
     * 
     * @return void
     * @author
     */
    public function delete($params)
    {
        $item = is_string($params['data']) ? @json_decode($params['data'], true) : $params['data'];
        
        $item['iid']  = $item['iid'] ? $item['iid'] : $item['num_iid']; unset($item['num_iid']);
        
        $this->__apilog['title']       = '平台商品删除';
        $this->__apilog['original_bn'] = $item['iid'];
        $this->__apilog['result']      = array('data'=>array('tid'=>$item['iid']),'msg'=>'');
        
        if (!$item) {
            $this->__apilog['result']['msg'] = 'item_push数据结构ERROR';
            return false;
        }
        
        if (!$item['iid']) {
            $this->__apilog['result']['msg'] = '缺少iid';
            return false;
        }
        
        $sdf = array(
                'item' => $item,
                'shop' => $this->__channelObj->channel,
        );
        
        return $sdf;
    }

    /**
     * 平台sku id 删除
     * 
     * @param array $params
     * @return array|bool
     */
    public function sku_delete($params) {
        $this->__apilog['title']       = '平台商品删除';
        $this->__apilog['original_bn'] = $params['sku_id'];
        $this->__apilog['result']      = array('data'=>array('tid'=>$params['sku_id']),'msg'=>'');
        
        if (!$params['sku_id']) {
            $this->__apilog['result']['msg'] = '缺少sku_id';
            return false;
        }
        
        if (!$params['iid']) {
            $this->__apilog['result']['msg'] = '缺少iid';
            return false;
        }
        
        $sdf = array(
                'sku_id' => $params['sku_id'],
                'iid' => $params['iid'],
                'shop' => $this->__channelObj->channel,
        );
        
        return $sdf;
    }
    
    /**
     * [翱象系统]货品新建&更新结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_update($params)
    {
        $this->__apilog['title'] = '翱象货品结果回传';
        $this->__apilog['original_bn'] = $this->__channelObj->channel['shop_bn'].'_update';
        
        //result
        $results = json_decode($params['results'], true);
        
        //params
        $sdf = array(
            'shop_id' => $this->__channelObj->channel['shop_id'],
            'items' => $results
        );
        
        return $sdf;
    }
    
    /**
     * [翱象系统]组合货品新建&更新结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_combine_update($params)
    {
        $this->__apilog['title'] = '翱象组合货品结果回传';
        $this->__apilog['original_bn'] = $this->__channelObj->channel['shop_bn'].'_update';
        
        //result
        $results = json_decode($params['results'], true);
        
        //params
        $sdf = array(
            'shop_id' => $this->__channelObj->channel['shop_id'],
            'items' => $results
        );
        
        return $sdf;
    }
    
    /**
     * [翱象系统]货品删除结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_delete($params)
    {
        $this->__apilog['title'] = '翱象删除货品结果回传';
        $this->__apilog['original_bn'] = $params['scItemId'];
        
        //result
        $rsp = ($params['success'] == 'True' ? 'succ' : 'fail');
        
        //error_msg
        $error_msg = ($params['bizMessage'] ? $params['bizMessage'] : $params['err_msg']);
        $error_msg = stripslashes($error_msg);
        $error_msg = str_replace(array('"', "'", '/'), '', $error_msg);
        
        //params
        $sdf = array(
            'product_bn' => $params['scItemId'],
            'shop_id' => $this->__channelObj->channel['shop_id'],
            'rsp' => $rsp,
            'err_msg' => $error_msg,
        );
        
        return $sdf;
    }
    
    /**
     * [翱象系统]商货品关联关系结果回传
     * 
     * @param array $params
     * @return array
     */
    public function aoxiang_mapping($params)
    {
        $this->__apilog['title'] = '翱象货品关联关系结果回传';
        $this->__apilog['original_bn'] = $this->__channelObj->channel['shop_bn'].'_mapping';
        
        //result
        $results = json_decode($params['results'], true);
        
        //params
        $sdf = array(
            'shop_id' => $this->__channelObj->channel['shop_id'],
            'items' => $results
        );
        
        return $sdf;
    }
}