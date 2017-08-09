<?php
require_once('./../../../../wp-config.php');

header('Content-Type: application/javascript; charset=utf-8');
?>

jQuery(document).ready( function() {
  jQuery('#commentform').addClass('hcard-commenting');
  jQuery('#comment-form').addClass('hcard-commenting');

  var html = ' <a id="hcard_enabled_link" href="http://microformats.org/wiki/hCard">(import hCard)</a> '
           + ' <span id="ajax-loader" style="display: none;">Loading hCard</span> ';

  if (jQuery('form.hcard-commenting a#hcard_enabled_link').length == 0) {
    jQuery('form.hcard-commenting input#url').next().append(html);
  }

  jQuery('#hcard_enabled_link').click( function() {
    var url = jQuery("form.hcard-commenting input#url").val();
    if (url == "") {
      jQuery("form.hcard-commenting input#url").val("use this field for your hCard url");
    } else {
      jQuery.ajax({
        type: "GET",
        url: "<?php echo parse_url(get_option('siteurl'), PHP_URL_PATH); ?>/index.php",
        data: {hcard_url: url},
        dataType: "json",
        timeout: 25000,
        error: function(XMLHttpRequest, textStatus, errorThrown) { alert(textStatus + ": " + errorThrown); },
        success: function(data) {
          if (data.vcard.fn != "") {
            jQuery("form.hcard-commenting input#author").val(data.vcard.fn);
          }
          if (data.vcard.email != "") {
            jQuery("form.hcard-commenting input#email").val(data.vcard.email);
          }
          if (data.vcard.url != "") {
            jQuery("form.hcard-commenting input#url").val(data.vcard.url);
          }
        }
      });
    }

    return false;
  });

  jQuery("#ajax-loader").ajaxStart(function(){
    jQuery(this).show();
    jQuery('#hcard_enabled_link').hide();
  });

  jQuery("#ajax-loader").ajaxStop(function(){
    jQuery(this).hide();
    jQuery('#hcard_enabled_link').show();
  });

  jQuery('form.hcard-commenting input#url').click( function() {
    var url = jQuery("form.hcard-commenting input#url").val();
    if (url == "use this field for your hCard url") {
      jQuery("form.hcard-commenting input#url").val("");
    }
  });
});