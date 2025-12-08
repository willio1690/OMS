<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class ome_goods_product{
    

    /**
     * 检查ProductByBn
     * @param mixed $bn bn
     * @return mixed 返回验证结果
     */
    public function checkProductByBn($bn){

        $basicMaterialObj = app::get('material')->model('basic_material');
        $productInfo = $basicMaterialObj->dump(array('material_bn'=>$bn),"bm_id");


        if(!$productInfo){
            return false;
        }

        return $productInfo['bm_id'];
    }
    

    /**
     * 获取ProductByBn
     * @param mixed $bn bn
     * @return mixed 返回结果
     */
    public function getProductByBn($bn){
        $basicMaterialObj = app::get('material')->model('basic_material');
        $bMaterialRow    = $basicMaterialObj->dump(array('material_bn'=>$bn), 'bm_id, material_bn, material_name');
        if(empty($bMaterialRow)){
            return false;
        }
        
        $productInfo    = array('product_id'=>$bMaterialRow['bm_id'], 'bn'=>$bMaterialRow['material_bn'], 'name'=>$bMaterialRow['material_name']);
        
        return $productInfo;
    }
    
    /**
     * 验证正数(包括小数)
     * @param null $data 金额
     * @param bool $is_allow 是否允许 0 和 '0'
     * @return bool
     * @date 2024-11-25 9:43 上午
     */

    function valiPositive($data = null,$is_allow = false){
        if(is_numeric( $data)){
            $new = explode('.',$data);
            $count = count($new);

            if(1 == $count){
                $patter = '/^[1-9]{1}[0-9]{0,}$/';
                if ($is_allow) {
                    $patter = '/^0$|^[1-9][0-9]*$/';//允许金额为0
                }
                preg_match($patter,$new[0],$arr);
                if(empty($arr)){
                    return false;
                }
            }elseif(2 == $count){
                $patter = '/^(?:(?:[0-9]{1})||(?:[1-9]{1}[0-9]{1,}))$/';
                preg_match($patter,$new[0],$arr);
                if(empty($arr)){
                    return false;
                }
            }

            if($data<=0 && !$is_allow){
                return false;
            }
            return true;
        }else{
            return false;
        }
    }

    //校验字符是否合法
    function validstr($bn){
        preg_match('/\+|\'/',$bn,$filtcontent);
        if ($filtcontent){
            return false;
        }
        return true;
    }

    /**
     * 获取ProductGoods
     * @param mixed $productId ID
     * @param mixed $productField productField
     * @param mixed $goodField goodField
     * @return mixed 返回结果
     */
    public function getProductGoods($productId, $productField = '*', $goodField = '*') {
        if($productField != '*'){
            if(strpos($productField, 'product_id') === false) {
                $productField = $productField ? $productField . ',product_id' : 'product_id';
            }
            if(strpos($productField, 'goods_id') === false) {
                $productField .= ',goods_id';
            }
        }
        if($goodField != '*'){
            if(strpos($goodField, 'goods_id') === false) {
                $goodField = $goodField ? $goodField . ',goods_id' : 'goods_id';
            }
        }
        $products = app::get('ome')->model('products')->getList($productField, array('product_id'=>$productId));
        $productGoods = array();
        $goodsId = array();
        foreach ($products as $val) {
            $productGoods[$val['product_id']] = $val;
            $goodsId[] = $val['goods_id'];
        }
        $goods = app::get('ome')->model('goods')->getList($goodField, array('goods_id'=>$goodsId));
        $goodsData = array();
        foreach ($goods as $val) {
            $goodsData[$val['goods_id']] = $val;
        }
        foreach ($productGoods as $k => $val) {
            $productGoods[$k]['goods'] = $goodsData[$val['goods_id']];
        }
        return $productGoods;
    }

}
