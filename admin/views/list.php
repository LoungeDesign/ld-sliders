<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap ld-sliders-wrap">

	<div class="ld-sliders-header">
		<div class="ld-sliders-header__inner">
			<div class="ld-sliders-header__brand">
				<span class="dashicons dashicons-slides"></span>
				<h1>LD Sliders</h1>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ld-sliders-new' ) ); ?>" class="ld-btn ld-btn--primary">
				<span class="dashicons dashicons-plus-alt2"></span> Add New Slider
			</a>
		</div>
	</div>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Slider deleted.</p></div>
	<?php endif; ?>

	<?php if ( empty( $sliders ) ) : ?>
		<div class="ld-empty-state">
			<span class="dashicons dashicons-slides ld-empty-state__icon"></span>
			<h2>No sliders yet</h2>
			<p>Create your first slider to get started.</p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ld-sliders-new' ) ); ?>" class="ld-btn ld-btn--primary">Create Slider</a>
		</div>
	<?php else : ?>

		<div class="ld-how-to-use">
			<strong>How to use:</strong> Create a slider below, copy its class, then add that class to any container div in your page builder. Add child divs with the class <code>ld-carousel-cell</code> inside it — that's it.
		</div>

		<table class="wp-list-table widefat fixed striped ld-sliders-table">
			<thead>
				<tr>
					<th>Name</th>
					<th>Add this class to your container</th>
					<th>Cell class (always the same)</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sliders as $slider ) :
					$edit_url   = add_query_arg( array( 'page' => 'ld-sliders-new', 'id' => $slider->id ), admin_url( 'admin.php' ) );
					$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'ld_slider_delete', 'id' => $slider->id ), admin_url( 'admin-post.php' ) ), 'ld_slider_delete_' . $slider->id );
				?>
				<tr>
					<td><strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($slider->name); ?></a></strong></td>
					<td><code class="ld-copyable">ld-slider-<?php echo absint($slider->id); ?></code></td>
					<td><code class="ld-copyable">ld-carousel-cell</code></td>
					<td><?php echo esc_html( date_i18n( get_option('date_format'), strtotime($slider->created_at) ) ); ?></td>
					<td class="ld-actions">
						<a href="<?php echo esc_url($edit_url); ?>" class="ld-btn ld-btn--sm">Edit</a>
						<a href="<?php echo esc_url($delete_url); ?>" class="ld-btn ld-btn--sm ld-btn--danger" onclick="return confirm('Delete this slider?')">Delete</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

	<?php endif; ?>

	<p class="ld-footer-credit">LD Sliders by <a href="https://www.loungedesign.co.uk" target="_blank" rel="noopener">Lounge Design</a></p>
</div>
