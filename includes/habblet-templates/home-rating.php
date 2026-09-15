<?php
$summary = $homes->ratingSummary((int) $ownerId);
$widgetId = (int) $widgetId;
$canVote = (int) $user->id > 0 && !$summary['owner'] && !$summary['mine'];
?>
<div id="rating-main">
<?php if ($canVote) { ?>
<script type="text/javascript">
	var ratingWidget;
	document.observe("dom:loaded", function() {
		ratingWidget = new RatingWidget(<?php echo (int) $ownerId; ?>, <?php echo $widgetId; ?>);
	});
</script>
<div class="rating-average">
		<b><?php echo $lang->loc['cast.vote'] ?? 'Click on the stars to cast your vote!'; ?></b>
	<div id="rating-stars" class="rating-stars">
				<ul id="rating-unit_ul1" class="rating-unit-rating">
				<li class="rating-current-rating" style="width:0px;" />
					<li><a href="#" class="r1-unit rater">1</a></li>
					<li><a href="#" class="r2-unit rater">2</a></li>
					<li><a href="#" class="r3-unit rater">3</a></li>
					<li><a href="#" class="r4-unit rater">4</a></li>
					<li><a href="#" class="r5-unit rater">5</a></li>
			</ul>
	</div>
	<?php echo (int) $summary['total']; ?> <?php echo $lang->loc['votes.total'] ?? 'votes total'; ?>
	<br/>
	(<?php echo (int) $summary['high']; ?> <?php echo $lang->loc['high.votes.total'] ?? 'users voted 4 or better'; ?>)
</div>
<?php } else { ?>
<script type="text/javascript">
	var ratingWidget;
	ratingWidget = new RatingWidget(<?php echo (int) $ownerId; ?>, <?php echo $widgetId; ?>);
</script>
<div class="rating-average">
		<b><?php echo $lang->loc['average.rating'] ?? 'Average rating'; ?>: <?php echo htmlspecialchars((string) $summary['average'], ENT_QUOTES, 'UTF-8'); ?></b><br/>
	<div id="rating-stars" class="rating-stars">
				<ul id="rating-unit_ul1" class="rating-unit-rating">
				<li class="rating-current-rating" style="width:<?php echo (int) $summary['px']; ?>px;" />
			</ul>
	</div>
	<?php echo (int) $summary['total']; ?> <?php echo $lang->loc['votes.total'] ?? 'votes total'; ?>
	<br/>
	(<?php echo (int) $summary['high']; ?> <?php echo $lang->loc['high.votes.total'] ?? 'users voted 4 or better'; ?>)
</div>
<?php } ?>
</div>
