<{script src="coms/pager.js"  app='desktop'}>
<{script src="coms/modedialog.js" app="desktop"}>
<{script src="coms/autocompleter.js" app="desktop"}>

<{if !$accountsafy}>
<script>
    window.addEvent('domready', setTimeout(function() {
        new Dialog("index.php?ctl=dashboard&act=perfectAccount", {
            width:600,
            height:340,
            title:"账户安全",
            onLoad:function(e){
                $E('.btn-close',e.dialog_head).style.display = 'none';
            }
        });
    }, 5000));
</script>
<{/if}>