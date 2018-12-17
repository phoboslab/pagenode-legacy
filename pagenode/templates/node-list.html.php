<?php $title = f($type, 'TitleCase', 'Pluralize'); include(PN_TEMPLATES.'head.html.php') ?>

<div class="row list-head">
	<a href="<?=PN_ABS?>admin/nodes/<?= f($type::PathName()) ?>/new">
		+ New <?= f($type, 'TitleCase') ?>
	</a>
	<form action="<?=PN_ABS?>admin/nodes/<?= f($type::PathName()) ?>" method="GET" class="search-bar">
		<?php if (!empty($_GET['q'])) { ?>
			<a href="<?=PN_ABS?>admin/nodes?type=<?= f($type::PathName()) ?>" class="cancel">×</a>
		<?php } ?>
		<select name="tags">
			<option value="0">Title</option>
			<option value="1" <?php if (!empty($_GET['tags'])) {?>selected<?php }?>>Tags</option>
		</select>
		<input 
			type="text" name="q" placeholder="" 
			value="<?= f($_GET['q'] ?? '') ?>"/>

		<button class="button" role="submit">Search</button>
	</form>
</div>

<?php if (!empty($nodes)) { ?>
	<table>
		<tr>
			<th>Title</th>
			<th class="min-width-550">Tags</th>
			<th class="date min-width-960">Date</th>
		</tr>
		<?php foreach ($nodes as $n) { ?> 
			<tr <?php if (!$n->active) {?>class="inactive"<?php } ?>>
				<td>
					<a
						href="<?=PN_ABS?>admin/nodes/<?= f($typeName) ?>/<?= f($n->keyword) ?>">
						<?= $n->title ?>
					</a>
				</td>
				<td class="min-width-550">
					<?php foreach ($n->tags as $i => $t) { ?>
						<a
							href="<?=PN_ABS?>admin/nodes/<?= f($typeName) ?>?tags=1&amp;q=<?= $t ?>"
						><?= $t ?></a><?php if ($i+1 !== count($n->tags)) { ?>,<?php } ?>
					<?php } ?>
				</td>
				<td class="nowrap min-width-960 date"><?= $n->date->format(CONFIG::FORMAT_DATE) ?></td>
			</tr>
		<?php } ?>
	</table>
<?php } else { ?>
	<p>
		<em>No nodes found</em>
	</p>
<?php } ?>

<?php include(PN_TEMPLATES.'foot.html.php') ?>
