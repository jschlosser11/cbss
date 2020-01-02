<?php
/**
 * @copyright © TMS-Plugins. All rights reserved.
 * @licence   See LICENCE.md for license details.
 */

?>

<script>
  var hasBookingShortcode = (typeof hasBookingShortcode === 'undefined') ? false : true
  var bookingEntitiesIds = (typeof bookingEntitiesIds === 'undefined') ? [] : bookingEntitiesIds
  bookingEntitiesIds.push(
    {
      'counter': '<?php echo $atts['counter']; ?>',
      'category': '<?php echo $atts['category']; ?>',
      'service': '<?php echo $atts['service']; ?>',
      'employee': '<?php echo $atts['employee']; ?>',
      'location': '<?php echo $atts['location']; ?>'
    }
  )
</script>

<div id="amelia-app-booking<?php echo $atts['counter']; ?>" class="amelia-booking amelia-frontend amelia-app-booking">
  <booking id="amelia-step-booking<?php echo $atts['counter']; ?>"></booking>
</div>
