<?php

// If this file is called directly, abort.
if ( ! defined( "ABSPATH" ) ) {
  exit();
}

/**
 * Inline the <style> block CSS used by our transactional email templates
 * (see email/email_styles.php) into per-element style="" attributes.
 *
 * Some mail clients / sync services (e.g. Outlook when connected to a Gmail
 * account routes mail through Microsoft's own relay, not Gmail directly)
 * strip or ignore <head><style> blocks, so relying on them alone leaves the
 * email completely unstyled for some recipients. Inlining keeps the single
 * CSS source in email_styles.php as the source of truth for design changes,
 * while still guaranteeing the styling renders in clients that only honour
 * inline style="" attributes.
 *
 * @media rules are automatically preserved by Emogrifier in a <style> block
 * as progressive enhancement for clients that support both.
 *
 * @param string $html Full HTML email document produced by our templates.
 * @return string HTML with CSS inlined, or the original HTML unchanged if
 *                inlining isn't possible/fails.
 */
function hkota_inline_email_html( $html ) {

  if ( empty( $html ) || ! class_exists( '\Pelago\Emogrifier\CssInliner' ) ) {
    return $html;
  }

  try {
    return \Pelago\Emogrifier\CssInliner::fromHtml( $html )
      ->inlineCss()
      ->render();
  } catch ( \Throwable $e ) {
    // Fail safe: send the original (un-inlined) HTML rather than blocking
    // the email entirely if the markup can't be parsed for some reason.
    error_log( 'hkota_inline_email_html: failed to inline email CSS - ' . $e->getMessage() );
    return $html;
  }
}
