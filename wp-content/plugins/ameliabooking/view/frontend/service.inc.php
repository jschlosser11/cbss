<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

?>
<script>
  var bookingEntitiesIds = (typeof bookingEntitiesIds === 'undefined') ? [] : bookingEntitiesIds
  bookingEntitiesIds.push(
    {
      'counter': '<?php echo $atts['counter']; ?>',
      'service': '<?php echo $atts['service']; ?>',
      'employee': '<?php echo $atts['employee']; ?>',
      'location': '<?php echo $atts['location']; ?>'
    }
  )
</script>

<div id="amelia-app-booking<?php echo $atts['counter']; ?>" class="amelia-service amelia-frontend amelia-app-booking">
  <service></service>
</div>
