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
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Slider deleted.', 'ld-sliders' ); ?></p></div>
	<?php endif; ?>

	<?php if ( empty( $sliders ) ) : ?>
		<div class="ld-empty-state">
			<span class="dashicons dashicons-slides ld-empty-state__icon"></span>
			<h2><?php esc_html_e( 'No sliders yet', 'ld-sliders' ); ?></h2>
			<p><?php esc_html_e( 'Create your first slider to get started.', 'ld-sliders' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=ld-sliders-new' ) ); ?>" class="ld-btn ld-btn--primary">
				<?php esc_html_e( 'Create Slider', 'ld-sliders' ); ?>
			</a>
		</div>
	<?php else : ?>

		<div class="ld-sliders-table-wrap">
			<table class="wp-list-table widefat fixed striped ld-sliders-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'ld-sliders' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'ld-sliders' ); ?></th>
						<th><?php esc_html_e( 'Class', 'ld-sliders' ); ?></th>
						<th><?php esc_html_e( 'Created', 'ld-sliders' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'ld-sliders' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $sliders as $slider ) :
						$edit_url   = add_query_arg( array( 'page' => 'ld-sliders-new', 'id' => $slider->id ), admin_url( 'admin.php' ) );
						$delete_url = wp_nonce_url( add_query_arg( array( 'action' => 'ld_slider_delete', 'id' => $slider->id ), admin_url( 'admin-post.php' ) ), 'ld_slider_delete_' . $slider->id );
						$shortcode  = '[ld_slider id="' . esc_attr( $slider->id ) . '"]';
						$class_name = 'ld-slider--' . esc_attr( $slider->slug );
					?>
					<tr>
						<td>
							<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $slider->name ); ?></a></strong>
						</td>
						<td>
							<code class="ld-copyable" title="Click to copy"><?php echo esc_html( $shortcode ); ?></code>
						</td>
						<td>
							<code class="ld-copyable" title="Click to copy">.<?php echo esc_html( $class_name ); ?></code>
						</td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $slider->created_at ) ) ); ?></td>
						<td class="ld-actions">
							<a href="<?php echo esc_url( $edit_url ); ?>" class="ld-btn ld-btn--sm">Edit</a>
							<a href="<?php echo esc_url( $delete_url ); ?>" class="ld-btn ld-btn--sm ld-btn--danger" onclick="return confirm('Delete this slider? This cannot be undone.')">Delete</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	<?php endif; ?>

	<p class="ld-footer-credit">LD Sliders by <a href="https://www.loungedesign.co.uk" target="_blank" rel="noopener">Lounge Design</a></p>
</div>
