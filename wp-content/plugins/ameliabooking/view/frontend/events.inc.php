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
      'eventId': '<?php echo $atts['event']; ?>',
      'eventRecurring': <?php echo $atts['recurring'] ? 1 : 0; ?>,
      'eventTag': '<?php echo $atts['tag']; ?>'
    }
  )
</script>

<div id="amelia-app-booking<?php echo $atts['counter']; ?>" class="amelia-service amelia-frontend amelia-app-booking">
	<events></events>
</div>
