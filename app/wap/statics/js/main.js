/**
 * Shopex OMS
 * 
 * Copyright (c) 2025 Shopex (http://www.shopex.cn)
 * Licensed under Apache-2.0 with additional terms (See LICENSE file)
 */

//Open the Mobile Front-end Codebase JS
$(document).mobile();
$('form').on('complete.validator', function(e, rs) {
    try {
        rs = JSON.parse(rs);
    }
    catch(e) {}

    if (rs.redirect) {
        if(rs.message) {
            $(document).off('hide.tips')
            .on('hide.tips', function() {
                location.href = rs.redirect;
            });
        }
        else {
            location.href = rs.redirect;
        }
    }
    if(rs.error) {
        return $(document).mobile('tips', 'show', [rs.message, 'msg']);
    }
    if(rs.success) {
        if(rs.message) $(document).mobile('tips', 'show', [rs.message, 'msg']);
    }
})

// if ((/MicroMessenger/i).test(window.navigator.userAgent)) {
//     document.querySelector('[data-topbar]').style.display = 'none';
// }

