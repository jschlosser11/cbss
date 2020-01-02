<?php defined('ABSPATH') or die('No script kiddies please!'); ?>
<!--suppress JSUnusedLocalSymbols -->
<script>
  var wpAmeliaUploadsAmeliaURL = '<?php echo UPLOADS_AMELIA_FILES_URL; ?>'
  var wpAmeliaUseUploadsAmeliaPath = '<?php echo UPLOADS_AMELIA_FILES_PATH_USE; ?>'
  var wpAmeliaPluginURL = '<?php echo AMELIA_URL; ?>'
  var wpAmeliaPluginAjaxURL = '<?php echo AMELIA_ACTION_URL; ?>'
  var wpAmeliaPluginStoreURL = '<?php echo AMELIA_STORE_API_URL; ?>'
  var wpAmeliaSiteURL = '<?php echo AMELIA_SITE_URL; ?>'
  var menuPage = '<?php echo isset($page) ? (string)$page : ''; ?>'
</script>
<div id="amelia-app-backend" class="amelia-booking">
  <transition name="fade">
    <router-view></router-view>
  </transition>
</div>
