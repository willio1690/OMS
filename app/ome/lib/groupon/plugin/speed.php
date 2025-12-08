<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

/**
 * 快速导入模板
 *
 * @author shiyao744@sohu.com
 * @version 0.1b
 */
class ome_groupon_plugin_speed extends ome_groupon_plugin_abstract implements ome_groupon_plugin_interface {
    
    public $_name = '快速导入模板';
    
    static $regionList=array(); //全部地区

    /**
     * 处理导入到原始数据
     *
     * @param array $data 原始数据
     * @return Array
     */
    public function process($data, $post) {
        
        return parent::process($data, $post);
    }
    
    public function convertToRowSdf($row, $post) {
        $row_sdf = array ();
        
        $order_bn = '';
        if ($row [0]) {
            $order_bn = str_replace ( '`', '', $row [0] );
        }
        
        $consignee_name = '';
        if ($row [1]) {
            $consignee_name = $row [1];
        }
        
        $consignee_area_province = '';
        $consignee_area_city = '';
        $consignee_area_county = '';
        $consignee_area_addr = '';
        if ($row [2]) {
            $consignee_area_province = $row [2];
        }
        
        if ($row [3]) {
            $consignee_area_city = $row [3];
        }
        
        if ($row [4]) {
            $consignee_area_county = $row [4];
        }
        
        if ($row [5]) {
            $consignee_area_addr = $row [5];
        }
        
        $consignee_mobile = '';
        if ($row [6]) {
            $consignee_mobile = $row [6];
        }
        
        $consignee_tel = '';
        if ($row [7]) {
            $consignee_tel = $row [7];
        }

        $shipping_name = '';
        if ($row [8]) {
            $shipping_name = $row [8];
        }

        $custom_mark = '';
        if ($row [9]) {
            $custom_mark = $row [9];
        }

        $createtime = '';
        if ($row [10]) {
            $createtime = strtotime($row [10]);
        }

        $cost_freight = 0;
        if($row[11]){
            $cost_freight = $row[11];
        }

        $mark_text = '';
        if ($row [12]) {
            $mark_text = $row [12];
        }
        
        $shipping_cod = false;
        if ($row [13]) {
            $row[13] = trim($row[13]);
            #货到付款
            if( ($row[13] == '是') || ($row[13] == 'true')||($row[13] == 'TRUE') ||($row[13] == 'yes') ||($row[13] == 'YES')){
                $shipping_cod = 'true';
            }
        }

        $product_bn = '';
        if ($row [14]) {
            $product_bn = $row [14];
        }

        $product_nums = '';
        if ($row [15]) {
            $product_nums = $row [15];
        }

        // 价格允许为0
        $product_price = '';
        if ($row [16] || $row [16] == '0') {
            $product_price = $row [16];
        }

        $is_tax = false;
        
        // 注意：cost_item和total_amount将在abstract.php中根据多商品重新计算
        // 这里只设置初始值，实际计算会在处理完所有商品后进行
        $cost_item = 0;
        $total_amount = 0;

        $row_sdf = array(
            'order_bn'=>trim($order_bn),
            'shipping'=>array(
                'shipping_name'=>$shipping_name,
                'is_cod'=>$shipping_cod,
                'cost_shipping'=>$cost_freight,                
            ),
            'custom_mark'=>$custom_mark,
            'mark_text'=>$mark_text,
            'consignee'=>array(
            'name'=>$consignee_name,
            'email'=>'',
            'zip'=>'',
            'mobile'=>$consignee_mobile,
            'telephone' => $consignee_tel,
            'addr'=>$consignee_area_addr,
            'area'=>
                array(
                 'province'=>$consignee_area_province,
                 'city'=>$consignee_area_city,
                 'county'=>$consignee_area_county,
                ),
            ),
            'is_tax'=>$is_tax,
            'cost_item'=>$cost_item,
            'total_amount'=>$total_amount,
            // 注意：以下字段在多商品订单中仅代表第一行商品信息
            // 完整的商品信息将在abstract.php中通过order_objects数组处理
            'product_bn'=>$product_bn,
            'product_price'=>$product_price,
            'product_nums'=>$product_nums,
        );
        return $row_sdf;
    }

    // 这些方法已不再需要，因为我们现在直接使用三列数据
    
    /**
     * 获取所有地区
     */
    function getRegions()
    {
        $sql = "SELECT local_name1,local_name2,local_name3 FROM(
        SELECT g.local_name AS local_name1,c.local_name AS local_name2,r.local_name AS local_name3
        FROM sdb_eccommon_regions AS r
        LEFT JOIN sdb_eccommon_regions AS c ON r.p_region_id=c.region_id
        LEFT JOIN sdb_eccommon_regions AS g ON c.p_region_id=g.region_id
        ) as f WHERE local_name1<>'' AND local_name2<>'' AND local_name3<>''";
        
        $regions = kernel::database()->select($sql);
        
        self::$regionList = $regions;
    }
    
    /**
     * [分词]匹配地址中的省市区
     */
    public function getareaname($addressTxt, &$province, &$city, &$county)
    {
        //check
        if(empty($addressTxt)){
            return false;
        }
        
        //获取所有地区
        $this->getRegions();
        
        $so = scws_new();
        $so->set_charset('utf8');
        $so->add_dict(APP_DIR.'/ome/statics/scws/etc/dict.utf8.xdb');
        $so->add_dict(APP_DIR.'/ome/statics/scws/etc/regions.txt',SCWS_XDICT_TXT);
        $so->set_rule(APP_DIR.'/ome/statics/scws/etc/rules.utf8.ini');
        $so->set_ignore(true);
        //$so->set_multi(true);
        $so->set_duality(true);
        $so->send_text($addressTxt);
        
        $i=1;
        while ($tmp = $so->get_result())
        {
            foreach($tmp as $key=>$arr){
                $mcWord[] = $arr['word'];
                $i++;
                if($i>6)
                    break;
            }
        }
        $so->close();
        
        if($mcWord[0]=='中国')
        {
            $mcWord[0]=$mcWord[1];
            $mcWord[1]=$mcWord[2];
            $mcWord[2]=$mcWord[3];
        }
        
        $municipality = array(
                '上海'=>'上海市',
                '北京'=>'北京市',
                '天津'=>'天津市',
                '重庆'=>'重庆市'
        );
        
        $provincetemp = $mcWord[0];
        $citytemp = $mcWord[1];
        $countytemp = $mcWord[2];
        
        $edf = mb_substr($provincetemp,-1,1,'utf-8');
        if($edf =='省'){
            $provincetemp = mb_substr($provincetemp, 0, -1,'utf-8');
        }
        
        $autonomy = array(
                '内蒙古'=>'内蒙古自治区',
                '新疆'=>'新疆维吾尔自治区',
                '广西'=>'广西壮族自治区',
                '宁夏'=>'宁夏回族自治区',
                '西藏'=>'西藏自治区');
        
        foreach($autonomy as $key=>$arr)
        {
            if($arr==$provincetemp){
                $provincetemp = $key;
            }
        }
        
        foreach($municipality as $key=>$arr)
        {
            if($arr==$mcWord[0]){
                $citytemp = $arr;
                $provincetemp = $key;
                if($citytemp==$mcWord[1]){
                    $countytemp = $mcWord[2];
                }else{
                    $countytemp = $mcWord[1];
                }
            }elseif($key==$mcWord[0]){
                $provincetemp = $key;
                $citytemp = $arr;
                if($citytemp==$mcWord[1]){
                    $countytemp = $mcWord[2];
                }else{
                    $countytemp = $mcWord[1];
                }
            }
        }
        
        $lv = array('县','市','镇','村','乡','区','旗');
        
        $regions = self::$regionList;
        foreach($regions as $key => $arr)
        {
            if($arr['local_name1']==$provincetemp&&$arr['local_name2']==$citytemp&&$arr['local_name3']==$countytemp){
                $province = $arr['local_name1'];
                $city = $arr['local_name2'];
                $county = $arr['local_name3'];
                return true;
            }
            
            foreach($lv as $arr12)
            {
                if($arr['local_name1']==$provincetemp&&$arr['local_name2']==$citytemp.$arr12&&$arr['local_name3']==$countytemp){
                    $province = $arr['local_name1'];
                    $city = $arr['local_name2'];
                    $county = $arr['local_name3'];
                    return true;
                }elseif($arr['local_name1']==$provincetemp&&$arr['local_name2']==$citytemp&&$arr['local_name3']==$countytemp.$arr12){
                    $province = $arr['local_name1'];
                    $city = $arr['local_name2'];
                    $county = $arr['local_name3'];
                    return true;
                }elseif($arr['local_name1']==$provincetemp&&$arr['local_name2']==$citytemp.$arr12&&$arr['local_name3']==$countytemp.$arr12){
                    $province = $arr['local_name1'];
                    $city = $arr['local_name2'];
                    $county = $arr['local_name3'];
                    return true;
                }
            }
        }
    }
    
    /**
     * 正则匹配详细地址中的省、市、区、镇
     * 
     * @param string $address
     * @return array
     */
    public function getAddressRegions($address)
    {
        //省
        $province = '';
        preg_match('/(.*?(省|自治区|北京市|天津市|上海市|重庆市|澳门特别行政区|香港特别行政区))/', $address, $matches);
        if(count($matches) > 1){
            $province = $matches[count($matches) - 2];
            $address = preg_replace('/(.*?(省|自治区|北京市|天津市|上海市|重庆市|澳门特别行政区|香港特别行政区))/','', $address, 1);
        }
        
        //市
        $city = '';
        preg_match('/(.*?(市|自治州|地区|区划|县))/', $address, $matches);
        if(count($matches) > 1){
            $city = $matches[count($matches) - 2];
            $address = str_replace($city, '', $address);
        }
        
        //区
        $area = '';
        preg_match('/(.*?(区|县|镇|乡|街道))/', $address, $matches);
        if (count($matches) > 1) {
            $area = $matches[count($matches) - 2];
            $address = str_replace($area, '', $address);
        }
        
        //return
        return array(
                'province' => isset($province) ? $province : '',
                'city' => isset($city) ? $city : '',
                'district' => isset($area) ? $area : '',
        );
    }
    
    /**
     * 匹配京东区域名称
     * 
     * @param string $address
     * @return array
     */
    public function getMappingJdRegions($address)
    {
        $regionsMdl = app::get('eccommon')->model('platform_regions');
        
        $province = $city = $county = '';
        
        //check
        if(empty($address)){
            return false;
        }
        
        //[分词]匹配详细地址中的省、市、区
        $mapResult = $this->getareaname($address, $province, $city, $county);
        if($province && $city && $county){
            $regionInfo = array('province'=>$province, 'city'=>$city, 'country'=>$county);
            
            return $regionInfo;
        }
        
        //正则匹配省、市、区
        $areaList = $this->getAddressRegions($address);
        
        //市、区不能为空
        if(empty($areaList['city']) && empty($areaList['district'])){
            return false;
        }
        
        $areaList = array_filter($areaList);
        
        $path_name = implode('-', $areaList);
        $path_name = str_replace(array('"',"'"), '', $path_name);
        
        //模糊查找
        $regionList = $regionsMdl->getList('id,local_path_name', array('local_path_name|has'=>$path_name, 'region_grade'=>3), 0, 5);
        if(empty($regionList)){
            return false;
        }
        
        //有多个记录
        if(count($regionList) > 1){
            return false;
        }
        
        //省、市、区
        list($province, $city, $county) = explode('-', $regionList[0]['local_path_name']);
        
        $regionInfo = array('province'=>$province, 'city'=>$city, 'country'=>$county);
        
        return $regionInfo;
    }
}