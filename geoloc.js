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

