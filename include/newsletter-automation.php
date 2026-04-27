<?php
/**
 * Newsletter Membership Automation
 *
 * Adds a "Membership Automation" submenu under the Newsletter menu
 * and handles AJAX-based batch syncing of newsletter subscription lists
 * with WooCommerce Memberships data.
 */

defined('ABSPATH') || exit;

// ─── Admin submenu ───────────────────────────────────────────────────
add_action('admin_menu', 'hkota_newsletter_automation_menu', 99); // priority 99 so Newsletter menus exist first
function hkota_newsletter_automation_menu() {
    add_submenu_page(
        'newsletter_main_index',
        'Membership Automation',
        'Membership Automation',
        'manage_options',
        'hkota_membership_automation',
        'hkota_membership_automation_page'
    );
}

// ─── Admin page renderer ────────────────────────────────────────────
function hkota_membership_automation_page() {
    ?>
    <div class="wrap">

        <h1>Membership Automation</h1>
        <p>Sync newsletter subscription lists with WooCommerce Memberships data.</p>

        <!-- List 2: Active Members -->
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; margin-bottom:20px; max-width:700px;">
            <h3 style="margin-top:0;">List 2 — Active Members</h3>
            <p>Set <code>list_2 = 1</code> for all subscribers with an active membership (<code>wcm-active</code>), and <code>list_2 = 0</code> for everyone else.</p>
            <button type="button" id="btn-sync-list2" class="button button-primary">Sync List 2 — Active Members</button>
            <div id="progress-list2" style="display:none; margin-top:15px;">
                <div style="background:#e0e0e0; border-radius:4px; overflow:hidden; height:24px; position:relative;">
                    <div id="bar-list2" style="background:#0073aa; height:100%; width:0%; transition:width 0.3s; border-radius:4px;"></div>
                    <span id="bar-text-list2" style="position:absolute; top:3px; left:10px; font-size:12px; color:#fff; font-weight:bold;"></span>
                </div>
                <p id="status-list2" style="margin-top:8px; font-style:italic;"></p>
            </div>
        </div>

        <!-- List 3: Expiring Members -->
        <div style="background:#fff; border:1px solid #ccd0d4; padding:20px; margin-bottom:20px; max-width:700px;">
            <h3 style="margin-top:0;">List 3 — Expiring Members (<?php echo esc_html(date('Y')); ?>)</h3>
            <p>Set <code>list_3 = 1</code> for active members whose membership ends around <strong>30 Apr – 1 May <?php echo esc_html(date('Y')); ?></strong> (date-range query to handle timezone edge cases), and <code>list_3 = 0</code> for everyone else.</p>
            <button type="button" id="btn-sync-list3" class="button button-primary">Sync List 3 — Expiring Members</button>
            <div id="progress-list3" style="display:none; margin-top:15px;">
                <div style="background:#e0e0e0; border-radius:4px; overflow:hidden; height:24px; position:relative;">
                    <div id="bar-list3" style="background:#0073aa; height:100%; width:0%; transition:width 0.3s; border-radius:4px;"></div>
                    <span id="bar-text-list3" style="position:absolute; top:3px; left:10px; font-size:12px; color:#fff; font-weight:bold;"></span>
                </div>
                <p id="status-list3" style="margin-top:8px; font-style:italic;"></p>
            </div>
        </div>

    </div>

    <script>
    (function($) {
        var nonce = <?php echo wp_json_encode(wp_create_nonce('hkota_newsletter_sync')); ?>;
        var running = false;

        function startSync(listNum) {
            if (running) return;
            running = true;

            var $btn      = $('#btn-sync-list' + listNum);
            var $progress = $('#progress-list' + listNum);
            var $bar      = $('#bar-list' + listNum);
            var $barText  = $('#bar-text-list' + listNum);
            var $status   = $('#status-list' + listNum);

            $btn.prop('disabled', true).text('Syncing...');
            $progress.show();
            $bar.css('width', '0%');
            $barText.text('');
            $status.text('Initializing...');

            doStep(listNum, 'init', 0, 0, $btn, $bar, $barText, $status);
        }

        function doStep(listNum, step, offset, total, $btn, $bar, $barText, $status) {
            $.post(ajaxurl, {
                action: 'hkota_sync_newsletter_list' + listNum,
                nonce: nonce,
                step: step,
                offset: offset
            }, function(response) {
                if (!response.success) {
                    $status.text('Error: ' + (response.data || 'Unknown error'));
                    resetBtn($btn, listNum);
                    running = false;
                    return;
                }

                var data = response.data;

                switch (data.step) {
                    case 'init':
                        total = data.total;
                        if (total === 0) {
                            $bar.css('width', '100%');
                            $barText.text('No members found');
                            $status.text('No matching members found. Running cleanup...');
                            doStep(listNum, 'cleanup', 0, 0, $btn, $bar, $barText, $status);
                            return;
                        }
                        $status.text('Found ' + total + ' members. Processing...');
                        doStep(listNum, 'process', 0, total, $btn, $bar, $barText, $status);
                        break;

                    case 'process':
                        var processed = data.processed;
                        var pct = total > 0 ? Math.round((processed / total) * 90) : 90;
                        $bar.css('width', pct + '%');
                        $barText.text(processed + ' / ' + total);
                        $status.text('Processed ' + processed + ' of ' + total + ' members...');

                        if (data.batch > 0 && processed < total) {
                            doStep(listNum, 'process', processed, total, $btn, $bar, $barText, $status);
                        } else {
                            $status.text('Cleaning up non-matching subscribers...');
                            doStep(listNum, 'cleanup', 0, total, $btn, $bar, $barText, $status);
                        }
                        break;

                    case 'cleanup':
                        $bar.css('width', '100%');
                        var countKey = listNum === 2 ? 'active_count' : 'expiring_count';
                        var finalCount = data[countKey] || 0;
                        $barText.text('Done');
                        $status.text('Sync complete! ' + finalCount + ' subscribers now have list_' + listNum + ' = 1.');
                        resetBtn($btn, listNum);
                        running = false;
                        break;
                }
            }).fail(function(xhr) {
                $status.text('AJAX request failed: ' + xhr.statusText);
                resetBtn($btn, listNum);
                running = false;
            });
        }

        function resetBtn($btn, listNum) {
            $btn.prop('disabled', false).text(listNum === 2 ? 'Sync List 2 — Active Members' : 'Sync List 3 — Expiring Members');
        }

        $('#btn-sync-list2').on('click', function() { startSync(2); });
        $('#btn-sync-list3').on('click', function() { startSync(3); });

    })(jQuery);
    </script>
    <?php
}

// ─── AJAX: Sync List 2 (Active Members) ─────────────────────────────
add_action('wp_ajax_hkota_sync_newsletter_list2', 'hkota_sync_newsletter_list2');
function hkota_sync_newsletter_list2() {
    if (!current_user_can('administrator')) {
        wp_send_json_error('Unauthorized');
    }
    check_ajax_referer('hkota_newsletter_sync', 'nonce');

    global $wpdb;
    $newsletter_table = $wpdb->prefix . 'newsletter';
    $step = sanitize_text_field(wp_unslash($_POST['step'] ?? ''));
    $batch_size = 100;

    switch ($step) {
        case 'init':
            $total = (int) $wpdb->get_var(
                "SELECT COUNT(DISTINCT post_author)
                 FROM {$wpdb->posts}
                 WHERE post_type = 'wc_user_membership'
                   AND post_status = 'wcm-active'"
            );
            wp_send_json_success(['total' => $total, 'step' => 'init']);
            break;

        case 'process':
            $offset = absint($_POST['offset'] ?? 0);

            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT post_author
                 FROM {$wpdb->posts}
                 WHERE post_type = 'wc_user_membership'
                   AND post_status = 'wcm-active'
                 ORDER BY post_author ASC
                 LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            ));

            $processed = 0;
            foreach ($user_ids as $user_id) {
                $subscriber = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$newsletter_table} WHERE wp_user_id = %d",
                    $user_id
                ));

                if ($subscriber) {
                    $wpdb->update($newsletter_table, ['list_2' => 1], ['id' => $subscriber->id]);
                } else {
                    $user = get_userdata($user_id);
                    if ($user) {
                        $wpdb->insert($newsletter_table, [
                            'email'      => $user->user_email,
                            'name'       => get_user_meta($user_id, 'member_first_name_eng', true) ?: $user->first_name,
                            'surname'    => get_user_meta($user_id, 'member_last_name_eng', true) ?: $user->last_name,
                            'status'     => 'C',
                            'wp_user_id' => $user_id,
                            'list_1'     => 1,
                            'list_2'     => 1,
                            'referrer'   => 'wordpress',
                            'created'    => current_time('mysql'),
                            'token'      => wp_generate_password(12, false),
                        ]);
                    }
                }
                $processed++;
            }

            wp_send_json_success([
                'processed' => $offset + $processed,
                'batch'     => $processed,
                'step'      => 'process',
            ]);
            break;

        case 'cleanup':
            $wpdb->query(
                "UPDATE {$newsletter_table} n
                 SET n.list_2 = 0
                 WHERE n.list_2 = 1
                   AND n.wp_user_id NOT IN (
                       SELECT DISTINCT p.post_author
                       FROM {$wpdb->posts} p
                       WHERE p.post_type = 'wc_user_membership'
                         AND p.post_status = 'wcm-active'
                   )"
            );

            $active_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$newsletter_table} WHERE list_2 = 1");
            wp_send_json_success([
                'step'         => 'cleanup',
                'active_count' => $active_count,
            ]);
            break;

        default:
            wp_send_json_error('Invalid step');
    }
}

// ─── AJAX: Sync List 3 (Expiring Members) ───────────────────────────
add_action('wp_ajax_hkota_sync_newsletter_list3', 'hkota_sync_newsletter_list3');
function hkota_sync_newsletter_list3() {
    if (!current_user_can('administrator')) {
        wp_send_json_error('Unauthorized');
    }
    check_ajax_referer('hkota_newsletter_sync', 'nonce');

    global $wpdb;
    $newsletter_table = $wpdb->prefix . 'newsletter';
    $step = sanitize_text_field(wp_unslash($_POST['step'] ?? ''));
    $batch_size = 100;

    // Date range covers timezone bug: April 30 00:00 to May 1 23:59
    $year = date('Y');
    $end_date_start = $year . '-04-30 00:00:00';
    $end_date_end   = $year . '-05-01 23:59:59';

    switch ($step) {
        case 'init':
            $total = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.post_author)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'wc_user_membership'
                   AND p.post_status = 'wcm-active'
                   AND pm.meta_key = '_end_date'
                   AND pm.meta_value >= %s
                   AND pm.meta_value <= %s",
                $end_date_start,
                $end_date_end
            ));
            wp_send_json_success(['total' => $total, 'step' => 'init', 'year' => $year]);
            break;

        case 'process':
            $offset = absint($_POST['offset'] ?? 0);

            $user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT p.post_author
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'wc_user_membership'
                   AND p.post_status = 'wcm-active'
                   AND pm.meta_key = '_end_date'
                   AND pm.meta_value >= %s
                   AND pm.meta_value <= %s
                 ORDER BY p.post_author ASC
                 LIMIT %d OFFSET %d",
                $end_date_start,
                $end_date_end,
                $batch_size,
                $offset
            ));

            $processed = 0;
            foreach ($user_ids as $user_id) {
                $subscriber = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$newsletter_table} WHERE wp_user_id = %d",
                    $user_id
                ));

                if ($subscriber) {
                    $wpdb->update($newsletter_table, ['list_3' => 1], ['id' => $subscriber->id]);
                } else {
                    $user = get_userdata($user_id);
                    if ($user) {
                        $wpdb->insert($newsletter_table, [
                            'email'      => $user->user_email,
                            'name'       => get_user_meta($user_id, 'member_first_name_eng', true) ?: $user->first_name,
                            'surname'    => get_user_meta($user_id, 'member_last_name_eng', true) ?: $user->last_name,
                            'status'     => 'C',
                            'wp_user_id' => $user_id,
                            'list_1'     => 1,
                            'list_3'     => 1,
                            'referrer'   => 'wordpress',
                            'created'    => current_time('mysql'),
                            'token'      => wp_generate_password(12, false),
                        ]);
                    }
                }
                $processed++;
            }

            wp_send_json_success([
                'processed' => $offset + $processed,
                'batch'     => $processed,
                'step'      => 'process',
            ]);
            break;

        case 'cleanup':
            $wpdb->query($wpdb->prepare(
                "UPDATE {$newsletter_table} n
                 SET n.list_3 = 0
                 WHERE n.list_3 = 1
                   AND n.wp_user_id NOT IN (
                       SELECT DISTINCT p.post_author
                       FROM {$wpdb->posts} p
                       INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                       WHERE p.post_type = 'wc_user_membership'
                         AND p.post_status = 'wcm-active'
                         AND pm.meta_key = '_end_date'
                         AND pm.meta_value >= %s
                         AND pm.meta_value <= %s
                   )",
                $end_date_start,
                $end_date_end
            ));

            $expiring_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$newsletter_table} WHERE list_3 = 1");
            wp_send_json_success([
                'step'           => 'cleanup',
                'expiring_count' => $expiring_count,
            ]);
            break;

        default:
            wp_send_json_error('Invalid step');
    }
}
