<?php
/**
 * Site header.
 *
 * ⚠️ THIS FILE OVERRIDES Divi/header.php AND MUST HONOUR ITS WRAPPER CONTRACT.
 *
 * Divi opens two wrappers here and closes them in footer.php:
 *   <div id="page-container">   … closed in footer.php
 *     <div id="et-main-area">   … closed in footer.php
 * and fires `et_before_main_content` / `et_after_main_content` around the content.
 * Divi's CSS and its Theme Builder body layouts target those IDs, so removing
 * them mid-migration would break the layout of every page still built in Divi.
 *
 * So this file replaces ONLY Divi's own `#top-header` / `#main-header` markup with
 * our own chrome, and keeps everything else byte-compatible. The wrappers come out
 * in phase 4, when no page renders Divi shortcodes any more.
 *
 * Also preserved from Divi's head: elegant_description/keywords/canonical (Divi's
 * meta output) and the `et_head_meta` action, both of which plugins hook into.
 *
 * Behaviour + a11y contract: vibe-frontend-standards/references/header-standard.md
 * Look: .claude/design.md §7 → "Header / navigation"
 *
 * @package kalynyuk
 */

defined( 'ABSPATH' ) || exit;

$ak_nav       = ak_nav_tree( 'primary-menu' );
$ak_langs     = ak_language_switcher();
$ak_cta       = ak_primary_cta();
$ak_home      = home_url( '/' );
$ak_logo      = ak_logo_html();
$ak_menu_open = ak_str( 'ak_menu_open', 'Меню' );
$ak_back      = ak_str( 'ak_back', 'Назад' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php
if ( function_exists( 'elegant_description' ) ) {
	elegant_description();
	elegant_keywords();
	elegant_canonical();
}

/** Divi's head hook — plugins rely on it. */
do_action( 'et_head_meta' );

wp_head();
?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="u-skip-link" href="#ak-content"><?php esc_html_e( 'Skip to content', 'kalynyuk' ); ?></a>

<div id="page-container">

	<?php
	/*
	 * Page dim for open desktop dropdowns. Sits BELOW the header in the stacking
	 * order, so the header and its open panel stay bright and interactive while
	 * everything behind them dims. Hidden entirely on mobile — the drawer is
	 * full-viewport there, so there is nothing left to dim.
	 *
	 * aria-hidden + no focusable content: it is decoration, and closing is already
	 * handled by Escape / outside-click / the trigger.
	 */
	?>
	<div class="page-scrim" aria-hidden="true" data-ak-page-scrim></div>

	<header class="site-header" data-ak-header>
		<div class="site-header__inner">

			<a class="site-header__logo" href="<?php echo esc_url( $ak_home ); ?>" rel="home">
				<?php
				// ak_logo_html() escapes its own output.
				echo $ak_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</a>

			<?php if ( $ak_nav ) : ?>
				<nav class="site-nav" aria-label="<?php echo esc_attr( $ak_menu_open ); ?>">
					<ul class="site-nav__list">
						<?php foreach ( $ak_nav as $i => $item ) : ?>
							<?php if ( ! empty( $item['children'] ) ) : ?>
								<?php $panel_id = 'ak-nav-panel-' . $item['id']; ?>
								<li class="site-nav__item site-nav__item--has-children">
									<?php
									/*
									 * A parent renders as a <button>, never <a href="#">.
									 * header-standard §1/§4: an item with children is a
									 * trigger, not a destination. Petr confirmed 2026-07-27
									 * that "Portugal" is deliberately non-clickable — it may
									 * become an archive later.
									 */
									?>
									<button
										class="site-nav__trigger<?php echo $item['current'] ? ' is-current' : ''; ?>"
										type="button"
										aria-expanded="false"
										aria-controls="<?php echo esc_attr( $panel_id ); ?>"
										data-ak-dropdown-trigger
									>
										<span><?php echo esc_html( $item['title'] ); ?></span>
										<svg class="site-nav__chevron" width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
											<path d="M4 6.5 8 10.5 12 6.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
										</svg>
									</button>

									<div class="site-nav__panel" id="<?php echo esc_attr( $panel_id ); ?>" hidden data-ak-dropdown-panel>
										<ul class="site-nav__sublist">
											<?php foreach ( $item['children'] as $child ) : ?>
												<li>
													<a
														class="site-nav__sublink"
														href="<?php echo esc_url( $child['url'] ); ?>"
														<?php echo ak_link_target_attrs( $child['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
													><?php echo esc_html( $child['title'] ); ?></a>
												</li>
											<?php endforeach; ?>
										</ul>
									</div>
								</li>
							<?php else : ?>
								<li class="site-nav__item">
									<a
										class="site-nav__link<?php echo $item['current'] ? ' is-current' : ''; ?>"
										href="<?php echo esc_url( $item['url'] ); ?>"
										<?php echo $item['current'] ? ' aria-current="page"' : ''; ?>
										<?php echo ak_link_target_attrs( $item['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									><?php echo esc_html( $item['title'] ); ?></a>
								</li>
							<?php endif; ?>
							<?php unset( $i ); ?>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<div class="site-header__actions">

				<?php if ( count( $ak_langs ) > 1 ) : ?>
					<?php get_template_part( 'template-parts/lang-switch' ); ?>
				<?php endif; ?>

				<?php if ( $ak_cta ) : ?>
					<a
						class="btn btn--primary site-header__cta"
						href="<?php echo esc_url( $ak_cta['url'] ); ?>"
						<?php echo ak_link_target_attrs( $ak_cta['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					><?php echo esc_html( $ak_cta['label'] ); ?></a>
				<?php endif; ?>

				<button
					class="site-header__toggle"
					type="button"
					aria-expanded="false"
					aria-controls="ak-drawer"
					data-ak-drawer-toggle
				>
					<span class="u-visually-hidden"><?php echo esc_html( $ak_menu_open ); ?></span>
					<svg class="site-header__burger" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M3 6h18M3 12h18M3 18h18" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
					</svg>
					<svg class="site-header__cross" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
						<path d="M5 5l14 14M19 5L5 19" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" />
					</svg>
				</button>
			</div>
		</div>
	</header>

	<?php get_template_part( 'template-parts/nav-drawer', null, array(
		'nav'   => $ak_nav,
		'cta'   => $ak_cta,
		'back'  => $ak_back,
	) ); ?>

	<div id="et-main-area">
		<div id="ak-content" tabindex="-1"></div>
<?php
/** Divi's pre-content hook — Theme Builder body layouts and plugins use it. */
do_action( 'et_before_main_content' );
