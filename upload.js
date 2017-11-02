jQuery(document).ready(function($){
   if(document.getElementById('plugin_position_container')) {
      var token = 0;
      if(document.getElementsByName("_glpi_csrf_token").item(0) != undefined) {
         var token = document.getElementsByName("_glpi_csrf_token").item(0).value;
      }


       var uploader = new plupload.Uploader({
           runtimes : 'html5, html4',
           browse_button : 'pickfiles',
           container : 'plugin_position_container',
           max_file_size : '1mb',
           url : 'upload.php',
           //flash_swf_url : '../lib/plupload/plupload.flash.swf',
           silverlight_xap_url : '/plupload/js/plupload.silverlight.xap',
           filters : [
               {title : "Image files", extensions : "jpg,gif,png"},
           ],
           resize : {width : 35, height : 35, quality : 90},
           multipart:           true,
           multipart_params: {
               _glpi_csrf_token : token
           }
       });



      /*uploader.bind('Init', function(up, params) {
         $('#filelist').html("<div>Current runtime: " + params.runtime + "</div>");
      });*/

      $('#uploadfiles').click(function(e) {
         uploader.start();
         e.preventDefault();
      });

      uploader.init();

      uploader.bind('FilesAdded', function(up, files) {
         $.each(files, function(i, file) {
            $('#filelist').append(
               '<div id="' + file.id + '">' +
               file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
            '</div>');
         });

         up.refresh(); // Reposition Flash/Silverlight
      });

      uploader.bind('UploadProgress', function(up, file) {
         $('#' + file.id + " b").html(file.percent + "%");
      });

      uploader.bind('Error', function(up, err) {
         $('#filelist').append("<div>Error: " + err.code +
            ", Message: " + err.message +
            (err.file ? ", File: " + err.file.name : "") +
            "</div>"
         );

         up.refresh(); // Reposition Flash/Silverlight
      });

      uploader.bind('FileUploaded', function(up, file) {
         $('#' + file.id + " b").html("100%");
      });
	}
});