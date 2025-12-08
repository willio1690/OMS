<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 电子发票类
 * @author wangjianjun<wangjianjun@shopex.cn>
 * 20160627
 * @version 0.1
 */
class invoice_electronic{
    
    //组电子发票获取url的参数
    function getEinvoiceGetUrlRequestParams($sdf){
        //获取platform node_id
        $shop_info = kernel::single('ome_shop')->getRowByShopId($sdf['shop_id']);
        $einvoice_shop_type = kernel::single('invoice_common')->returnEinvoiceShopType($shop_info);
        $platform = kernel::single('invoice_common')->getPlatformByShopType($einvoice_shop_type);
        
        //根据红/蓝票的参数 电子发票开票信息明细表里获取invoice_no
        $billing_type = 1; //默认蓝票
        if($sdf["einvoice_type"] == "red"){
            $billing_type = 2;
        }
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        $rs_invoice_item = $mdlInOrderElIt->dump(array("id"=>$sdf["id"],"billing_type"=>$billing_type));
        
        $params = array(
                'node_id' => $shop_info["node_id"],
                'platform' => $platform,
                'invoice_no' => $rs_invoice_item["invoice_no"],
                'tid' => $sdf["order_bn"],
                'order_bn' => $sdf["order_bn"],
                'expire' => $sdf["expire_time"],
        );
        
        return $params;
    }
    
    //组电子发票回流天猫的参数
    function getEinvoiceUploadTmallRequestParams($sdf){
        //获取invoice_item
        $invoice_items = $this->getEinvoiceInvoiceItems($sdf,$sdf["einvoice_type"]);
        
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        if($sdf["einvoice_type"] == "blue"){
            //蓝票
            $invoice_type = "1";
            $invoice_amount = $sdf["amount"]; //开票金额（价税合计）
            //获取当前蓝票发票代码、发票号码
            $rs_el_item = $mdlInOrderElIt->dump(array("id"=>$sdf["id"],"billing_type"=>"1"));
            $invoice_code = $rs_el_item["invoice_code"];
            $invoice_no = $rs_el_item["invoice_no"];
        }
        if($sdf["einvoice_type"] == "red"){
            //冲红成功
            $invoice_type = "2";
            $invoice_amount = -$sdf["amount"]; //开票金额（价税合计）
            //获取当前红票和原蓝票的发票代码、发票号码
            $rs_el_items = $mdlInOrderElIt->getList("*",array("id"=>$sdf["id"]));
            foreach ($rs_el_items as $var_item){
                if(intval($var_item["billing_type"]) == 1){
                    //原蓝票
                    $arr_old_blue_info = array(
                        "normal_invoice_code" => $var_item["invoice_code"], //回流红票时，对应的原蓝票发票代码
                        "normal_invoice_no" => $var_item["invoice_no"], //回流红票时，对应的原蓝票发票号码
                    );
                }
                if(intval($var_item["billing_type"]) == 2){
                    //当前红票
                    $invoice_code = $var_item["invoice_code"];
                    $invoice_no = $var_item["invoice_no"];
                }
            }
        }
        
        //获取invoice_file_data 和 electronic_invoice_no
        $einvoice_url = $this->getApiEinvoiceUrl($sdf, $sdf["einvoice_type"]);
        $invoice_file_data = file_get_contents($einvoice_url);
        $electronic_invoice_no = $invoice_code.$invoice_no;
        
        $params = array(
                "invoice_code" => $invoice_code, //发票代码
                "invoice_no" => $invoice_no, //发票号码
                "invoice_file_data" => base64_encode($invoice_file_data), //发票文件内容,目前只支持jpg,png,bmp,pdf格式
                "invoice_type" => $invoice_type, //1 蓝票 2 红票
                "invoice_items" => json_encode($invoice_items), //电子发票明细
                "electronic_invoice_no" => $electronic_invoice_no, //电子发票号(一般是:发票代码+发票号码)
                "serial_no" => $sdf["serial_no"], //开票流水号，唯一标志开票请求。如果两次请求流水号相同，则表示重复请求, 由于ERP根据自己的业务请求确定。可采用订单id+操作代码，比如：订单号转成十六进制 + 操作代码（表示红票还是蓝票）+ 操作序号
                "payee_register_no" => $sdf["tax_no"], //收款方税务登记号
                "invoice_amount" => number_format($invoice_amount,2,".",""), //开票金额（价税合计金额），格式:100.00, 冲红时格式为"-100.00"
                "invoice_time" => date("Y-m-d H:i:s",$sdf["dateline"]), //开票日期, 格式"YYYY-MM-DD HH:SS:MM"
                "payee_name" => $sdf["payee_name"], //开票方名称，xx商城
                "tid" => $sdf["order_bn"], //淘宝的主订单id
                "invoice_title" => $sdf["title"]?$sdf["title"]:$sdf["tax_company"], //发票抬头，付款方名称
                'image_type' => 'pdf',
                //可选
                "qr_code" => "", //二维码,二维码扫码的结果。
                "anti_fake_code" => "", //防伪码
        );
        
        if($arr_old_blue_info){
            $params = array_merge($params,$arr_old_blue_info);
        }
        
        return $params;
    }
    
    //打电子发票接口获取电子发票url
    function getApiEinvoiceUrl($sdf,$type){
        $einvoice_url_cache_name = $type.$sdf["id"].$sdf["sync"];
        $einvoice_url = cachecore::fetch($einvoice_url_cache_name);
        if($einvoice_url){
            return $einvoice_url;
        }
        $expire_time = 3600;
        $sdf["expire_time"] = $expire_time; //过期时间
        $sdf["einvoice_type"] = $type; //看电子发票(蓝票blue或红票red)
        $einvoice_return_rs = kernel::single('invoice_event_trigger_einvoice')->getInvoiceUrl($sdf['shop_id'],$sdf);
        if($einvoice_return_rs["rsp"] == "succ"){
            $einvoice_url_data = json_decode($einvoice_return_rs["data"],true);
            $einvoice_url = $einvoice_url_data["url"];
            cachecore::store($einvoice_url_cache_name, $einvoice_url, $expire_time);
            return $einvoice_url;
        }else{
            return false;
        }
    }
    
    //组打电子发票接口开蓝票/冲红的参数
    function getEinvoiceCreateRequestParams($sdf,$type="blue"){
        //获取invoice_item
        $invoice_items = $this->getEinvoiceInvoiceItems($sdf,$type);
    
        //获取platform invoice_amount provider_appkey proxy_appkey
        $shop_info = kernel::single('ome_shop')->getRowByShopId($sdf['shop_id']);
        $einvoice_shop_type = kernel::single('invoice_common')->returnEinvoiceShopType($shop_info);
        $platform = kernel::single('invoice_common')->getPlatformByShopType($einvoice_shop_type);
        $invoice_amount = $sdf["amount"]; //开票金额（价税合计）
        $mdlInOrderSet = app::get('invoice')->model('order_setting');
        $rs_invoice_setting = $mdlInOrderSet->dump(array("shop_id"=>$sdf["shop_id"]));
        $provider_appkey = '60028257';//$rs_invoice_setting["provider_appkey"];
        $proxy_appkey = $provider_appkey;//目前和开票服务商的APPKEY一样
    
        //获取订单主表信息
        $mdlOmeOrders = app::get('ome')->model('orders');
        $rs_order = $mdlOmeOrders->dump(array("order_bn"=>$sdf["order_bn"],"shop_id"=>$sdf["shop_id"]));
        
        if($type == "red"){
            $invoice_amount = -$sdf["amount"];
            $rs_order["total_amount"] = -$rs_order["total_amount"];
            $sdf["cost_tax"] = -$sdf["cost_tax"];
        }
        
        $params = array(
                "business_type" => "0", //默认：0。对于商家对个人开具，为0;对于商家对企业开具，为1;
                "platform" => $platform, //电商平台代码
                "tid" => $sdf["order_bn"], //电商平台对应的订单号
                "serial_no" => $sdf["serial_no"], //开票流水号 例子： 20141234123412341
                "payee_address" => $sdf["address"], //开票方地址(新版中为必传)
                "payee_name" => $sdf["payee_name"], //开票方名称，公司名(如:XX商城)
                "payee_operator" => $sdf["payee_operator"], //开票人
                "invoice_amount" => number_format($invoice_amount,2,".",""), //开票金额
                "invoice_time" => date("Y-m-d H:i:s",time()), //开票日期
                "invoice_type" => $type, //发票(开票)类型，蓝票blue,红票red，默认blue
                "payee_register_no" => $sdf["tax_no"],  //收款方税务登记证号
                "payer_name" => $sdf["tax_company"]?$sdf["tax_company"]:$sdf["title"], //付款方名称, 对应发票台头
                "sum_price" => number_format($rs_order["total_amount"],2,".",""),  //合计金额(新版中为必传) 订单总金额
                "sum_tax" => number_format($sdf["cost_tax"],2,".",""), //合计税额
                "invoice_items" => json_encode($invoice_items), //电子发票明细
                //沙箱测试用 写死都是60028257
                //"provider_appkey" => $provider_appkey, //开票服务商的APPKEY
               // "proxy_appkey" => $proxy_appkey, //商家自己申请的放在开票代理客户端的appkey
                //可选
                "erp_tid" => "", //erp中唯一单据
                "payee_bankaccount" => $sdf["bank_no"], //开票方银行及 帐号
                "payer_register_no" => $sdf["ship_tax"], //付款方税务登记证号。对企业开具电子发票时必填
                "invoice_memo" => $sdf["remarks"], //发票备注
                "payer_address" => $sdf["ship_addr"], //消费者地址
                "payer_bankaccount" => $sdf["ship_bank_no"], //付款方开票开户银行及账号
                "payer_email" => "", //消费者电子邮箱
                "payer_phone" => $sdf["ship_tel"], //消费者联系电话
                "payee_checker" => "", //复核人
                "payee_receiver" => "", //收款人
                "payee_phone" => $sdf["telephone"], //收款方电话
        );
        
        if($type == "red"){
            //获取原始蓝票记录
            $rs_old_blue = $this->getOldBlueEinvoiceInfo($sdf["id"]);
            $params["normal_invoice_code"] = $rs_old_blue["invoice_code"]; //原发票代码(开红票时传入)
            $params["normal_invoice_no"] = $rs_old_blue["invoice_no"]; //原发票号码(开红票时传入)
        }
    
        return $params;
    }
    
    //组invoice_item行数据
    private function getEinvoiceInvoiceItemArr($type="blue",$item_name,$row_type,$sum_price,$tax,$tax_rate,$amount,$unit,$item_no="",$price="",$quantity=""){
        if($type == "red"){
            $amount = -$amount;
            $sum_price = -$sum_price;
            $tax = -$tax;
            $quantity = -$quantity;
        }
        $return_arr = array(
                "item_name" => $item_name, //发票项目名称（或商品名称）
                "row_type" => $row_type, //发票行性质。0表示正常行，1表示折扣行，2表示被折扣行。比如充电器单价100元，折扣10元，则明细为2行，充电器行性质为2，折扣行性质为1。如果充电器没有折扣，则值应为0
                "sum_price" => number_format($sum_price,2,".",""), //总价，格式：100.00
                "tax" => number_format($tax,2,".",""), //税额
                "tax_rate" => $tax_rate, //税率
                "amount" => number_format($amount,2,".",""), //价税合计
                "unit" => $unit, //单位。新版电子发票，折扣行不能传，非折扣行必传 如之后必要可从ome_products表中获取
                "item_no" => $item_no, //可选参数 发票项目编号（或商品编号）
                "specification" => "", //可选参数 规格型号 目前给空
        );
        //$price和$quantity 正常行和被折扣行 必有
        if($price){
            $return_arr["price"] = number_format($price,2,".",""); //单价，格式：100.00。新版电子发票，折扣行此参数不能传，非折扣行必传
        }
        if($quantity){
            $return_arr["quantity"] = $quantity; //数量。新版电子发票，折扣行此参数不能传，非折扣行必传
        }
        return $return_arr;
    }
    
    //组invoice_items参数数组
    private function getEinvoiceInvoiceItems($sdf,$type="blue"){
        //获取订单的order_object
        $mdlOmeOrderObjects = app::get('ome')->model('order_objects');
        $rs_objects= $mdlOmeOrderObjects->getList("*",array("order_id"=>$sdf["order_id"]));
    
        //商品的unit为必要参数统一获取
        $rl_bn_unit = $this->getEinvoiceBnUnitRlArr($rs_objects);
    
        $invoice_items = array();
        $count_line = 0; //如是折扣行需要计算行数
        $tax_rate = number_format($sdf["tax_rate"]/100,2,".","");
        foreach ($rs_objects as $var_object){
            if($var_object["pmt_price"] > 0){
                //有优惠金额 获取被折扣行和折扣行
                //先被折扣行 row_type为2
                $format_amount = number_format($var_object["amount"],2,".","");
                $tax = number_format($format_amount*$tax_rate,2,".","");
                $amount_plus_tax = $format_amount+$tax;
                $invoice_items[] = $this->getEinvoiceInvoiceItemArr($type,$var_object["name"],"2",$format_amount,$tax,$tax_rate,$amount_plus_tax,$rl_bn_unit[$var_object["bn"]],$var_object["bn"],$var_object["price"],$var_object["quantity"]);
                $count_line++;
                //后折扣行 row_type为1
                $format_pmt_price = number_format($var_object["pmt_price"],2,".",""); //优惠金额
                $item_name = "折扣行数".$count_line."()";
                $pmt_price = -$format_pmt_price; //优惠金额
                $tax = number_format($format_pmt_price*$tax_rate,2,".","");
                $tax = -$tax;
                $amount_plus_tax = $pmt_price+$tax;
                $invoice_items[] = $this->getEinvoiceInvoiceItemArr($type,$item_name,"1",$pmt_price,$tax,$tax_rate,$amount_plus_tax,$rl_bn_unit[$var_object["bn"]],$var_object["bn"]);
                $count_line++;
            }else{
                //row_type为0正常行
                $format_amount = number_format($var_object["amount"],2,".",""); //单商品总价
                $tax = number_format($format_amount*$tax_rate,2,".","");
                $amount_plus_tax = $format_amount+$tax;
                $invoice_items[] = $this->getEinvoiceInvoiceItemArr($type,$var_object["name"],"0",$format_amount,$tax,$tax_rate,$amount_plus_tax,$rl_bn_unit[$var_object["bn"]],$var_object["bn"],$var_object["price"],$var_object["quantity"]);
                $count_line++;
            }
        }
        //判断是否有配送费用
        $mdlOmeOrders = app::get('ome')->model('orders');
        $rs_order = $mdlOmeOrders->dump(array("order_bn"=>$sdf["order_bn"],"shop_id"=>$sdf["shop_id"]),"*");
        if($rs_order["shipping"]["cost_shipping"]>0){
            $shipping_tax = $rs_order["shipping"]["cost_shipping"]*$tax_rate;
            $amount_plus_tax = $rs_order["shipping"]["cost_shipping"]+$shipping_tax;
            $invoice_items[] = $this->getEinvoiceInvoiceItemArr($type,"运费","0",$rs_order["shipping"]["cost_shipping"],$shipping_tax,$tax_rate,$amount_plus_tax,"笔","",$rs_order["shipping"]["cost_shipping"],"1");
        }
        return $invoice_items;
    }
    
    //获取bn统一对应unit数组
    private function getEinvoiceBnUnitRlArr($rs_objects){
        $products_bns = array();
        foreach ($rs_objects as $var_obj){
            $products_bns[] = $var_obj["bn"];
        }
        $mdlOmeProducts = app::get('ome')->model('products');
        $rs_products = $mdlOmeProducts->getList("bn,unit",array("bn|in"=>$products_bns));
        $rl_bn_unit = array();
        foreach ($rs_products as $var_product){
            $rl_bn_unit[$var_product["bn"]] = $var_product["unit"];
        }
        return $rl_bn_unit;
    }

    public function getEinvoiceSerialNo(&$sdf, $billing_type = 1)
    {
        //获取开票信息明细表中的开票流水号
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        $filter         = array ("id" => $sdf["id"], "billing_type" => $billing_type);
        $rs_item        = $mdlInOrderElIt->dump($filter);
        if (empty($rs_item)) {
            //第一次点击开蓝票或者冲红 相应明细为空则insert一条有新的开票流水号的记录
            $serial_no  = $this->genEinvoiceSerialNo();
            $insert_arr = array (
                'id'             => $sdf["id"],
                'serial_no'      => $serial_no,
                'billing_type'   => $billing_type,
                'create_time'    => time(),
                'invoice_status' => $billing_type == 1 ? '10' : '20',
            );

            if ($sdf['invoice_action_type']) {
                $insert_arr['invoice_action_type'] = $sdf['invoice_action_type'];
            }

            $result = $mdlInOrderElIt->insert($insert_arr);
            if ($result) {
                // 再次读取, 因需获取全量数据
                $rs_item          = $mdlInOrderElIt->dump($filter);
                $sdf["serial_no"] = $rs_item["serial_no"];
                unset($rs_item['content']);
                // 金4全量电票,补充电票明细表内全部数据
                $sdf['order_electronic_items'] = $rs_item;
                return $sdf;
            } else {
                //没有生成明细记录 直接返回false 停止开票
                return false;
            }
        } else {
            //开票中或者开票失败或者冲红中或者冲红失败 不是首次点击 直接获取获取serial_no
            $sdf["serial_no"] = $rs_item["serial_no"];
            // 金4全量电票,补充电票明细表内全部数据
            unset($rs_item['content']);
            $sdf['order_electronic_items'] = $rs_item;
            return $sdf;
        }
    }
    
    //做当前电子发票开票或者冲红的按钮显示缓存 限制5分钟内不显示刚刚点击过的开票或者冲红link 防止重复点击打开票接口（蓝票、红票）
    public function do_einvoice_create_limit($id,$type="blue"){
        $einvoice_create_name = $id."_".$type;
        $current_time = time();
        $expire_time = 300;
        cachecore::store($einvoice_create_name,$current_time,$expire_time);
    }
    
    //生成电子发票唯一开票流水号20位
    private function genEinvoiceSerialNo(){
        //当前时间戳9位十六进制
        list($usec, $sec) = explode(" ", microtime());
        $time = $sec.$usec*100000000;
        $time_str = substr($time,7,9);
        $time_hex = dechex($time_str); //转16进制
        $time_hex = substr($time_hex,0,6);//截6位
    
        //请求ip十六进制
        if(!isset($GLOBALS['_REMOTE_ADDR_'])){
            $addrs = array();
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                foreach( array_reverse( explode( ',',  $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) as $x_f ) {
                    $x_f = trim($x_f);
                    if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $x_f ) )  {
                        $addrs[] = $x_f;
                    }
                }
            }
            $GLOBALS['_REMOTE_ADDR_'] = isset($addrs[0])?$addrs[0]:$_SERVER['REMOTE_ADDR'];
        }
        $remote_ip = $GLOBALS['_REMOTE_ADDR_'];
        $remote_ip = str_replace('.','',$remote_ip);
        $ip_hex = dechex((int)$remote_ip); //转16进制
        $ip_hex = substr($ip_hex,0,5);//截5位
    
        //随机数md5
        $rand_num = mt_rand(0,999999);
        $rand_md5 = md5($time.$rand_num);
        $rand_str = substr($rand_md5,0,9); //截9位
    
        return $time_hex.$ip_hex.$rand_str;
    }
    
    //获取冲红时原始蓝票的记录
    private function getOldBlueEinvoiceInfo($id){
        //电子发票开票信息明细表
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        //一个主表id只会有一个蓝票和一个红票
        $arr_filter = array("id"=>$id,"billing_type"=>"1");
        $rs_info = $mdlInOrderElIt->dump($arr_filter);
        return $rs_info;
    }
    
    //组开票结果接口请求参数
    public function getEinvoiceCreateResultParams($sdf){
        //获取platform node_id
        $shop_info = kernel::single('ome_shop')->getRowByShopId($sdf['shop_id']);
        $einvoice_shop_type = kernel::single('invoice_common')->returnEinvoiceShopType($shop_info);
        $platform = kernel::single('invoice_common')->getPlatformByShopType($einvoice_shop_type);
        //获取存在的开票流水号
        $mdlInOrderElIt = app::get('invoice')->model('order_electronic_items');
        $rs_item = $mdlInOrderElIt->dump(array("id"=>$sdf["id"],"billing_type"=>$sdf["billing_type"]));
        return array(
            "platform" => $platform, //电商平台代码
            "serial_no" => $rs_item["serial_no"], //开票流水号
            "tid" => $sdf["order_bn"], //电商平台对应的订单号
            "payee_register_no" => $sdf["tax_no"], //收款方税务登记证号
        );
    }
    
}
