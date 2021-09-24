<!DOCTYPE HTML>
<?php
ob_start();
session_start();
require '../_app/Config.inc.php';
require '../_cdn/cronjob.php';

if (isset($_SESSION['userLogin']) && isset($_SESSION['userLogin']['user_level']) && $_SESSION['userLogin']['user_level'] >= 6):
    $Read = new Read;
    $Read->FullRead("SELECT user_level FROM " . DB_USERS . " WHERE user_id = :user", "user={$_SESSION['userLogin']['user_id']}");
    if (!$Read->getResult() || $Read->getResult()[0]['user_level'] < 6):
        unset($_SESSION['userLogin']);
        header('Location: ./index.php');
        exit;
    else:
        $Admin = $_SESSION['userLogin'];
        $Admin['user_thumb'] = (!empty($Admin['user_thumb']) && file_exists("../uploads/{$Admin['user_thumb']}") && !is_dir("../uploads/{$Admin['user_thumb']}") ? $Admin['user_thumb'] : '../admin/_img/no_avatar.jpg');
        $DashboardLogin = true;
    endif;
else:
    unset($_SESSION['userLogin']);
    header('Location: ./index.php');
    exit;
endif;

$AdminLogOff = filter_input(INPUT_GET, 'logoff', FILTER_VALIDATE_BOOLEAN);
if ($AdminLogOff):
    $_SESSION['trigger_login'] = Erro("<b>LOGOFF:</b> Olá {$Admin['user_name']}, você desconectou com sucesso do " . ADMIN_NAME . ", volte logo!");
    unset($_SESSION['userLogin']);
    header('Location: ./index.php');
    exit;
endif;

$getViewInput = filter_input(INPUT_GET, 'wc', FILTER_DEFAULT);
$getView = ($getViewInput == 'home' ? 'home' . ADMIN_MODE : $getViewInput);

/*
  if (!file_exists("dashboard.json")):
  echo "<span class='wc_domain_license icon-key icon-notext wc_tooltip radius'></span>";
  endif; */

/*
  //SITEMAP GENERATE (1X DAY)
  $SiteMapCheck = fopen('sitemap.txt', "a+");
  $SiteMapCheckDate = fgets($SiteMapCheck);
  if ($SiteMapCheckDate != date('Y-m-d')):
  $SiteMapCheck = fopen('sitemap.txt', "w");
  fwrite($SiteMapCheck, date('Y-m-d'));
  fclose($SiteMapCheck);

  $SiteMap = new Sitemap;
  $SiteMap->exeSitemap(DB_AUTO_PING);
  endif; */
?>
<html lang="en">
    <!--begin::Head-->
    <head><base href="">
        <meta charset="utf-8" />
        <title><?= ADMIN_NAME; ?> - <?= SITE_NAME; ?></title>
        <meta name="description" content="<?= ADMIN_DESC; ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <!--begin::Fonts-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
        <!--end::Fonts-->
        <!--begin::Page Vendors Styles(used by this page)-->
        <link href="_assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
        <link href="_assets/plugins/custom/fullcalendar/fullcalendar.bundle.css" rel="stylesheet" type="text/css" />
        <!--end::Page Vendors Styles-->
        <!--begin::Global Theme Styles(used by all pages)-->
        <link href="_assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="_assets/plugins/custom/prismjs/prismjs.bundle.css" rel="stylesheet" type="text/css" />
        <link href="_assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
        <!--end::Global Theme Styles-->
        <!--begin::Layout Themes(used by all pages)-->
        <!--end::Layout Themes-->
        <link rel="base" href="<?= BASE; ?>/admin/">
        <link rel="shortcut icon" href="<?= BASE; ?>/admin/_img/favicon.png" />
    </head>
    <!--end::Head-->
    <!--begin::Body--><?php /* style="background-image: url(_assets/media/bg/bg-5.jpg);background-color: #56077E;" */ ?>
    <body id="kt_body" class="header-fixed subheader-enabled page-loading" style="background-image: url(_assets/media/bg/bg-2.jpg);background-color: #000;">
        <!--begin::Main-->
        <div class="d-flex flex-column flex-root">
            <!--begin::Page-->
            <div class="d-flex flex-row flex-column-fluid page">
                <!--begin::Wrapper-->
                <div class="d-flex flex-column flex-row-fluid wrapper" id="kt_wrapper">
                    <?php
                    require ("inc/header.php");
                    require ("inc/menu.php");

                    if (file_exists('../DATABASE.sql')):
                        echo "<div>";
                        echo Erro("<span class='al_center'><b class='icon-warning'>IMPORTANTE:</b> Para sua segurança delete o arquivo DATABASE.sql da pasta do projeto! <a class='btn btn_yellow' href='dashboard.php?wc=home&database=true' title=''>Deletar Agora!</a></span>", E_USER_ERROR);
                        echo "</div>";

                        $DeleteDatabase = filter_input(INPUT_GET, 'database', FILTER_VALIDATE_BOOLEAN);
                        if ($DeleteDatabase):
                            unlink('../DATABASE.sql');
                            header('Location: dashboard.php?wc=home');
                            exit;
                        endif;
                    endif;

                    if (!file_exists("../license.txt")):
                        echo "<div>";
                        echo Erro("<span class='al_center'><b class='icon-warning'>ATENÇÃO:</b> O license.txt não está presente na raiz do projeto. Utilizar o Work Control® sem esse arquivo caracteriza cópia não licenciada.", E_USER_ERROR);
                        echo "</div>";
                    endif;

                    if (ADMIN_MAINTENANCE):
                        echo "<div>";
                        echo Erro("<span class='al_center'><b class='icon-warning'>IMPORTANTE:</b> O modo de manutenção está ativo. Somente usuários administradores podem ver o site assim!</span>", E_USER_ERROR);
                        echo "</div>";
                    endif;

                    //DB TEST
                    $Read->FullRead("SELECT VERSION() as mysql_version");
                    if ($Read->getResult()):
                        $MysqlVersion = $Read->getResult()[0]['mysql_version'];
                        if (!stripos($MysqlVersion, "MariaDB")):
                            echo "<div>";
                            echo Erro('<span class="al_center"><b class="icon-warning">ATENÇÃO:</b> O Work Control® foi projetado com <b>banco de dados MariaDB superior a 10.1</b>, você está usando ' . $MysqlVersion . '!</span>', E_USER_ERROR);
                            echo "</div>";
                        endif;
                    endif;

                    //PHP TEST
                    $PHPVersion = phpversion();
                    if ($PHPVersion < '5.6'):
                        echo "<div>";
                        echo Erro('<span class="al_center"><b class="icon-warning">ATENÇÃO:</b> O Work Control® foi projetado com <b>PHP 5.6 ou superior</b>, a versão do seu PHP é ' . $PHPVersion . '!</span>', E_USER_ERROR);
                        echo "</div>";
                    endif;

                    //QUERY STRING
                    if (!empty($getView)):
                        $includepatch = __DIR__ . '/_sis/' . strip_tags(trim($getView)) . '.php';
                    else:
                        $includepatch = __DIR__ . '/_sis/' . 'dashboard.php';
                    endif;
                    ?>
                    <div class="d-flex flex-row flex-column-fluid container-fluid">
                        <div class="main d-flex flex-column flex-row-fluid">
                            <?php
                            if (file_exists(__DIR__ . "/_siswc/" . strip_tags(trim($getView)) . '.php')):
                                require_once __DIR__ . "/_siswc/" . strip_tags(trim($getView)) . '.php';
                            elseif (file_exists($includepatch)):
                                require_once($includepatch);
                            else:
                                $_SESSION['trigger_controll'] = "<b>OPPSSS:</b> <span class='fontred'>_sis/{$getView}.php</span> ainda está em contrução!";
                                header('Location: dashboard.php?wc=home');
                                exit;
                            endif;
                            ?>
                        </div>
                    </div>
                    <?php
                    require("inc/footer.php");
                    ?>
                </div>
            </div>
        </div>
        <!--end::Main-->
        <?php require("inc/sidebar.right.php"); ?>
        <!--begin::Scrolltop-->
        <div id="kt_scrolltop" class="scrolltop">
            <span class="svg-icon">
                <!--begin::Svg Icon | path:assets/media/svg/icons/Navigation/Up-2.svg-->
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                <polygon points="0 0 24 0 24 24 0 24" />
                <rect fill="#000000" opacity="0.3" x="11" y="10" width="2" height="10" rx="1" />
                <path d="M6.70710678,12.7071068 C6.31658249,13.0976311 5.68341751,13.0976311 5.29289322,12.7071068 C4.90236893,12.3165825 4.90236893,11.6834175 5.29289322,11.2928932 L11.2928932,5.29289322 C11.6714722,4.91431428 12.2810586,4.90106866 12.6757246,5.26284586 L18.6757246,10.7628459 C19.0828436,11.1360383 19.1103465,11.7686056 18.7371541,12.1757246 C18.3639617,12.5828436 17.7313944,12.6103465 17.3242754,12.2371541 L12.0300757,7.38413782 L6.70710678,12.7071068 Z" fill="#000000" fill-rule="nonzero" />
                </g>
                </svg>
                <!--end::Svg Icon-->
            </span>
        </div>
        <!--end::Scrolltop-->
        <?php //require("inc/icones.right.php");  ?>
        <script>var HOST_URL = "https://preview.keenthemes.com/metronic/theme/html/tools/preview";</script>
        <!--begin::Global Config(global config for global JS scripts)-->
        <script>var KTAppSettings = {"breakpoints": {"sm": 576, "md": 768, "lg": 992, "xl": 1200, "xxl": 1200}, "colors": {"theme": {"base": {"white": "#ffffff", "primary": "#8950FC", "secondary": "#E5EAEE", "success": "#1BC5BD", "info": "#6993FF", "warning": "#FFA800", "danger": "#F64E60", "light": "#F3F6F9", "dark": "#212121"}, "light": {"white": "#ffffff", "primary": "#EEE5FF", "secondary": "#ECF0F3", "success": "#C9F7F5", "info": "#E1E9FF", "warning": "#FFF4DE", "danger": "#FFE2E5", "light": "#F3F6F9", "dark": "#D6D6E0"}, "inverse": {"white": "#ffffff", "primary": "#ffffff", "secondary": "#212121", "success": "#ffffff", "info": "#ffffff", "warning": "#ffffff", "danger": "#ffffff", "light": "#464E5F", "dark": "#ffffff"}}, "gray": {"gray-100": "#F3F6F9", "gray-200": "#ECF0F3", "gray-300": "#E5EAEE", "gray-400": "#D6D6E0", "gray-500": "#B5B5C3", "gray-600": "#80808F", "gray-700": "#464E5F", "gray-800": "#1B283F", "gray-900": "#212121"}}, "font-family": "Poppins"};</script>
        <script>
            var KTAppOptions = {
            "colors": {
            "state": {
            "brand": "#716aca",
                    "light": "#ffffff",
                    "dark": "#282a3c",
                    "primary": "#7F2136",
                    "success": "#34bfa3",
                    "info": "#36a3f7",
                    "warning": "#ffb822",
                    "danger": "#fd3995"
            },
                    "base": {
                    "label": ["#c5cbe3", "#a1a8c3", "#3d4465", "#3e4466"],
                            "shape": ["#f0f3ff", "#d9dffa", "#afb4d4", "#646c9a"]
                    }
            }
            };
        </script>
        <!--end::Global Config-->
        <!--begin::Global Theme Bundle(used by all pages)-->
        <script src="_assets/plugins/global/plugins.bundle.js"></script>
        <script src="_assets/plugins/custom/prismjs/prismjs.bundle.js"></script>
        <script src="_assets/js/scripts.bundle.js"></script>
        <!--end::Global Theme Bundle-->
        <script src="_assets/plugins/custom/fullcalendar/fullcalendar.bundle.js"></script>
        <script src="_assets/plugins/custom/datatables/datatables.bundle.js"></script>
        <script src='_assets/plugins/custom/tinymce/tinymce.bundle.js'></script>
        <script src='_assets/js/pages/crud/forms/editors/tinymce.js'></script>
        <script src="_assets/js/pages/crud/forms/widgets/select2.js"></script>
        <?php
        if (isset($SendToEnd)) :
            echo $SendToEnd;
        endif;
        if (strstr($getViewInput, 'map/')):
            require("_sis/map/files/assets/map-cars.js.php");
        endif;
        ?>
        <script src="_assets/js/pages/widgets.js"></script>
        <script src="_js/workcontrol.js" type="text/javascript"></script>
        <?php
        if (isset($_SESSION['trigger_controll'])) :
            ?>
            <script>
                $(function () {
                toastr.options = {
                "closeButton": true,
                        "debug": false,
                        "newestOnTop": false,
                        "progressBar": true,
                        "positionClass": "toast-top-right",
                        "preventDuplicates": false,
                        "showDuration": "300",
                        "hideDuration": "1000",
                        "timeOut": "5000",
                        "extendedTimeOut": "1000",
                        "showEasing": "swing",
                        "hideEasing": "linear",
                        "showMethod": "fadeIn",
                        "hideMethod": "fadeOut"
                };
                toastr.error(<?= $_SESSION['trigger_controll'] ?>);
                });
            </script>
            <?php
            unset($_SESSION['trigger_controll']);
        endif;
        ?>
    </body>
</html>
<?php
ob_end_flush();
