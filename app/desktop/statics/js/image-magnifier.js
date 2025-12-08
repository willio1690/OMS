

/**
 * 图片放大镜组件 - 完整版
 * 基于 MooTools 的 Tips 和 Asset.image，支持智能缩放和动态尺寸调整
 */

(function(){
    'use strict';
    
    // 检查 MooTools 依赖 - 更宽松的检查
    if (typeof Class === 'undefined') {
        console.error('ImageMagnifier: 需要 MooTools Core 支持');
        return;
    }
    
    // 图片放大镜类
    var ImageMagnifier = new Class({
        
        // 默认配置
        options: {
            maxWidth: 250,           // 最大显示宽度
            maxHeight: 150,          // 最大显示高度
            maxScale: 3,             // 最大放大倍数
            offset: {x: 30, y: -20}, // 位置偏移
            className: 'magnifier-tip', // 自定义样式类
            showDelay: 0,            // 显示延迟(ms)
            hideDelay: 0,            // 隐藏延迟(ms)
            fixed: false,            // 是否固定位置
            fade: true,              // 是否使用淡入淡出
            onShow: null,            // 显示回调
            onHide: null,            // 隐藏回调
            onLoad: null             // 图片加载完成回调
        },
        
        // 初始化
        initialize: function(element, options) {
            this.element = $(element);
            this.options = Object.merge(this.options, options || {});
            this.tip = null;
            this.isActive = false;
            
            this._createTip();
            this._attachEvents();
            this._addStyles();
        },
        
        // 创建提示框
        _createTip: function() {
            // 检查是否有 Tips 支持，如果没有则使用简化版本
            if (typeof Tips !== 'undefined') {
                this.tip = new Tips(this.element, {
                    className: this.options.className,
                    showDelay: this.options.showDelay,
                    hideDelay: this.options.hideDelay,
                    fixed: this.options.fixed,
                    fade: this.options.fade,
                    onShow: this._onShow.bind(this),
                    onHide: this._onHide.bind(this),
                    text: function() { return '&nbsp;'; }
                });
            } else {
                // 简化版本：直接绑定鼠标事件
                this._createSimpleTip();
            }
        },
        
        // 创建简化版提示框
        _createSimpleTip: function() {
            this.tip = {
                element: null,
                show: function() { this.element.style.display = 'block'; },
                hide: function() { this.element.style.display = 'none'; },
                detach: function() { 
                    if (this.element && this.element.parentNode) {
                        this.element.parentNode.removeChild(this.element);
                    }
                }
            };
            
            // 创建提示框元素
            this.tip.element = new Element('div', {
                'class': this.options.className,
                'style': 'position:fixed;z-index:999999;display:none;background:transparent;border:none;padding:0;max-width:' + this.options.maxWidth + 'px;max-height:' + this.options.maxHeight + 'px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.15);border-radius:6px;pointer-events:none;'
            });
            
            document.body.appendChild(this.tip.element);
            
            // 绑定显示/隐藏事件
            this.element.addEvent('mouseenter', this._onShow.bind(this));
            this.element.addEvent('mouseleave', this._onHide.bind(this));
            this.element.addEvent('mousemove', this._updatePosition.bind(this));
        },
        
        // 显示事件处理
        _onShow: function(tip, element) {
            this.isActive = true;
            element = element || this.element;
            element.addClass('magnifier-active');
            
            var imgSrc = element.get('ref') || element.src;
            
            if (this.tip.element) {
                // 简化版本
                this.tip.element.store('tip:imgsource', imgSrc);
                this._loadImage(imgSrc, this.tip.element);
                this.tip.show();
            } else if (tip) {
                // Tips 版本
                tip.setStyle('display', 'block').store('tip:imgsource', imgSrc);
                var tipContent = tip.getElement('.tip-text');
                if (!tipContent) {
                    tipContent = new Element('div', {'class': 'tip-text'}).inject(tip.getElement('.tip') || tip);
                }
                tipContent.set('html', '&nbsp;').addClass('loading');
                this._loadImage(imgSrc, tipContent);
            }
            
            // 触发显示回调
            if (this.options.onShow) {
                this.options.onShow.call(this, tip || this.tip, element);
            }
        },
        
        // 隐藏事件处理
        _onHide: function(tip, element) {
            this.isActive = false;
            element = element || this.element;
            if (element) element.removeClass('magnifier-active');
            
            if (this.tip.hide) {
                this.tip.hide();
            }
            
            // 触发隐藏回调
            if (this.options.onHide) {
                this.options.onHide.call(this, tip || this.tip, element);
            }
        },
        
        // 加载图片
        _loadImage: function(imgSrc, container) {
            if (typeof Asset !== 'undefined' && Asset.image) {
                // 使用 Asset.image 加载
                Asset.image(imgSrc, {
                    onload: function() {
                        if (this.src != container.retrieve('tip:imgsource')) return;
                        
                        // 计算智能缩放尺寸
                        var zoomedSize = this._calculateZoomSize(this.width, this.height);
                        
                        container.empty().adopt(this).removeClass('loading');
                        this.setStyles({
                            'width': zoomedSize.width + 'px',
                            'height': zoomedSize.height + 'px',
                            'max-width': '100%',
                            'max-height': '100%',
                            'object-fit': 'contain'
                        });
                        
                        // 触发加载完成回调
                        if (this.options.onLoad) {
                            this.options.onLoad.call(this, this, zoomedSize);
                        }
                    }.bind(this),
                    onerror: function() {
                        container.set('html', '图片加载失败').removeClass('loading');
                    }
                });
            } else {
                // 简化版本：直接创建图片元素
                var img = new Element('img', {
                    'src': imgSrc,
                    'style': 'max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain;border-radius:6px;'
                });
                
                container.empty().adopt(img);
                
                // 图片加载完成后计算尺寸
                img.addEvent('load', function() {
                    var zoomedSize = this._calculateZoomSize(img.width, img.height);
                    img.setStyles({
                        'width': zoomedSize.width + 'px',
                        'height': zoomedSize.height + 'px'
                    });
                    
                    if (this.options.onLoad) {
                        this.options.onLoad.call(this, img, zoomedSize);
                    }
                }.bind(this));
            }
        },
        
        // 计算智能缩放尺寸
        _calculateZoomSize: function(originalWidth, originalHeight) {
            var maxW = this.options.maxWidth;
            var maxH = this.options.maxHeight;
            var maxScale = this.options.maxScale;
            
            // 计算缩放比例
            var scaleX = maxW / originalWidth;
            var scaleY = maxH / originalHeight;
            var scale = Math.min(scaleX, scaleY, maxScale);
            
            // 限制最大缩放倍数
            if (scale > maxScale) {
                scale = maxScale;
            }
            
            return {
                width: Math.round(originalWidth * scale),
                height: Math.round(originalHeight * scale)
            };
        },
        
        // 绑定事件
        _attachEvents: function() {
            // 鼠标移动时更新位置
            this.element.addEvent('mousemove', function(e) {
                if (this.isActive && this.tip) {
                    this._updatePosition(e);
                }
            }.bind(this));
        },
        
        // 更新位置
        _updatePosition: function(event) {
            if (!this.tip) return;
            
            var tipElement = this.tip.tip || this.tip.element;
            if (!tipElement) return;
            
            var offset = this.options.offset;
            
            tipElement.setStyles({
                'left': (event.pageX + offset.x) + 'px',
                'top': (event.pageY + offset.y) + 'px'
            });
        },
        
        // 添加样式
        _addStyles: function() {
            // 检查是否已经添加了样式
            if (document.getElementById('image-magnifier-styles')) return;
            
            var styleElement = new Element('style', {
                'id': 'image-magnifier-styles',
                'html': `
                    .magnifier-tip { position: fixed !important; z-index: 999999 !important; background: transparent !important; border: none !important; padding: 0 !important; max-width: 600px !important; max-height: 600px !important; overflow: hidden !important; box-shadow: 0 4px 20px rgba(0,0,0,0.15) !important; border-radius: 6px !important; pointer-events: none !important; }
                    .magnifier-tip img { max-width: 100% !important; max-height: 100% !important; width: auto !important; height: auto !important; object-fit: contain !important; border-radius: 6px !important; display: block !important; }
                    .magnifier-active { opacity: 0.8 !important; transition: opacity 0.2s ease-in-out !important; cursor: zoom-out !important; }
                    img[rel], img[data-src], img.zoomable { cursor: zoom-in !important; transition: opacity 0.2s ease-in-out !important; }
                    img[rel]:hover, img[data-src]:hover, img.zoomable:hover { opacity: 0.9 !important; }
                `
            });
            
            document.head.appendChild(styleElement);
        },
        
        // 更新配置
        updateOptions: function(newOptions) {
            Object.merge(this.options, newOptions);
            
            // 重新创建提示框
            if (this.tip) {
                this.tip.detach();
            }
            this._createTip();
            this._attachEvents();
        },
        
        // 销毁
        destroy: function() {
            if (this.tip) {
                this.tip.detach();
                this.tip = null;
            }
            this.element.removeClass('magnifier-active');
            this.isActive = false;
        },
        
        // 强制隐藏
        hideTips: function() {
            if (this.tip && this.tip.hide) {
                this.tip.hide();
            }
        }
    });
    
    // 静态方法：批量应用
    ImageMagnifier.applyToElements = function(selector, options) {
        var elements = $$(selector);
        var magnifiers = [];
        
        elements.each(function(element) {
            magnifiers.push(new ImageMagnifier(element, options));
        });
        
        return magnifiers;
    };
    
    // 导出到全局
    window.ImageMagnifier = ImageMagnifier;
    
    // 保持向后兼容的全局函数
    var magnifierTip = null;
    var offsetX = 50;
    var offsetY = -30;
    
    // 显示放大镜 - 全局函数，可在HTML中直接调用
    function showImageMagnifier(event, element) {
        if (magnifierTip) {
            magnifierTip.remove();
            magnifierTip = null;
        }
        
        magnifierTip = document.createElement('div');
        magnifierTip.className = 'magnifier-tip';
        magnifierTip.style.cssText = 'position:fixed;z-index:999999;background:transparent;border:none;padding:0;max-width:400px;max-height:300px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.15);border-radius:4px;pointer-events:none;';
        
        var magnifiedImg = document.createElement('img');
        magnifiedImg.src = element.getAttribute('ref') || element.src;
        magnifiedImg.style.cssText = 'max-width:400px;max-height:300px;width:auto;height:auto;object-fit:contain;border-radius:4px;';
        
        magnifierTip.appendChild(magnifiedImg);
        document.body.appendChild(magnifierTip);
        
        updateImageMagnifierPosition(event);
    }
    
    // 隐藏放大镜 - 全局函数，可在HTML中直接调用
    function hideImageMagnifier() {
        if (magnifierTip) {
            magnifierTip.remove();
            magnifierTip = null;
        }
    }
    
    // 更新放大镜位置 - 全局函数，可在HTML中直接调用
    function updateImageMagnifierPosition(event) {
        if (!magnifierTip) return;
        magnifierTip.style.left = (event.pageX + offsetX) + 'px';
        magnifierTip.style.top = (event.pageY + offsetY) + 'px';
    }
    
    // 导出全局函数
    window.showImageMagnifier = showImageMagnifier;
    window.hideImageMagnifier = hideImageMagnifier;
    window.updateImageMagnifierPosition = updateImageMagnifierPosition;
    
    // 移除自动初始化，改为手动控制
    // 用户需要在HTML中手动添加事件处理函数
    
})();

