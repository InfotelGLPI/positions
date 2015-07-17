Ext.ns('Position');
Position = {
  init:function()
  {
      /* just for demo */
  },

  openWindow:function(url)
  {
      var win = new Ext.Window({
          width:1400,
          height:650,
          //title:'Site:' + url,
          autoScroll:true,
          modal:true
      });

      var iframeid = win.getId() + '_iframe';

      var iframe = {
          id:iframeid,
          tag:'iframe',
          src:url,
          width:'100%',
          height:'100%',
          frameborder:0
      }

      // show first
      win.show();
      // then iframe
      Ext.DomHelper.insertFirst(win.body, iframe)

  }
}

//Ext.onReady(function() {
//   Position.init();
//   Ext.select('a[href*=user.form.php]').each(function(el){
//      var href = el.getAttribute('href');
//      var users_id = href.substring(href.lastIndexOf('id=')+3, href.length);
//      var target = "../plugins/positions/front/geoloc.php?users_id="+users_id;
//
//      el.insertHtml(
//         'afterEnd',
//         '<a href=\"#\" onclick=\"Position.openWindow(\''+target+'\')\"><img src="../plugins/positions/pics/sm_globe.png" /></a>'
//      );
//   });
//});