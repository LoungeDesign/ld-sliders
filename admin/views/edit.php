<?php if ( ! defined( 'ABSPATH' ) ) exit;

$is_edit    = ! empty( $slider );
$page_title = $is_edit ? esc_html__( 'Edit Slider', 'ld-sliders' ) : esc_html__( 'Add New Slider', 'ld-sliders' );
$shortcode  = $is_edit ? '[ld_slider id="' . absint( $slider->id ) . '"]' : '';
$class_name = $is_edit ? 'ld-slider--' . esc_attr( $slider->slug ) : '';

function ld_field( $settings, $key, $default = '' ) {
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}
function ld_checked( $settings, $key ) {
	return ! empty( $settings[ $key ] ) ? 'checked' : '';
}
function ld_selected( $settings, $key, $value ) {
	return ( isset( $settings[ $key ] ) && $settings[ $key ] === $value ) ? 'selected' : '';
}
?>
<div class="wrap ld-sliders-wrap ld-sliders-edit-wrap">

	<div class="ld-sliders-header">
		<div class="ld-sliders-header__inner">
			<div class="ld-sliders-header__brand">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ld-sliders' ) ); ?>" class="ld-back-link">
					<span class="dashicons dashicons-arrow-left-alt"></span>
				</a>
				<span class="dashicons dashicons-slides"></span>
				<h1><?php echo esc_html( $page_title ); ?></h1>
			</div>
			<?php if ( $is_edit ) : ?>
			<div class="ld-header-shortcode">
				<label>Container class</label>
				<code class="ld-copyable">ld-slider-<?php echo absint($slider->id); ?></code>
				<label>Cell class</label>
				<code class="ld-copyable">ld-carousel-cell</code>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( isset( $_GET['saved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Slider saved.</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="ld_slider_save">
		<input type="hidden" name="slider_id" value="<?php echo $is_edit ? absint( $slider->id ) : 0; ?>">
		<?php wp_nonce_field( 'ld_slider_save', '_ld_nonce' ); ?>

		<div class="ld-edit-layout">
			<div class="ld-edit-main">

				<!-- Name -->
				<div class="ld-card">
					<div class="ld-card__body">
						<label class="ld-label--large" for="slider_name">Slider Name</label>
						<input type="text" id="slider_name" name="slider_name" class="large-text"
							value="<?php echo $is_edit ? esc_attr( $slider->name ) : ''; ?>"
							placeholder="e.g. Homepage Hero" required>
					</div>
				</div>

				<!-- ── Behaviour ──────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-controls-play"></span> Behaviour</h2></div>
					<div class="ld-card__body ld-toggles">

						<?php $toggles = array(
							'wrapAround'           => array( 'Wrap Around',        'Loop infinitely from last to first slide' ),
							'freeScroll'           => array( 'Free Scroll',        'Drag freely with momentum rather than snapping' ),
							'adaptiveHeight'       => array( 'Adaptive Height',    'Resize wrapper to match the tallest visible cell' ),
							'rightToLeft'          => array( 'Right to Left',      'Reverse slide direction for RTL languages' ),
							'resize'               => array( 'Resize on Window Change', 'Re-position cells when the browser is resized' ),
							'watchCSS'             => array( 'Watch CSS',          'Enable/disable slider via CSS :after content rule' ),
						);
						foreach ( $toggles as $key => $labels ) : ?>
						<label class="ld-toggle">
							<input type="checkbox" name="settings[<?php echo esc_attr($key); ?>]" value="1" <?php echo ld_checked( $settings, $key ); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label"><?php echo esc_html($labels[0]); ?><small><?php echo esc_html($labels[1]); ?></small></span>
						</label>
						<?php endforeach; ?>

						<label class="ld-toggle">
							<input type="checkbox" name="settings[autoPlay]" value="1" id="ld_autoplay" <?php echo ld_checked( $settings, 'autoPlay' ); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label">Auto Play<small>Automatically advance slides</small></span>
						</label>
						<div class="ld-sub-field" id="autoplay-speed-wrap">
							<label>Auto Play Speed (ms)</label>
							<input type="number" name="settings[autoPlaySpeed]" value="<?php echo absint( ld_field($settings,'autoPlaySpeed',3000) ); ?>" min="500" step="100">
						</div>
						<label class="ld-toggle">
							<input type="checkbox" name="settings[pauseAutoPlayOnHover]" value="1" <?php echo ld_checked($settings,'pauseAutoPlayOnHover'); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label">Pause on Hover<small>Pause auto play when mouse is over slider</small></span>
						</label>

					</div>
				</div>

				<!-- ── Navigation ─────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-arrow-right-alt2"></span> Navigation</h2></div>
					<div class="ld-card__body ld-toggles">
						<label class="ld-toggle">
							<input type="checkbox" name="settings[prevNextButtons]" value="1" <?php echo ld_checked($settings,'prevNextButtons'); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label">Prev / Next Buttons<small>Show arrow navigation buttons</small></span>
						</label>
						<label class="ld-toggle">
							<input type="checkbox" name="settings[pageDots]" value="1" <?php echo ld_checked($settings,'pageDots'); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label">Page Dots<small>Show dot indicators below the slider</small></span>
						</label>
						<div class="ld-field">
							<label>Arrow Shape</label>
							<select name="settings[arrowShape]" id="ld_arrowshape">
								<option value="default" <?php echo ld_selected($settings,'arrowShape','default'); ?>>Default</option>
								<option value="custom"  <?php echo ld_selected($settings,'arrowShape','custom'); ?>>Custom SVG Path</option>
							</select>
						</div>
						<div class="ld-sub-field" id="arrowshape-custom-wrap">
							<label>Custom SVG Path</label>
							<input type="text" name="settings[arrowShapeCustom]" value="<?php echo esc_attr(ld_field($settings,'arrowShapeCustom','')); ?>" placeholder="M 10,50 L 60,100 L 60,0 Z">
						</div>
						<div class="ld-field" style="margin-top:8px">
							<label>Sync with Slider — asNavFor</label>
							<input type="text" name="settings[asNavFor]" value="<?php echo esc_attr(ld_field($settings,'asNavFor','')); ?>" placeholder="#ld-slider-2">
							<small class="ld-hint">CSS selector of another slider this one controls as a thumbnail nav</small>
						</div>
					</div>
				</div>

				<!-- ── Layout ─────────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-editor-table"></span> Layout</h2></div>
					<div class="ld-card__body">
						<div class="ld-field-row">
							<div class="ld-field">
								<label>Cell Alignment</label>
								<select name="settings[cellAlign]">
									<option value="left"   <?php echo ld_selected($settings,'cellAlign','left'); ?>>Left</option>
									<option value="center" <?php echo ld_selected($settings,'cellAlign','center'); ?>>Centre</option>
									<option value="right"  <?php echo ld_selected($settings,'cellAlign','right'); ?>>Right</option>
								</select>
							</div>
							<div class="ld-field">
								<label>Initial Slide Index</label>
								<input type="number" name="settings[initialIndex]" value="<?php echo absint(ld_field($settings,'initialIndex',0)); ?>" min="0">
							</div>
						</div>
						<div class="ld-toggles">
							<?php $layout_toggles = array(
								'contain'         => array('Contain',          'Prevent scrolling past the last cell'),
								'overflowVisible' => array('Overflow Visible', 'Cells peek out from container edges — for off-screen scrolling effects'),
								'percentPosition' => array('Percent Position', 'Position cells in percentages rather than pixels'),
							); foreach ($layout_toggles as $k => $l) : ?>
							<label class="ld-toggle">
								<input type="checkbox" name="settings[<?php echo esc_attr($k); ?>]" value="1" <?php echo ld_checked($settings,$k); ?>>
								<span class="ld-toggle__slider"></span>
								<span class="ld-toggle__label"><?php echo esc_html($l[0]); ?><small><?php echo esc_html($l[1]); ?></small></span>
							</label>
							<?php endforeach; ?>
							<label class="ld-toggle">
								<input type="checkbox" name="settings[groupCells]" value="1" id="ld_groupcells" <?php echo ld_checked($settings,'groupCells'); ?>>
								<span class="ld-toggle__slider"></span>
								<span class="ld-toggle__label">Group Cells<small>Advance multiple cells per slide</small></span>
							</label>
							<div class="ld-sub-field" id="groupcells-count-wrap">
								<label>Cells per group</label>
								<input type="number" name="settings[groupCellsCount]" value="<?php echo absint(ld_field($settings,'groupCellsCount',1)); ?>" min="1" max="12">
							</div>
						</div>
					</div>
				</div>

				<!-- ── Cell Sizing ────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-desktop"></span> Cell Sizing — Desktop</h2></div>
					<div class="ld-card__body">
						<p class="ld-hint">Leave blank to let cells size naturally from their content.</p>
						<div class="ld-field-row">
							<?php foreach ( array(
								array('cellWidth','cellWidthUnit','Cell Width', array('px','%','vw','em','rem')),
								array('cellHeight','cellHeightUnit','Cell Height', array('px','%','vh','em','rem')),
								array('cellGap','cellGapUnit','Gap Between Cells', array('px','%','em','rem')),
							) as $f ) : ?>
							<div class="ld-field ld-field--with-unit">
								<label><?php echo esc_html($f[2]); ?></label>
								<div class="ld-input-unit">
									<input type="number" name="settings[<?php echo esc_attr($f[0]); ?>]" value="<?php echo esc_attr(ld_field($settings,$f[0],''));?>" min="0">
									<select name="settings[<?php echo esc_attr($f[1]); ?>]">
										<?php foreach ($f[3] as $u) : ?>
										<option value="<?php echo esc_attr($u); ?>" <?php echo ld_selected($settings,$f[1],$u); ?>><?php echo esc_html($u); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- ── Responsive ─────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-tablet"></span> Responsive Overrides</h2></div>
					<div class="ld-card__body">
						<p class="ld-hint">Leave blank to inherit the desktop values.</p>
						<?php foreach ( array(
							'tablet' => array('Tablet (≤ 1024px)','dashicons-tablet'),
							'mobile' => array('Mobile (≤ 767px)','dashicons-smartphone'),
						) as $bp => $info ) : ?>
						<div class="ld-breakpoint">
							<h3 class="ld-breakpoint__label"><span class="dashicons <?php echo esc_attr($info[1]); ?>"></span> <?php echo esc_html($info[0]); ?></h3>
							<div class="ld-field-row">
								<?php foreach ( array('Width','Height','Gap') as $dim ) :
									$key = $bp . '_cell' . $dim; ?>
								<div class="ld-field">
									<label><?php echo esc_html($dim); ?> (px)</label>
									<input type="number" name="settings[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr(ld_field($settings,$key,'')); ?>" placeholder="inherit" min="0">
								</div>
								<?php endforeach; ?>
								<div class="ld-field">
									<label>Cells per group</label>
									<input type="number" name="settings[<?php echo esc_attr($bp); ?>_groupCellsCount]" value="<?php echo absint(ld_field($settings,$bp.'_groupCellsCount',1)); ?>" min="1" max="12">
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- ── Overlays ───────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-image-filter"></span> Gradient Overlays</h2></div>
					<div class="ld-card__body">
						<p class="ld-hint">Fade cells into the page edge. Set the colour to match your section/page background. Offset automatically so the active cell is never covered.</p>
						<?php foreach ( array('Left','Right') as $side ) :
							$k = 'overlay' . $side; ?>
						<div class="ld-overlay-panel">
							<label class="ld-toggle">
								<input type="checkbox" name="settings[<?php echo esc_attr($k); ?>]" value="1" class="ld-overlay-trigger" id="overlay<?php echo esc_attr($side); ?>_toggle" <?php echo ld_checked($settings,$k); ?>>
								<span class="ld-toggle__slider"></span>
								<span class="ld-toggle__label"><?php echo esc_html($side); ?> Overlay<small>Gradient fade on the <?php echo strtolower($side); ?> edge</small></span>
							</label>
							<div class="ld-overlay-fields" id="overlay<?php echo esc_attr($side); ?>_fields">
								<div class="ld-field-row" style="margin-top:12px">
									<div class="ld-field">
										<label>Colour</label>
										<input type="color" name="settings[<?php echo esc_attr($k.'Color'); ?>]" value="<?php echo esc_attr(ld_field($settings,$k.'Color','#ffffff')); ?>">
									</div>
									<div class="ld-field">
										<label>Opacity (%)</label>
										<input type="number" name="settings[<?php echo esc_attr($k.'Opacity'); ?>]" value="<?php echo absint(ld_field($settings,$k.'Opacity',100)); ?>" min="0" max="100">
									</div>
									<div class="ld-field">
										<label>Width (px)</label>
										<input type="number" name="settings[<?php echo esc_attr($k.'Width'); ?>]" value="<?php echo absint(ld_field($settings,$k.'Width',120)); ?>" min="20" max="600">
									</div>
								</div>
							</div>
						</div>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- ── Images ─────────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-format-image"></span> Images</h2></div>
					<div class="ld-card__body ld-toggles">
						<label class="ld-toggle">
							<input type="checkbox" name="settings[imagesLoaded]" value="1" <?php echo ld_checked($settings,'imagesLoaded'); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label">Wait for Images to Load<small>Re-positions cells after all images have loaded — prevents layout jumps</small></span>
						</label>
						<label class="ld-toggle">
							<input type="checkbox" name="settings[lazyLoad]" value="1" id="ld_lazyload" <?php echo ld_checked($settings,'lazyLoad'); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label">Lazy Load Images<small>Load images only as they approach the viewport — use data-src on img tags</small></span>
						</label>
						<div class="ld-sub-field" id="lazyload-count-wrap">
							<label>Cells Ahead to Preload</label>
							<input type="number" name="settings[lazyLoadCount]" value="<?php echo absint(ld_field($settings,'lazyLoadCount',1)); ?>" min="1" max="5">
						</div>
					</div>
				</div>

				<!-- ── Advanced ───────────────────────────────── -->
				<div class="ld-card">
					<div class="ld-card__header"><h2 class="ld-card__title"><span class="dashicons dashicons-admin-settings"></span> Advanced</h2></div>
					<div class="ld-card__body">
						<div class="ld-field-row">
							<div class="ld-field">
								<label>Attraction (0–1)</label>
								<input type="number" name="settings[selectedAttraction]" value="<?php echo esc_attr(ld_field($settings,'selectedAttraction',0.025)); ?>" step="0.005" min="0" max="1">
								<small class="ld-hint">How strongly slides snap to position</small>
							</div>
							<div class="ld-field">
								<label>Friction (0–1)</label>
								<input type="number" name="settings[friction]" value="<?php echo esc_attr(ld_field($settings,'friction',0.28)); ?>" step="0.01" min="0" max="1">
								<small class="ld-hint">Higher = snappier; lower = more momentum</small>
							</div>
							<div class="ld-field">
								<label>Drag Threshold (px)</label>
								<input type="number" name="settings[dragThreshold]" value="<?php echo absint(ld_field($settings,'dragThreshold',3)); ?>" min="1" max="50">
							</div>
						</div>
						<label class="ld-toggle">
							<input type="checkbox" name="settings[accessibility]" value="1" <?php echo ld_checked($settings,'accessibility'); ?>>
							<span class="ld-toggle__slider"></span>
							<span class="ld-toggle__label">Accessibility<small>Enable keyboard navigation and ARIA announcements</small></span>
						</label>
						<div class="ld-field" style="margin-top:16px">
							<label>Custom CSS</label>
							<textarea name="settings[customCSS]" rows="6" placeholder="#ld-slider-1 .ld-carousel-cell { border-radius: 8px; }"><?php echo esc_textarea(ld_field($settings,'customCSS','')); ?></textarea>
							<small class="ld-hint">Scoped to this slider only. No &lt;style&gt; tags needed.</small>
						</div>
					</div>
				</div>

			</div><!-- /.ld-edit-main -->

			<!-- ── Sidebar ────────────────────────────────────── -->
			<div class="ld-edit-sidebar">
				<div class="ld-card ld-card--sticky">
					<div class="ld-card__body">
						<button type="submit" class="ld-btn ld-btn--primary ld-btn--full">
							<span class="dashicons dashicons-saved"></span>
							<?php echo $is_edit ? 'Update Slider' : 'Create Slider'; ?>
						</button>
						<?php if ( $is_edit ) : ?>
							<hr>
							<a href="<?php echo esc_url(admin_url('admin.php?page=ld-sliders')); ?>" class="ld-btn ld-btn--ghost ld-btn--full">← Back to all sliders</a>
							<hr>
							<div class="ld-sidebar-info">
								<p><strong>Slider ID:</strong> <?php echo absint($slider->id); ?></p>
								<p><strong>Slug:</strong> <?php echo esc_html($slider->slug); ?></p>
								
								<p><strong>CSS Class:</strong><br><code class="ld-copyable">.<?php echo esc_html($class_name); ?></code></p>
							</div>
							<hr>
							<a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action'=>'ld_slider_delete','id'=>$slider->id),admin_url('admin-post.php')),'ld_slider_delete_'.$slider->id)); ?>"
							   class="ld-btn ld-btn--danger ld-btn--full"
							   onclick="return confirm('Delete this slider? This cannot be undone.')">
								<span class="dashicons dashicons-trash"></span> Delete Slider
							</a>
						<?php endif; ?>
					</div>
				</div>
				<div class="ld-card ld-card--usage">
					<div class="ld-card__header"><h3 class="ld-card__title">How to use</h3></div>
					<div class="ld-card__body">
						<p>1. Copy the <strong>container class</strong> from the header above.</p>
						<p>2. In your page builder (Breakdance etc.), add a Div and paste that class onto it.</p>
						<p>3. Add child Divs inside it, each with class <code>ld-carousel-cell</code>.</p>
						<p>4. Drop any content you like inside each cell — images, text, buttons, anything.</p>
						<p>5. For lazy loading, use <code>data-src</code> instead of <code>src</code> on images.</p>
					</div>
				</div>
			</div>

		</div>
	</form>

	<p class="ld-footer-credit">LD Sliders by <a href="https://www.loungedesign.co.uk" target="_blank" rel="noopener">Lounge Design</a></p>
</div>
