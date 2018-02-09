var multi = [];
jQuery(document).ready(function($){
   $("#imageitem").change(function() {
      $("#imageitemPreview").empty();

      var filename = document.imageitemform.elements['img'].options[document.imageitemform.elements['img'].selectedIndex].value;
      var fileExt = filename.split('.').pop();
      if ( $("#imageitem").val()!=0 ) {
         link = "<object width='60' height='60' data='map.send.php?file=" + filename + "&type=pics'  type='image/" + fileExt + "'><param name='src' value='map.send.php?file=" + filename + "&type=pics'></object>";
         $("#imageitemPreview").append(link);

      }
   });
});
