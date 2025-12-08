<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 库存同步处理类
 * 
 * @author chenping<chenping@shopex.cn>
 */

class inventorydepth_stock {
    //const PUBL_LIMIT = 100; //批量发布的最大上限数
    //const SYNC_LIMIT = 50; //批量下载的最大上限数
    protected $type;

    public function __construct($app)
    {
        if(is_array($app)) {
            $this->app = $app[0];
            $this->type = $app[1];
        } else {
            $this->app = $app;
        }
    }

    public function get_benchmark($key=null)
    {
        $return = array(
                'actual_stock'   => '可售库存',
                //'release_stock'  => '发布库存',
                'branch_actual_stock'     => '仓库可售',
                'md_actual_stock'     => '门店可售',
                'shop_freeze'    => '店铺预占',
                //'globals_freeze' => '全局预占',
                'actual_safe_stock'   => '可售库存-安全库存',
                'branch_actual_safe_stock'     => '仓库可售-安全库存',
                'md_actual_safe_stock'     => '门店可售-安全库存',
            );
        if($this->type == '3') {//代表门店库存回传
            $return = array(
                    'actual_stock'   => '可售库存',
                    'safe_stock' => '安全库存',
                );
        }
        return $key ? $return[$key] : $return;
    }

    public function get_benchobj($key=null)
    {
        $return = array(
                'actual_stock'   => '可售库存',
                //'release_stock'  => $this->app->_('发布库存'),
                //'shop_freeze'    => $this->app->_('店铺预占'),
                //'globals_freeze' => $this->app->_('全局预占'),
            );
        return $key ? $return[$key] : $return;
    }


    public function update_release_stock($merchandise_id, $result)
    {

        $r = $this->app->model('viewProduct_stock')->update(array('release_stock'=>$result,'release_status'=>'sleep'),array('merchandise_id'=>$merchandise_id));

        return $r;
    }

    public function update_check($data, $result, &$msg)
    {
        foreach($data as $d){
            $merchandise_id = $d['merchandise_id'];

            $resultVal = $this->check_and_build($merchandise_id, $result, $msg);
            if ($resultVal === false) {
                if (strpos($msg, '小于零') !== false)
                    $msg = $this->app->_('部分商品调整后的库存已经小于零，请重新填写');

                return false;
            }

            $this->update_release_stock($merchandise_id,$resultVal);
        }
        return true;
    }

    /**
     * 通过公式更新发布库存 弃用
     * @return void
     * @author 
     **/
    public function updateReleaseByformula($filter,$result,&$errormsg)
    {
        return false;
    }

     public function storeNeedUpdateSku($order_id, $shop_id) {
        $prefix = 'inventorydepth_stock-';
        $objs = app::get('ome')->model('order_objects')->getList('bn', ['order_id'=>$order_id]);
        foreach($objs as $v) {
            $index = $prefix.$shop_id.'-'.$v['bn'];
            cachecore::store($index, 1, 600);
        }
    }

    public function getNeedUpdateSku($shop_id, $bn) {
        $prefix = 'inventorydepth_stock-';
        $index = $prefix.$shop_id.'-'.$bn;
        return cachecore::fetch($index);
    }

    /**
     * 公式安全计算
     * 如公式为{可售库存} + {安全库存}，则需要将可售库存、安全库存替换为实际值
     * 
     * @param string $formula 公式
     * @param array $params 参数
     * @return array [bool, string]
     */
    public function cal($formula, $params) 
    {
        $benchmark = $this->get_benchmark();

        // 正则
        $pattern = '/\{('.implode('|', $benchmark).')\}/';

        // 替换变量为数值（使用正则防止变量名被错误替换，比如 "ab" 替换成 a 的值）
        $replacedFormula = preg_replace_callback(
            $pattern,
            function ($matches) use ($params, $benchmark) {
                $key = array_search($matches[1], $benchmark);

                return (int)$params[$key];
            },

            $formula
        );
    
        // 使用简单的白名单验证数学的四则运算表达式是否合法
        if (!preg_match('/^[0-9+\-*\/\.\s\(\)]+$/', $replacedFormula)) {
            throw new \Exception('公式包含非法字符！原始公式: ' . $formula . ', 替换后公式: ' . $replacedFormula);
        }

        // 安全计算表达式（不使用 eval）
        // 可使用安全的 eval 替代函数或第三方库
        $result = eval("return $replacedFormula;");

        return $result;
    }
}


