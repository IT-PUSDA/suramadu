<?php
// Cek session sudah dilakukan di admin.php, jadi kita hanya butuh config
require_once(BASE_PATH . '/src/include/config.php');
?>

<head>

    <title>Aplikasi Manajemen Surat</title>

    <!-- Meta START -->
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <?php
    $query = mysqli_query($config, "SELECT logo from tbl_instansi");
    list($logo) = mysqli_fetch_array($query);
    if (!empty($logo)) {
        echo '<link rel="icon" href="upload/' . $logo . '" type="image/x-icon">';
    } else {
        echo '<link rel="icon" href="asset/img/logo.png" type="image/x-icon">';
    }
    ?>
    <!-- Meta END -->

    <!--[if lt IE 9]>
    <script src="asset/js/html5shiv.min.js"></script>
    <![endif]-->

    <!-- Global style START -->
    <!-- 1. Bootstrap CSS (Contoh menggunakan Bootstrap 3) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
    <!-- 2. Bootstrap DateTimePicker CSS - Dihapus karena konflik -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css" /> -->

    <link type="text/css" rel="stylesheet" href="asset/css/materialize.min.css" media="screen,projection" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" integrity="sha512-aOG0c6nPNzGk+5zjwyJaoRUgCdOrfSDhmMID2u4+OIslr0GjpLKo7Xm0Ao3xmpM4T8AmIouRkqwj1nrdVsLKEQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style type="text/css">
        body {
            background: #fff;
        }

        .bg::before {
            content: '';
            background-image: url('asset/img/background.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: scroll;
            position: fixed;
            z-index: -1;
            top: 0;
            bottom: 0;
            left: 0;
            right: 0;
            opacity: 0.10;
            filter: alpha(opacity=10);
        }

        #header-instansi {
            margin-top: 1%;
        }

        .ams {
            font-size: 1.8rem;
        }

        .grs {
            position: absolute;
            margin: 10px 0;
            background-color: #fff;
            height: 42px;
            width: 1px;
        }

        #menu {
            margin-left: 20px;
        }

        .title {
            background: #333;
            border-radius: 3px 3px 0 0;
            margin: -20px -20px 25px;
            padding: 20px;
        }

        .logo {
            border-radius: 50%;
            margin: 0 15px 15px 0;
            width: 90px;
            height: 90px;
        }

        .logoside {
            border-radius: 50%;
            margin: 0 auto;
            width: 125px;
            height: 125px;
        }

        .ins {
            font-size: 1.8rem;
        }

        .almt {
            font-size: 1.15rem;
        }

        .description {
            font-size: 1.4rem;
        }

        .jarak {
            height: 13.4rem;
        }

        .hidden {
            display: none;
        }

        .add {
            font-size: 1.45rem;
            padding: 0.1rem 0;
        }

        .jarak-card {
            margin-top: 1rem;
        }

        .jarak-filter {
            margin: -12px 0 5px;
        }

        #footer {
            background: #546e7a;
        }

        .warna {
            color: #444;
        }

        .agenda {
            font-size: 1.39rem;
            padding-left: 8px;
        }

        .hid {
            display: none;
        }

        .galeri {
            width: 100%;
            height: 26rem;
        }

        .gbr {
            float: right;
            width: 80%;
            height: auto;
        }

        .file {
            width: 70%;
            height: auto;
        }

        .kata {
            font-size: 1.04rem;
        }

        #alert-message {
            font-size: 0.9rem;
        }

        .notif {
            margin: -1rem 0 !important;
        }

        .green-text,
        .red-text {
            font-weight: normal !important;
        }

        .lampiran {
            color: #444 !important;
            font-weight: normal !important;
        }

        .waves-green {
            margin-bottom: -20px !important;
        }

        div.callout {
            height: auto;
            width: auto;
            float: left;
        }

        div.callout {
            position: relative;
            padding: 13px;
            border-radius: 3px;
            margin: 25px;
            min-height: 46px;
            top: -25px;
        }

        .callout::before {
            content: "";
            width: 0px;
            height: 0px;
            border: 0.8em solid transparent;
            position: absolute;
        }

        .callout.bottom::before {
            left: 25px;
            top: -20px;
            border-bottom: 10px solid #ffcdd2;
        }

        .pace {
            -webkit-pointer-events: none;
            pointer-events: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            -webkit-transform: translate3d(0, -50px, 0);
            -ms-transform: translate3d(0, -50px, 0);
            transform: translate3d(0, -50px, 0);
            -webkit-transition: -webkit-transform .5s ease-out;
            -ms-transition: -webkit-transform .5s ease-out;
            transition: transform .5s ease-out;
        }

        .pace.pace-active {
            -webkit-transform: translate3d(0, 0, 0);
            -ms-transform: translate3d(0, 0, 0);
            transform: translate3d(0, 0, 0);
        }

        .pace .pace-progress {
            display: block;
            position: fixed;
            z-index: 2000;
            top: 0;
            right: 100%;
            width: 100%;
            height: 3px;
            background: #2196f3;
            pointer-events: none;
        }

        @media print {

            .side-nav,
            .secondary-nav,
            .jarak-form,
            .center,
            .hide-on-med-and-down,
            .dropdown-content,
            .button-collapse,
            .btn-large,
            .footer-copyright {
                display: none;
            }

            body {
                font-size: 12px;
                color: #212121;
            }

            .hid {
                display: block;
                font-size: 16px;
                text-transform: uppercase;
                margin-bottom: 0;
            }

            .add {
                font-size: 15px !important;
            }

            .agenda {
                font-size: 13px;
                text-align: center;
                color: #212121;
            }

            th,
            td {
                border: 1px solid #444 !important;
            }

            th {
                padding: 5px;
                display: table-cell;
                text-align: center;
                vertical-align: middle;
            }

            td {
                padding: 5px;
            }

            table {
                border-collapse: collapse;
                border-spacing: 0;
                font-size: 12px;
                color: #212121;
            }

            .container {
                margin-top: -20px !important;
            }
        }

        noscript {
            color: #fff;
        }

        /* Custom Table Styling */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table-header {
            font-weight: 600;
            font-size: 0.9rem;
        }

        #tbl {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        #tbl th {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-bottom: 2px solid #2196f3;
            padding: 15px 10px;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
        }

        #tbl td {
            padding: 12px 10px;
            vertical-align: top;
            border-bottom: 1px solid #e0e0e0;
            transition: background-color 0.3s ease;
        }

        #tbl tbody tr:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        #tbl tbody tr:hover .action-buttons .btn {
            transform: scale(1.05);
        }

        .action-buttons {
            display: flex;
            gap: 4px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            margin: 1px;
            border-radius: 50%;
            padding: 0;
            width: 36px;
            height: 36px;
            line-height: 36px;
            min-width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        }

        .action-buttons .btn i {
            font-size: 18px;
            margin: 0;
        }

        /* Header column styling enhancement */
        #tbl th .table-header {
            display: block;
            font-weight: 600;
            margin: 2px 0;
        }

        #tbl th small {
            color: rgba(0, 0, 0, 0.6);
            font-weight: 400;
        }

        #tbl th .material-icons.tiny {
            font-size: 16px;
            vertical-align: middle;
            color: #2196f3;
        }

        .card-panel {
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Mobile Responsiveness */
        @media only screen and (max-width: 992px) {

            #tbl th,
            #tbl td {
                padding: 8px 5px;
                font-size: 0.85rem;
            }

            .action-buttons {
                flex-direction: row;
                gap: 3px;
            }

            .action-buttons .btn {
                width: 32px;
                height: 32px;
                line-height: 32px;
                min-width: 32px;
            }

            .action-buttons .btn i {
                font-size: 16px;
            }
        }

        @media only screen and (max-width: 701px) {
            #colres {
                width: 100%;
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            #tbl {
                min-width: 800px !important;
                font-size: 0.8rem;
            }

            #tbl th {
                padding: 10px 5px;
                min-width: 80px;
            }

            #tbl td {
                padding: 8px 5px;
                min-width: 80px;
            }

            .action-buttons .btn {
                width: 28px;
                height: 28px;
                line-height: 28px;
                min-width: 28px;
            }

            .card-panel {
                margin: 2px 0 !important;
                padding: 5px !important;
            }
        }

        @media only screen and (max-width: 480px) {
            #tbl {
                min-width: 600px !important;
                font-size: 0.75rem;
            }

            .action-buttons .btn {
                width: 24px;
                height: 24px;
                line-height: 24px;
                min-width: 24px;
            }

            .action-buttons .btn i {
                font-size: 14px;
            }
        }
    </style>
    <!-- Global style END -->

</head>