<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */
/**
 * 商品同步pos
 *
 * @category
 * @package
 * @author sunjing
 * @version $Id: Z
 */
class erpapi_store_openapi_pekon_request_goods extends erpapi_store_request_goods
{

   static $units = array(

        '台' =>  'TAI',
        '条' =>  'TIAO',
        '个' =>  'GE',
        '套' =>  'TAO',
        '杯' =>  'BEI',

   );

    protected function _format_goods_params($sdf)
    {

        $source = $sdf['source'];
        $saleFlag = 'Y';
        if($source == 'local' || $sdf['visibled'] == 2){
            $saleFlag = 'N';
        }

        
        $params = array(
            'originDataId'          =>  $sdf['material_bn'],//第三方编码
            'code'                  =>  $sdf['material_bn'],//商品
            'barcode'               =>  $sdf['barcode'],//商品条码
            'name'                  =>  $sdf['material_name'],//商品名称
            'nameShort'             =>  $sdf['tax_name'],//商品简称
            'brandCode'             =>  $sdf['brand_code'],//商品品牌代码
            'productType'           =>  'normal',//商品类型   
            'industryCode'          =>  'DEFAULT',//类目编码   
            'hasComponent'          =>  'N',//是否包含组件
            'uomCode'               =>  $sdf['unit'] ? self::$units[$sdf['unit']] : '',//基本单位
            'status'                =>  'Y',//状态
            'saleFlag'              =>  $saleFlag,//是否可销售
            'orderFlag'             =>  'Y',//是否可订货
            'purchaseFlag'          =>  'Y',//是否采购类商品
            'stockFlag'             =>  'Y',//是否库存类商品
            'classificationNameLv1' =>  $sdf['parent_cat_name'],//1级分类名称
            'classificationNameLv2' =>  $sdf['cat_name'],//2级分类名称
            'classificationNameLv3' =>  '',//对应小类名称
            'originDataUpdatedTime' =>  date('Y-m-d'),
            'retailPrice'           =>  $sdf['retail_price'],//标准零售价
            'memberPrice'           =>  $sdf['retail_price'],
            'tagPrice'              =>  $sdf['retail_price'],
            'orderPrice'            =>  $sdf['retail_price'],
            'orderMarketPrice'      =>  $sdf['retail_price'],
            'purchasePrice'         =>  $sdf['retail_price'],
            'costPrice'             =>  $sdf['retail_price'],
            'taxRate'               =>  $sdf['tax_rate'],
            'taxCode'               =>  $sdf['tax_code'],
            'productSeriesCode'     =>  $sdf['product_type'],
            'productSeriesName'     =>  $sdf['product_type'],
        );
        //productEntityAttribute
        if(in_array($sdf['product_type'],array('R','SV','A7'))){
            $params['productEntityAttribute'] = 'N';
            //$params['stockFlag'] = 'N';
        }

        if ($sdf['material_type'] == '5') {
            $params['stockFlag'] = 'N';
        }


        if($sdf['combination_items']){
            $params['hasComponent'] = 'Y';
            $params['hasComponentType'] = 'AND';
            $productBomItemList = [];
            foreach($sdf['combination_items'] as $v){
                $comuomCode = $v['unit'] ? self::$units[$v['unit']] : '';
                $productBomItemList[] = [
                    'originDataId'  =>  $v['material_bn'],
                    'code'          =>  $v['material_bn'],
                    'quantity'      =>  $v['material_num'],
                    'uomCode'       =>  $comuomCode,

                ];
            }
            $params['productBomItemList'] = $productBomItemList;
        }
        if (in_array($sdf['product_type'], ['C','M'])){
           // $params['productType'] = 'normal';
        }

        if(in_array($sdf['product_type'],array('R'))){
            $params['productType'] = 'repair';
           
        }
        //serial_number
        if($sdf['serial_number'] == 'true'){
            $params['managerStyle'] = 'unique';
        }
        return $params;
    }

    protected function get_goods_add_apiname()
    {
        $apiname = 'synchProductSpuByInterface';
        return $apiname;
    }

    

    /**
     * _format_syncprice_params
     * @param mixed $sdf sdf
     * @return mixed 返回值
     */

    public function _format_syncprice_params($sdf){
        $params=[];
        $priceBookItemList = [];
        foreach($sdf['items'] as $v){
            $priceBookItemList[] = array(
                'skuCode'       =>  $v['material_bn'],
                'price'         =>  $v['price'],
                'tagPrice'      =>  $v['price'],
                'memberPrice'   =>  $v['price'],
                'orgCode'       =>  $v['store_bn'],
                'fromDate'      =>  '2023-01-01',
                'priceType'     =>  'retail',

            );
        }
        $params['priceBookItemList'] = $priceBookItemList;
        
        $params['action'] = $sdf['action'];
        return $params;
    }

    protected function get_goods_syncprice_apiname()
    {
        $apiname = 'synchPriceBook';
        return $apiname;
    }

}
