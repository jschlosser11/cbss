<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

?>

<script>
  var searchToday = <?php echo $atts['today'] ? 1 : 0; ?>;
  var bookingEntitiesIds = (typeof bookingEntitiesIds === 'undefined') ? [] : bookingEntitiesIds
  bookingEntitiesIds.push(
    {
      'counter': '<?php echo $atts['counter']; ?>',
    }
  )
</script>

<div id="amelia-app-booking<?php echo $atts['counter']; ?>" class="amelia-search amelia-frontend amelia-app-booking">
  <search></search>
</div>
