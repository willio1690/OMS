
(function (global) {

  var PAGES = [
    { name: '首页', url: 'index.php?ctl=dashboard&act=index' },
    { name: '新增基础物料', url: 'index.php?app=material&ctl=admin_material_basic&act=add&_finder[finder_id]=6a8687&finder_id=6a8687' },
    { name: '新建订单', url: 'index.php?app=ome&ctl=admin_order&act=addNormalOrder&_finder[finder_id]=ae731e&finder_id=ae731e' },
    { name: '新建销售物料', url: 'index.php?app=material&ctl=admin_material_sales&act=add&_finder[finder_id]=1bd334&finder_id=1bd334' }
  ]

  var HTML_CACHE = {}

  var TABS_CACHE_KEY = 'TABS_CACHE_KEY'
  var container = document.getElementById('header-tab-list')
  var ctlRight = document.getElementsByClassName('tabs-ctl-right')[0]
  var ctlLeft = document.getElementsByClassName('tabs-ctl-left')[0]

  function isCurrent(url) {
      var currentArr = location.hash.substring(1).split("&")
      var current = currentArr.slice(0,currentArr.length - 1).join("&")
      var urlArr = url.split("?")[1].split("&")
      return current === urlArr.slice(0,urlArr.length - 1).join("&")
  }

  function TabsController() {
    this.tabs = []
    this.init()
  }

  TabsController.prototype.init = function () {
    var cache = localStorage.getItem(TABS_CACHE_KEY)
    this.tabs = cache ? JSON.parse(cache) : []
  }

  /**
   * 添加一个新的tab
   *
   * @param tab
   * @param tab.name
   * @param tab.url
   */
  TabsController.prototype.add = function (tab) {
    var exist = this.tabs.findIndex(function (f) { return f.url === tab.url })
    if (exist === -1) {
      this.tabs.push(tab)
    }
    localStorage.setItem(TABS_CACHE_KEY, JSON.stringify(this.tabs))
  }

  /**
   * 关闭当前tab，并且跳转到下一个tab
   *
   * @param url
   */
  TabsController.prototype.close = function (url) {
    var index = this.tabs.findIndex(function (f) { return f.url === url })
    this.tabs.splice(index, 1)
    var next = this.tabs[index - 1] ? index - 1 : index

    localStorage.setItem(TABS_CACHE_KEY, JSON.stringify(this.tabs))

    if (this.tabs.length === 0) {
      location.href = "/"
    } else {
      var isUpdate = container.children[next] && isCurrent(url)
      this.flush()
      if (isUpdate) {
        setTimeout(function () {
          container.children[next].firstElementChild.click()
        })
      }
    }
  }

  /**
   * 关闭左侧tab
   * @param index
   */
  TabsController.prototype.closeLeft = function(index) {
    this.tabs.filter((f, i) => i < index).forEach((f) => tabsController.close(f.url))
  }

  /**
   * 关闭右侧tab
   * @param index
   */
   TabsController.prototype.closeRight = function(index) {
    this.tabs.filter((f, i) => i > index).forEach((f) => tabsController.close(f.url))
  }
  /**
   * 关闭其他tab
   * @param index
   */
   TabsController.prototype.closeOther = function(index) {
    const url = this.tabs[index].url
    this.tabs.filter((f) =>  f.url !== url).forEach((f) => tabsController.close(f.url))
  }
  /**
   * 刷新tab
   */
  TabsController.prototype.flush = function () {
    var template = []
    var currentElement
    for (let i = 0; i < this.tabs.length; i++) {
      var tab = this.tabs[i]

      var div = document.createElement('div')
      var a = document.createElement('a')
      var icon = document.createElement('i')

      div.setAttribute('title', tab.name)
      a.innerText = tab.name
      icon.classList.add('iconfont')
      icon.classList.add('icon-guanbi1')

      a.href = tab.url
      div.classList.add('header-tabs-item')
      div.oncontextmenu = (e) => global.onContextmenu(e, i)
      if (isCurrent(tab.url)) {
        div.classList.add('header-tabs-active')
        currentElement = a
      }

      div.appendChild(a)
      div.appendChild(icon)
      template.push(div)
      bindCloseIconClick(icon, i)
      onClickTag(a, tab.url)
    }

    container.innerHTML = ''
    template.forEach(function (el) {
      container.appendChild(el)
    })

    if(currentElement) {
      currentElement.parentElement.scrollIntoView()
    }

    if (container.scrollWidth > container.offsetWidth) {
      ctlRight.style.display = 'block'
      ctlLeft.style.display = 'block'
    } else {
      ctlRight.style.display = 'none'
      ctlLeft.style.display = 'none'
    }
  }

  var tabsController = new TabsController()
  tabsController.flush()
  global.tabsController = tabsController

  var pushState = window.history.pushState
  window.history.pushState = function (...args) {
    pushState.call(window.history, ...args)
    flushCurrentTab()
  }

  window.addEventListener('resize', function () {
    tabsController.flush()
  })

  /**
   * @param {HTMLElement>} a
   * @param url
   */
  function onClickTag(a, url) {
    // a.addEventListener('click', function (e) {
    //   var container = document.getElementById('content-container')
    //   var tab = getCurrentTab()
    //   HTML_CACHE[tab.url] = container.cloneNode(true)
    //
    //   if (HTML_CACHE[url]) {
    //     e.preventDefault()
    //     e.cancelable = true
    //     e.stopPropagation()
    //
    //     container.parentElement.replaceChild(HTML_CACHE[url], container)
    //     container.innerHTML = HTML_CACHE[url]
    //     location.hash = '#' + url.split("?")[1]
    //     flushCurrentTab()
    //   }
    // })
  }

  /**
   * 关闭按钮
   *
   * @param {HTMLElement>} icon
   * @param {Number} index
   */
  function bindCloseIconClick(icon, index) {
    icon.addEventListener('click', function (e) {
      e.cancelable = true
      e.preventDefault()
      tabsController.close(tabsController.tabs[index].url)
    })
  }


  /**
   * 获取当前的菜单
   */
  function getCurrentTab() {
    return tabsController.tabs.find(function (f) {
      return isCurrent(f.url)
    })
  }

  function flushCurrentTab() {
    var index = tabsController.tabs.findIndex(function (f) {
      return isCurrent(f.url)
    })

    if (index === -1 && window.systemMenus) {
      // 所有的子菜单
      var arrMenuItems = window.systemMenus.reduce(function (subs, item) {
        var children = item.menus.reduce(function (arr, current) {
          return arr.concat(current.menus)
        }, [])
        return subs.concat(children)
      }, [])

      // 子菜单 + 自定义的页面配置
      var allItems = [].concat(arrMenuItems).concat(PAGES)

      for (var i = 0; i < allItems.length; i++) {
        var item = allItems[i]
        if (isCurrent(item.url)) {
          tabsController.add(item)
          break
        }
      }
    }
    tabsController.flush()
  }

  /**
   * 左右移动按钮
   */
  var step = container.offsetWidth * 0.5
  ctlRight.addEventListener('click', function () {
    container.scrollLeft = container.scrollLeft + step
  })

  ctlLeft.addEventListener('click', function () {
    container.scrollLeft = container.scrollLeft - step
  })
})(window)
