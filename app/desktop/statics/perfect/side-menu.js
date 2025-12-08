
function initMenus(json, type) {
  /**
   * 每一个li的高度
   */
  var LI_HEIGHT = 38

  /**
   * 菜单显示类型
   * @type {{GROUP: string, EXPAND: string}}
   */
  var MENU_TYPE = {
    GROUP : 'group',
    EXPAND: 'expand'
  }

  /**
   * 菜单显示类型
   */
  var menuType = type

  /**
   * 标题所占用高度
   */
  var TITLE_HEIGHT = 30

  /**
   * 外层容器padding
   */
  var CONTAINER_PADDING = 20

  /**
   * 距离下一个菜单组的下边距
   */
  var UL_MARGIN_BOTTOM = 20

  /**
   * 首页地址
   */
  var HOME_URL = 'index.php?ctl=dashboard&act=index'

  var CURRENT_MENU_ID_KEY = 'CURRENT_MENU_ID_KEY'

  /**
   * 当前显示的菜单ID
   */
  var currentId = localStorage.getItem(CURRENT_MENU_ID_KEY)

  var menus = menuDataAdapter(json)

  var sideExpand = document.getElementById('side-expand')

  var container = document.getElementById('container')
  var groupClzName = 'side-menu-drop'

  /**
   * @typedef Menu
   * @property {String} name
   * @property {String} id
   * @property {String} icon
   * @property {String} hash
   * @property {String} key
   * @property {SubMenu[]} menus
   */

  /**
   * @typedef SubMenu
   * @property {String} name
   * @property {String} url
   * @property {String} menuId
   * @property {SubMenu[]} menus
   */

  /**
   * 转换数据结构
   *
   * @param data
   * @return {Menu[]}
   */
  function menuDataAdapter(data) {
    var menus = []
    for (var key in data.workground) {
      var item = data.workground[key]
      var menu = {
        name: item.menu_title,
        id: item.menu_id,
        key: key,
        hash: item.menu_path,
        icon: 'iconfont ' + item.icon || 'iconfont icon-caiwuguanli-01',
        menus: []
      }
      menus.push(menu)
    }

    for (var i = 0; i < menus.length; i++) {
      var it = menus[i]
      var me = data.menu[it.key]
      for (var name in me) {
        var group = {
          name: name,
          menus: me[name].map(function (m) {
            return {
              name: m.menu_title,
              url: 'index.php?' + m.menu_path,
              menuId: it.id
            }
          })
        }
        it.menus.push(group)
      }
    }
    return menus
  }

  /**
   * 鼠标移入侧边栏 显示对应的子菜单
   *
   * @param id
   */
  function onmousemoveSide(id) {
    currentId = id

    var currentMenu = menus.filter(function (f) { return f.id === id })[0]
    sideExpand.style.display = 'flex'
    sideExpand.style.padding = CONTAINER_PADDING + 'px 0'

    var groups = []
    for (var n = 0; n < currentMenu.menus.length; n++) {
      var item = currentMenu.menus[n]
      var lis = renderExpandLi(item.menus, LI_HEIGHT)
      var h3 = document.createElement('h3')
      var ul = document.createElement('ul')

      h3.style.height = TITLE_HEIGHT + 'px'
      h3.innerText = item.name
      ul.style.marginBottom = UL_MARGIN_BOTTOM + 'px'
      ul.append(...lis)

      var group = {
        height: lis.length * LI_HEIGHT + TITLE_HEIGHT + UL_MARGIN_BOTTOM,
        nodes: [h3, ul]
      }
      groups.push(group)
    }

    // 单列最大高度
    var MAX_HEIGHT = sideExpand.offsetHeight - CONTAINER_PADDING * 2
    var colHeight = 0
    var columns = []
    var nodes = []
    groups.forEach(function (group, index) {
      if (colHeight >= MAX_HEIGHT || colHeight + group.height > MAX_HEIGHT) {
        columns.push(createColumnNode(nodes))
        nodes = [...group.nodes]
        colHeight = group.height
      } else {
        colHeight += group.height
        nodes.push(...group.nodes)
      }
      if (index === groups.length - 1) {
        columns.push(createColumnNode(nodes))
      }
    })
    sideExpand.innerHTML = ''
    sideExpand.append(...columns)
  }

  /**
   * 创建列节点
   *
   * @param nodes
   * @return {HTMLDivElement}
   */
  function createColumnNode(nodes) {
    var div = document.createElement('div')
    div.classList.add('side-expand-column')
    div.append(...nodes)
    return div
  }

  /**
   * 渲染li标签
   *
   * @param {SubMenu[]} list
   * @param height
   * @return {ChildNode[]}
   */
  function renderExpandLi(list, height) {
    var arr = []
    for (var i = 0; i < list.length; i++) {
      var li = document.createElement('li')
      var a = document.createElement('a')
      a.href = list[i].url
      a.id = list[i].menuId
      a.style.height = height + 'px'
      a.style.lineHeight = height + 'px'
      a.innerText = list[i].name

      if (getFinderId(location.hash.substring(1)) === getFinderId(list[i].url)) {
        a.classList.add('side-current')
      }

      li.appendChild(a)
      arr.push(li)
    }
    return arr
  }

  /**
   * 页面切换
   */
  function onPushState() {
    var rootMenu
    var group
    var menuItem
    menus.forEach(function (f) {
      for (var i = 0; i < f.menus.length; i++) {
        var gr = f.menus[i]
        for (var n = 0; n < gr.menus.length; n++) {
          var me = gr.menus[n]
          var url = me.url.replace('index.php?', '')
          if (url === location.hash.substring(1)) {
            rootMenu = f
            group = gr
            menuItem = me
            break
          }
        }
      }
    })

    if (!rootMenu) {
      renderCrumbs([])
      return
    }

    var crumbs = [
      rootMenu,
      group,
      menuItem
    ]
    renderCrumbs(crumbs)
    var rootLi = document.getElementById('side_' + rootMenu.key)
    var parent = rootLi.parentElement
    for (var i = 0; i < parent.childElementCount; i++) {
      parent.children[i].classList.remove('side-current')
    }
    rootLi.classList.add('side-current')
  }

  /**
   * 更新面包屑导航
   *
   * @param {SubMenu[]} menus
   */
  function renderCrumbs(menus) {
    var crumbsEl = document.getElementById('header-crumbs')
    var home = { name: '首页', url: HOME_URL }
    menus.unshift(home)
    var html = ' <span onclick="history.back()"><i class="iconfont icon-fanhui-01"></i>返回</span><span>|</span>'
    for (var i = 0; i < menus.length; i++) {
      if (menus[i].url === HOME_URL) {
        html += '<a href=' + menus[i].url + ' class="header-crumbs-item">' + menus[i].name + '</a>'
      } else {
        html += '<span class="header-crumbs-item">' + menus[i].name + '</span>'
      }
    }
    crumbsEl.innerHTML = html
  }

  /**
   * 初始化渲染侧边栏
   *
   * @param {Menu[]} list
   */
  function initSideMenus(list) {
    var ul = document.createElement('ul')
    for (var i = 0; i < list.length; i++) {
      var item = list[i]
      var li = document.createElement('li')
      var icon = document.createElement('i')
      var span = document.createElement('span')

      if (menuType === MENU_TYPE.EXPAND) {
        li.onclick = (function (i) {
          return function () {
            onmousemoveSide(list[i].id)
          }
        })(i)
      } else {
        li.onclick = (function (i) {
          return function () {
            onmousemoveSide(list[i].id)
            var menuId = list[i].menus[0].menus[0].menuId
            var el = document.getElementById(menuId)
            el.click()
          }
        })(i)
      }

      icon.className = item.icon
      span.innerText = item.name
      li.id = 'side_' + item.key

      li.append(icon, span)
      ul.appendChild(li)
      setCurrentSubMenu(list)
    }
    var sideMenu = document.getElementById('side-menu')
    sideMenu.innerHTML = ''
    sideMenu.append(ul)
  }

  /**
   * 设置当前一级菜单下的子菜单
   * @param list
   */
  function setCurrentSubMenu(list) {
    if (!container.classList.contains(groupClzName)) {
      return;
    }
    for (var i = 0; i < list.length; i++) {
      var item = list[i]
      if (isEqualCurrent(item)) {
        onmousemoveSide(item.id)
        return
      }
    }
    onmousemoveSide(list[0].id)
  }

  /**
   * 获取hash路径上的finder_vid
   * @param url
   * @return {*}
   */
  function getFinderId(url) {
    return url.split('&').reduce(function (obj, str) {
      var key = str.split('=')[0]
      var val = str.split('=')[1]
      obj[key] = val
      return obj
    }, {})['finder_vid']
  }

  function getSubMenus(menu) {
    return menu.menus.reduce(function (result, sub) {
      var subs = Array.isArray(sub.menus) && sub.menus.length > 0 ? getSubMenus(sub) : [sub]
      return result.concat(subs)
    }, [])
  }

  function isEqualCurrent(menu) {
    var menus = getSubMenus(menu)
    for (var i = 0; i < menus.length; i++) {
      if (getFinderId(location.hash.substring(1)) === getFinderId(menus[i].url)) {
        return true
      }
    }
    return false
  }

  var pushState = window.history.pushState
  window.history.pushState = function (...args) {
    pushState.call(window.history, ...args)
    onPushState()
    setCurrentSubMenu(menus)
  }

  window.addEventListener('resize', function () {
    if (sideExpand.style.display === 'flex') {
      if (currentId) onmousemoveSide(currentId)
    }
  })

  sideExpand.addEventListener('mousemove', onMouseMoveSideExpand)
  function onMouseMoveSideExpand(e) {
    if (container.classList.contains(groupClzName)) {
      return;
    }
    if (sideExpand.style.display !== 'none' && e.target === sideExpand) {
      sideExpand.style.display = 'none'
    }
  }

  document.body.addEventListener('click', onClickBody)
  function onClickBody(e) {
    if (container.classList.contains(groupClzName)) {
      return;
    }
    var side = document.querySelector('#side-menu')
    if (side.contains(e.target)) {
      return
    }
    sideExpand.style.display = 'none'
  }

  if (menuType === MENU_TYPE.EXPAND) {
    container.classList.remove(groupClzName)
    sideExpand.style.display = 'none'
  } else {
    container.classList.add(groupClzName)
  }

  window.systemMenus = menus
  initSideMenus(menus)

}
