/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

﻿/**
 * 加解密服务
 *
 *
 *
 *
 * 
 **/
(function(){
    Security=new Class({
        Implements:[Options,Events],
        options:{
            url:'',
            clickElement:false,
            sensitiveBlock:false,
            showData:false,
        },
        initialize:function(options){
            this.setOptions(options);

            this.options.sensitiveBlock = this.options.clickElement;

            this.data = [];

            if (options.clickElement && options.clickElement.hasClass('data-hide')) {
                this.options.showData = true;
            }

            if (this.options.showData) this.getData();
            
        },
        getAsyncData:function(){
            var _this = this;
            new Request.JSON({url:this.options.url,onComplete:function(resp){
                var data = resp;
                _this.dealData(data);
                _this.fireEvent('asyncData',_this);
            }}).get({});
        },
        getData:function(){
            var data = [];
            new Request.JSON({async: false,url:this.options.url,onComplete:function(resp){
                data = resp;
            }}).get({});
            this.dealData(data);
        },
        dealData:function(data){
            data['resp'] = {};

            if ($defined(data['encrypt_body']) && typeof data['encrypt_body'] === 'object' && 0 < Object.getLength(data['encrypt_body']) ) {
                new Request({
                    async: false,
                    url:data['encrypt_body']['url'],
                    method:'POST',
                    data:data['encrypt_body'],
                    onComplete:function(resp){
                        resp = JSON.decode(resp);
                        data['resp'] = resp;
                        if (resp.rsp=='succ' && resp.data) {
                            var decrypt = resp.data[data['encrypt_body']['tids']];

                            Object.each(JSON.decode(data['encrypt_body']['fields']), function(value, key){
                                if (decrypt[value]) data[key] = decrypt[value];
                            });
                            if(decrypt.virtual_number_type){
                               data['virtual_number_type']     = decrypt.virtual_number_type;
                            }
                            if(decrypt.ship_area && data['ship_addr'].indexOf(decrypt.ship_area) == -1) {
                                data['ship_addr'] = decrypt.ship_area+data['ship_addr'];
                            }
                        }
                        if(resp.order_info){
                            var decrypt = resp.order_info;
                            Object.each(JSON.decode(data['encrypt_body']['fields']), function(value, key){
                                if (decrypt[value]) data[key] = decrypt[value];
                            });
                            if(decrypt.virtual_number_type){
                               data['virtual_number_type']     = decrypt.virtual_number_type;
                               data['virtual_identify_number'] = decrypt.virtual_identify_number;
                            }

                        }
                    }
                }).send();
            }
            console.log(data);
            this.data = data;
        },
        encrypt:function(){},
        decrypt:function(field){
            return this.data[field];
        },
        desHtml:function(){
            var params = Array.flatten(arguments).link({
                'sensitiveBlock': Element.type
            });
            if (params.sensitiveBlock) {
                this.options.sensitiveBlock = params.sensitiveBlock;
            }

            if (this.data.error) return MessageBox.error(this.data.error);

            if (this.options.showData) {
                this.showContent();
            } else {
                this.hideContent();
            }
        },
        showContent:function(){
            if (this.options.clickElement) this.options.clickElement.removeClass('data-hide').addClass('data-show');
            this.options.sensitiveBlock.getElements(':sensitive-field').each(function(item){
                var value = this.decrypt(item.get('sensitive-field'));
                if (!value) return ;

                switch (item.tagName){
                    case 'INPUT':
                        item.value = value;

                        break;
                    default:
                        item.retrieve(item.get('sensitive-field'),item.getText()); 

                        var virtualNumberType     = this.decrypt('virtual_number_type');
                        var virtualIdentifyNumber = this.decrypt('virtual_identify_number');

                        if(virtualNumberType && value.length == 11 && virtualIdentifyNumber){
                            value = value + '#' + virtualIdentifyNumber;
                        }

                        item.setText(value);

                        if(virtualNumberType){
                            var text   = ' 隐私号可用于发货/联系买家,与真实手机号用法一致';
                            var desc  =  '<span style="font-style: italic;">'+'隐私号可用于'+"<span style='color:#FFA500;'>发货/联系买家</span>"+',与真实手机号用法一致'+'</br>'+'<span style="font-weight:900">'+'直接联系买家'+'</span>'+'</br>'+'直接拨打隐私号，听到语音提示'+"<span style='color:#FFA500;'>输入姓名/地址后的4位分机号</span>"+'</br>'+'<span style="font-weight:900">'+'直接发货'+'</span>'+'</br>'+'请务必复制完整的收件人信息'+"<span style='color:#FFA500;'>[含中括号及 4 位数字]</span>"+'</br>'+'<span style="font-weight:900">'+'短信实时转发'+'</span>'+'</br>'+'物流派送后，取件提醒短信会实时转发至买家真实手机号'+'</br>'+'<span style="font-weight:900">'+'买家可查看取件提醒'+'</span>'+'</br>'+'用隐私号发货的包裹，买家可在拼多多/抖音 App 中订单物流详情页查看取件信息。'+'</span>'; 
                            var _this =   this;
                            item.adopt(new Element('span', {
                                text: text,
                                rel:  desc,
                                styles:{
                                    'font-style':'italic',
                                    'color': 'gray'
                                },
                                events:{
                                    mouseover:function(e){
                                        _this.noticeTips(e);
                                    }
                                }
                            }));  
                        } 
                        if(item.get('sensitive-field') == 'ship_mobile' && this.decrypt('privacy_protection')) {
                            let text="   默认有效期<span style='color:#FFA500;'>"+this.decrypt('secret_no_expire_time')+"</span>，但会因为消费者签收后提前失效  "+'<a target="_blank" style="color:blue" href="https://huodong.taobao.com/wow/z/mt/default/HmZpDk4CTanZyJKDeEps?spm=a1zfx5.my_create_page.0.0.fde22251cebrhn">了解虚拟号</a>';
                            let _this =   this;
                            item.adopt(new Element('span', {
                                html: text,
                                styles:{
                                    'font-style':'italic',
                                    'color': 'gray'
                                },
                            }));  
                        }
                        
                        break;
                }
            },this);
        },
        hideContent:function(){
            this.options.clickElement.removeClass('data-show').addClass('data-hide');
            this.options.sensitiveBlock.getElements(':sensitive-field').each(function(item){
                item.setText(item.retrieve(item.get('sensitive-field')));
            });
        },
        noticeTips:function(e){
           var notice = new Tips({
                onShow:function(tip,el){
                    el.addClass('active');
                    tip.setStyle('display','block');
                },
                text: function(element){
                    return element.get('title') || element.get('rel');
                }
            });

            var e  = new Event(e), el = e.target;
            notice.attach(el);
            el.addEvent('mouseleave',function(){
                el.removeClass('active');
            });
            el.fireEvent('mouseenter',e); 
        }
    });
})();