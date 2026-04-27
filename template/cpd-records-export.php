<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$full_name       = isset( $args['full_name'] ) ? $args['full_name'] : '';
$ot_reg_number   = isset( $args['ot_reg_number'] ) ? $args['ot_reg_number'] : '';
$member_number   = isset( $args['member_number'] ) ? $args['member_number'] : '';
$period_from     = isset( $args['period_from'] ) ? $args['period_from'] : '';
$period_to       = isset( $args['period_to'] ) ? $args['period_to'] : '';
$records         = isset( $args['records'] ) && is_array( $args['records'] ) ? $args['records'] : array();

// Embed logo as base64 so dompdf renders it reliably.
$logo_base64 = '';
$logo_path   = HKOTA_PLUGIN_DIR . '/asset/logo-wz-text.png';
if ( file_exists( $logo_path ) ) {
    $logo_data   = file_get_contents( $logo_path );
    $logo_info   = getimagesize( $logo_path );
    $logo_mime   = $logo_info['mime'];
    $logo_base64 = 'data:' . $logo_mime . ';base64,' . base64_encode( $logo_data );
}

?>
<html>
<head>
<meta charset="UTF-8">
<style>

    @page {
        size: A4 portrait;
        margin: 1.6cm 1.2cm 1.8cm 1.2cm;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 11px;
        color: #222;
        margin: 0;
        padding: 0;
    }

    /* Fixed-positioned border drawn on every page. dompdf paints fixed
       elements on every page, which avoids the broken top padding seen when
       the table <thead> repeats on subsequent pages. The bottom is kept
       above the page-number footer. */
    .page-border {
        position: fixed;
        top: -0.8cm;
        left: -0.4cm;
        right: -0.4cm;
        bottom: -1.2cm;
        border: 3px solid #008080;
        z-index: -2;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
    }

    .header-table td {
        vertical-align: middle;
        padding: 0;
    }

    .header-table td.logo {
        width: 50%;
        text-align: left;
    }

    .header-table td.logo img {
        max-width: 70%;
        height: auto;
    }

    .header-table td.confidential {
        width: 50%;
        text-align: right;
        font-size: 12px;
        font-weight: bold;
        text-decoration: underline;
        color: #008080;
    }

    .header-table img {
        height: 55px;
    }

    .section-title {
        font-size: 13px;
        font-weight: bold;
        text-decoration: underline;
        color: #008080;
        margin: 14px 0 8px 0;
    }

    table.particulars {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }

    table.particulars td {
        padding: 5px 8px;
        vertical-align: top;
    }

    table.particulars td.label {
        width: 35%;
        font-weight: bold;
        color: #555;
    }

    table.records {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 8px;
    }

    table.records th,
    table.records td {
        padding: 9px 10px;
        text-align: left;
        vertical-align: top;
    }

    table.records th {
        background-color: #008080;
        color: #ffffff;
        font-weight: bold;
        font-size: 11px;
        letter-spacing: 0.2px;
    }

    table.records tbody td {
        background-color: #ffffff;
    }

    table.records tr.record-row td {
        border-bottom: 1px solid #00808030;
    }

    table.records td.no,
    table.records td.date,
    table.records td.code,
    table.records td.point {
        white-space: nowrap;
    }

    table.records td.no {
        text-align: center;
        font-weight: bold;
        color: #008080;
    }

    table.records td.code {
        color: #1f3d46;
    }

    table.records td.point {
        text-align: right;
        font-weight: bold;
        color: #0c4f4f;
    }

    .empty {
        text-align: center;
        padding: 16px;
        color: #888;
    }
</style>
</head>
<body>

<div class="page-border"></div>

    <table class="header-table">
        <tr>
            <td class="logo">
                <?php if ( $logo_base64 ) : ?>
                    <img src="<?php echo $logo_base64; ?>" alt="HKOTA">
                <?php endif; ?>
            </td>
            <td class="confidential">CONFIDENTIAL</td>
        </tr>
    </table>

    <div class="section-title">Personal Particulars</div>
    <table class="particulars">
        <tr>
            <td class="label">Full Name</td>
            <td><?php echo esc_html( $full_name ); ?></td>
        </tr>
        <tr>
            <td class="label">OT Registration Number</td>
            <td><?php echo esc_html( $ot_reg_number ); ?></td>
        </tr>
        <tr>
            <td class="label">HKOTA Membership Number</td>
            <td><?php echo esc_html( $member_number ); ?></td>
        </tr>
        <tr>
            <td class="label">Period</td>
            <td>
                <?php
                if ( $period_from && $period_to ) {
                    echo esc_html( $period_from ) . ' to ' . esc_html( $period_to );
                }
                ?>
            </td>
        </tr>
    </table>

    <div class="section-title">CPD Records</div>
    <table class="records">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 14%;">Date</th>
                <th>Course</th>
                <th style="width: 18%;">Course Code</th>
                <th style="width: 18%;">Organization</th>
                <th style="width: 13%;">CPD Point</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $records ) ) : ?>
                <tr>
                    <td class="empty" colspan="6">No CPD records found in the selected period.</td>
                </tr>
            <?php else : ?>
                <?php $i = 1; foreach ( $records as $record ) : ?>
                    <tr class="record-row">
                        <td class="no"><?php echo $i++; ?></td>
                        <td class="date"><?php echo esc_html( $record['date'] ); ?></td>
                        <td><?php echo esc_html( $record['course'] ); ?></td>
                        <td class="code"><?php echo esc_html( $record['code'] ); ?></td>
                        <td><?php echo esc_html( $record['organization'] ); ?></td>
                        <td class="point"><?php echo esc_html( $record['cpd_point'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
