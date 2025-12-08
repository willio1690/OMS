<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

class inventorydepth_offline_calculation
{

    public static $branchBn = array();

    public static $shopFreeze = array();
    
    public static $tmpBmShopFreeze = array();

    public static $actualStock = array();

    public static $storeAndFreeze = array();

    public static $pkgActualStockBn = array();

    public static $pkgActualStockBnNum = array();

    public function __construct($app)
    {
        $this->app = $app;
        $this->db  = kernel::database();
    }

    public function init()
    {
        self::$shopFreeze    =
        self::$actualStock   =
        self::$storeAndFreeze =
        self::$pkgActualStockBn =
        self::$pkgActualStockBnNum = array();
    }

    /**
     *  指定仓预占；根据订单来计算
     */
    public function get_shop_freeze($shop_product_bn, $shop_id, $branch_id)
    {
        $sha1Str = $shop_id . '-' . strtolower($shop_product_bn) . '-' . $branch_id;
        $sha1    = sha1($sha1Str);
        if (isset(self::$shopFreeze[$sha1])) {
            return (int) self::$shopFreeze[$sha1];
        }
        $bm_id_sm = $sm_id_bns = [];
        foreach ((array) inventorydepth_stock_products::$products as $key=>$value) {
            $bm_id_sm[$value['bm_id']] = $value['sm_id'];
            $sm_id_bns[$value['sm_id']]    = $value['sales_material_bn'];
        }
        $bm_id_sm = array_filter($bm_id_sm);
        if ( !$bm_id_sm ) {
            return 0;
        }
        //根据销售物料与基础物料的对应关系获取销售物料的店铺预占
        $basicMStockFreezeLib = kernel::single('material_basic_material_stock_freeze');
        foreach($bm_id_sm as $bm_id => $sm_id){
            //识别一个进程处理过程中是否获取过基础物料店铺预占
            $tmpBmShop = sha1($bm_id.'-'.$branch_id);
            if(isset(self::$tmpBmShopFreeze[$tmpBmShop])){
                $shop_freeze = self::$tmpBmShopFreeze[$tmpBmShop];
            }else{
                $shop_freeze = $basicMStockFreezeLib->getOrderBranchFreeze($bm_id, $branch_id);
                self::$tmpBmShopFreeze[$tmpBmShop] = $shop_freeze;
            }

            $sales_material_bn    = $sm_id_bns[$sm_id];
            $tmpsha1 = sha1($shop_id.'-'.strtolower($sales_material_bn).'-'.$branch_id);
            self::$shopFreeze[$tmpsha1] = (int) $shop_freeze;
        }

        return (int)self::$shopFreeze[$sha1];
    }

    /**
     * 可售库存 = 仓库库存 - 冻结库存 - 预占
     * @return int
     */
    public function get_actual_stock($shop_product_bn,$shop_id,$branch_id)
    {
        $sha1Str = $shop_id.'-'.$shop_product_bn.'-'.$branch_id;
        $sha1 = sha1($sha1Str);
        $storeAndFreeze = $this->get_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        $store = 0;
        $actual_stock = $storeAndFreeze['store']
            - $this->get_shop_freeze($shop_product_bn,$shop_id,$branch_id)
            - $storeAndFreeze['freeze'];

        self::$actualStock[$sha1] = (int)$actual_stock > 0 ? $actual_stock : 0;
        return self::$actualStock[$sha1];
    }

    public function get_actual_stock_make($shop_product_bn,$shop_id,$branch_id) {
        $storeAndFreeze = $this->get_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        $str = '库存:'.$storeAndFreeze['store'] .
            '- 指定仓预占:' . $this->get_shop_freeze($shop_product_bn,$shop_id,$branch_id) .
            '- 仓库预占:' . $storeAndFreeze['freeze'];
        return $str;
    }

    public function get_branch_bn($branch_id) {
        if (isset(self::$branchBn[$branch_id])) {
            return self::$branchBn[$branch_id];
        }
        $branch = app::get('ome')->model('branch')->db_dump($branch_id, 'branch_bn');
        self::$branchBn[$branch_id] = $branch['branch_bn'];
        return self::$branchBn[$branch_id];
    }

    /**
     * 获取仓库库存和冻结
     */
    public function get_store_and_freeze($shop_product_bn, $shop_id, $branch_id)
    {
        $sha1Str = $shop_id . '-' . $shop_product_bn . '-' . $branch_id;
        $sha1    = sha1($sha1Str);
        if (isset(self::$storeAndFreeze[$sha1])) {
            return self::$storeAndFreeze[$sha1];
        }

        $stockProductsLib = kernel::single('inventorydepth_stock_products');
        $store_sum = $store_freeze_sum = $arrive_num = $safe_num = 0;
        $branch_bn = $this->get_branch_bn($branch_id);
        $branch_product = $stockProductsLib->fetch_o2o_products($branch_bn, $shop_product_bn);
        self::$storeAndFreeze[$sha1] = array(
            'store' => $branch_product['store'],
            'freeze' => $branch_product['store_freeze'],
            'arrive' => $branch_product['arrive_store'],
            'safe' => $branch_product['safe_store'],
        );
        return self::$storeAndFreeze[$sha1];
    }

    //获取在途库存
    public function get_arrive_stock($shop_product_bn,$shop_id,$branch_id) {
        $storeAndFreeze = $this->get_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        return (int) $storeAndFreeze['arrive'];
    }

    //获取安全库存
    public function get_safe_stock($shop_product_bn,$shop_id,$branch_id) {
        $storeAndFreeze = $this->get_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        return (int) $storeAndFreeze['safe'];
    }

    //获取仓库预占
    public function get_branch_freeze($shop_product_bn,$shop_id,$branch_id) {
        $storeAndFreeze = $this->get_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        return (int) $storeAndFreeze['freeze'];
    }

    /**
     * @description 捆绑商品店铺预占
     */
    public function get_pkg_shop_freeze($shop_product_bn, $shop_id, $branch_id)
    {
        $sha1Str = $shop_id . '-' . strtolower($shop_product_bn) . '-' . $branch_id . '-pkg';
        $sha1    = sha1($sha1Str);
        if (isset(self::$shopFreeze[$sha1]) || $this->recal_pkg_shop_freeze === false) {
            return (int) self::$shopFreeze[$sha1];
        }
        $this->recal_pkg_shop_freeze = false;
        $pkgGoodsIds = $pkgGoodsIdBn = $pkgProduct = array();
        foreach ((array) inventorydepth_stock_pkg::$pkg as $key => $value) {
            $pkgGoodsIds[]                    = $value['goods_id'];
            $pkgGoodsIdBn[$value['goods_id']] = $key;
            $pkgProduct[$value['goods_id']]   = $value['products'];
        }
        $offset = 0;
        $limit  = 1000;
        do {
            $filter = array(
                'obj_id'   => $pkgGoodsIds,
                'obj_type' => 'pkg',
                'branch_id|than' => 0 
            );
            $list = app::get('ome')->model('shop_freeze_stock')->getList('shop_id,product_id,obj_id,freez_num,branch_id', $filter, $offset, $limit);
            if (empty($list)) {break;}
            if ($list) {
                foreach ($list as $k => $val) {
                    $firstProduct = $pkgProduct[$val['obj_id']][0];
                    if ($val['product_id'] == $firstProduct['product_id']) {
                        $tmpsha1 = sha1($val['shop_id'] . '-' . strtolower($pkgGoodsIdBn[$val['obj_id']]) . '-' . $val['branch_id'] . '-pkg');
                        self::$shopFreeze[$tmpsha1] += intval($val['freez_num'] / $firstProduct['pkgnum']);
                    }
                }
            }
            if (count($list) < $limit) {
                break;
            }
            $offset += $limit;
        } while (true);
        return (int) self::$shopFreeze[$sha1];
    }

    /**
     * @description 捆绑商品可售库存
     * @access public
     * @return void
     */
    public function get_pkg_actual_stock($shop_product_bn,$shop_id, $branch_id)
    {

        $sha1Str = $shop_id . '-' . $shop_product_bn . $branch_id . '-pkg';
        $sha1    = sha1($sha1Str);
        if (isset(self::$actualStock[$sha1])) {
            return (int) self::$actualStock[$sha1];
        }

        $pkg = kernel::single('inventorydepth_stock_pkg')->fetch_pkg($shop_product_bn);
        if (!$pkg || !$pkg['products'] || !is_array($pkg['products'])) {
            return false;
        }

        $stockList = array();
        $pkgNum = array();
        foreach ($pkg['products'] as $product) {
            $stock = $this->get_actual_stock(trim($product['bn']),$shop_id,$branch_id);

            if ($stock === false) return false;

            $stockList[$product['bn']] = (int)$stock/$product['pkgnum'];
            $pkgNum[$product['bn']] = $product['pkgnum'];
        }

        asort($stockList);

        self::$pkgActualStockBn[$sha1] = key($stockList);

        self::$pkgActualStockBnNum[$sha1] = $pkgNum[self::$pkgActualStockBn[$sha1]];

        $actual_stock = array_shift($stockList);

        self::$actualStock[$sha1] = (int) $actual_stock > 0 ? $actual_stock : 0;

        return (int)self::$actualStock[$sha1];
    }

    public function get_pkg_actual_stock_make($shop_product_bn,$shop_id,$branch_id){
        $storeAndFreeze = $this->get_pkg_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        $str = '最小可售库存商品:' . $storeAndFreeze['bn'] .
            '(库存('.$storeAndFreeze['store'].'):' .
            '- 指定仓预占:' . $storeAndFreeze['shop_freeze'] .
            '- 仓库预占:' . $storeAndFreeze['freeze'] . ') / 捆绑子商品数:' . $storeAndFreeze['pkg_bn_num'];
        return $str;
    }

    /**
     * @description 捆绑商品 仓库库存与冻结
     */
    public function get_pkg_store_and_freeze($shop_product_bn, $shop_id, $branch_id)
    {
        $sha1Str = $shop_id . '-' . $shop_product_bn . '-' . $branch_id . '-pkg';
        $sha1    = sha1($sha1Str);
        if (isset(self::$storeAndFreeze[$sha1])) {
            return self::$storeAndFreeze[$sha1];
        }

        if (!isset(self::$pkgActualStockBn[$sha1])) {
            $this->get_pkg_actual_stock($shop_product_bn, $shop_id, $branch_id);
        }
        $bn = self::$pkgActualStockBn[$sha1];
        $tmpArr = $this->get_store_and_freeze($bn, $shop_id, $branch_id);
        $shopFreeze = $this->get_shop_freeze($bn, $shop_id, $branch_id);
        $pkgBnNum = self::$pkgActualStockBnNum[$sha1];
        $storeAndFreeze = array();
        $storeAndFreeze['bn'] = $bn;
        $storeAndFreeze['pkg_bn_num'] = $pkgBnNum;
        $storeAndFreeze['shop_freeze'] = $shopFreeze;
        $storeAndFreeze['store'] = $tmpArr['store'];
        $storeAndFreeze['freeze'] = $tmpArr['freeze'];
        $storeAndFreeze['arrive'] = $tmpArr['arrive'];
        $storeAndFreeze['safe'] = $tmpArr['safe'];
        self::$storeAndFreeze[$sha1] = $storeAndFreeze;
        return self::$storeAndFreeze[$sha1];
    }

    //获取在途库存
    public function get_pkg_arrive_stock($shop_product_bn,$shop_id,$branch_id) {
        $storeAndFreeze = $this->get_pkg_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        return (int) $storeAndFreeze['arrive'] / $storeAndFreeze['pkg_bn_num'];
    }

    //获取安全库存
    public function get_pkg_safe_stock($shop_product_bn,$shop_id,$branch_id) {
        $storeAndFreeze = $this->get_pkg_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        return (int) $storeAndFreeze['safe'] / $storeAndFreeze['pkg_bn_num'];
    }

    //获取仓库预占
    public function get_pkg_branch_freeze($shop_product_bn,$shop_id,$branch_id) {
        $storeAndFreeze = $this->get_pkg_store_and_freeze($shop_product_bn,$shop_id,$branch_id);
        return (int) $storeAndFreeze['freeze'] / $storeAndFreeze['pkg_bn_num'];
    }

}
