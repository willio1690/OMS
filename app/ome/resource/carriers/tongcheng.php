<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

return [
    // 美团外卖配送方式配置
    
    // 专送类配送
    '1001' => array(
        'name'         => '美团专送（加盟）',
        'type'         => '1001',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '1002' => array(
        'name'         => '美团专送（自建）',
        'type'         => '1002',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '1004' => array(
        'name'         => '城市代理',
        'type'         => '1004',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    
    // 快送类配送
    '2002' => array(
        'name'         => '快送',
        'type'         => '2002',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '2010' => array(
        'name'         => '全城送',
        'type'         => '2010',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '1007' => array(
        'name'         => '新快送',
        'type'         => '1007',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    
    // 混合配送
    '3001' => array(
        'name'         => '混合送（专送+快送)',
        'type'         => '3001',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '30011002' => array(
        'name'         => '混合自建',
        'type'         => '30011002',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '30011001' => array(
        'name'         => '混合加盟',
        'type'         => '30011001',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '30012002' => array(
        'name'         => '混合快送',
        'type'         => '30012002',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    
    // 第三方配送
    '0002' => array(
        'name'         => '趣生活美食配送',
        'type'         => '0002',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '0016' => array(
        'name'         => '达达快递',
        'type'         => '0016',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    '0033' => array(
        'name'         => 'E_代送',
        'type'         => '0033',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'instatnt'
    ),
    
    // 商家配送
    '0000' => array(
        'name'         => '商家自配',
        'type'         => '0000',
        'website'      => '',
        'request_url'  => '',
        'model'        => 'seller'
    ),
];