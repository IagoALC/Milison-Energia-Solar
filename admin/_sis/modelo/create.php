<?php
$AdminLevel = LEVEL_WC_USERS;
if (empty($DashboardLogin) || empty($Admin) || ($Admin['user_level'] == 4 || $Admin['user_level'] == 6)) :
    die('<div style="text-align: center; margin: 5% 0; color: #C54550; font-size: 1.6em; font-weight: 400; background: #fff; float: left; width: 100%; padding: 30px 0;"><b>ACESSO NEGADO:</b> Você não esta logado<br>ou não tem permissão para acessar essa página!</div>');
endif;

// AUTO INSTANCE OBJECT READ
if (empty($Read)) :
    $Read = new Read;
endif;

// AUTO INSTANCE OBJECT READ
if (empty($Create)) :
    $Create = new Create;
endif;

$UserId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($UserId) :
    $Read->ExeRead(DB_USERS, "WHERE user_id = :id", "id={$UserId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);

        if ($user_level > $_SESSION['userLogin']['user_level']) :
            $_SESSION['trigger_controll'] = "<b>OPPSS {$Admin['user_name']}</b>. Por questões de segurança, é restrito o acesso a Cliente com nível de acesso maior que o seu!";
            header('Location: dashboard.php?wc=modelo/home');
            exit;
        endif;
    else :
        $_SESSION['trigger_controll'] = "<b>OPPSS {$Admin['user_name']}</b>, você tentou editar um Cliente que não existe ou que foi removido recentemente!";
        header('Location: dashboard.php?wc=modelo/home');
        exit;
    endif;
else :
    $CreateUserDefault = [
        "user_registration" => date('Y-m-d H:i:s'),
        "user_level" => 1,
        "user_pro_id" => $Admin['user_id']
    ];
    $Create->ExeCreate(DB_USERS, $CreateUserDefault);
    header("Location: dashboard.php?wc=modelo/create&id={$Create->getResult()}");
    exit;
endif;
?>

<script>
    function SelType(type) {
        if (type == 1) {
            document.getElementById("div_type_pf").style.display = "block";
            document.getElementById("div_type_pj1").style.display = "none";
            document.getElementById("user_insc_est").value = "";
            document.getElementById("user_insc_mun").value = "";
            //document.getElementById("add").href = "#add_pf";
        }
        if (type == 2) {
            document.getElementById("div_type_pf").style.display = "none";
            document.getElementById("div_type_pj1").style.display = "block";
            document.getElementById("user_document").value = "";
            document.getElementById("user_rg").value = "";
            //document.getElementById("add").href = "#add_pj";
        }
    }
</script>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Profissionais</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=modelo/home" class="text-muted">Profissionais</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="#" class=""><?= $user_name; ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="content flex-column-fluid" id="kt_content">

    <div class="d-flex flex-row">
        <?php require("menu.inc.php") ?>
        <div class="flex-row-fluid ml-lg-8">
            <div class="card card-custom">
                <div class="card-header py-3">
                    <div class="card-title align-items-start flex-column">
                        <h3 class="card-label font-weight-bolder text-dark">Dados Iniciais</h3>
                        <span class="text-muted font-weight-bold font-size-sm mt-1"><?= $user_name; ?></span>
                    </div>
                    <div class="card-toolbar">
                        <span rel='dashboard_header_search' callback='Clientes' callback_action='delete' class='btn btn-danger j_swal_action' id='<?= $UserId; ?>'>Deletar Registro!</span>
                    </div>
                </div>
                <form class="auto_save j_tab_home tab_create" name="user_manager" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Clientes" />
                    <input type="hidden" name="callback_action" value="manager" />
                    <input type="hidden" name="user_id" value="<?= $UserId; ?>" />
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-lg-6">
                                <label>Nome:</label>
                                <input class="form-control" value="<?= $user_name; ?>" type="text" name="user_name" placeholder="Primeiro Nome" />
                            </div>
                            <div class="col-lg-6">
                                <label>Último Nome:</label>
                                <input class="form-control" value="<?= $user_lastname; ?>" type="text" name="user_lastname" placeholder="Último Nome" />
                            </div>
                        </div>
                        <div class="form-group row">

                            <div class="col-lg-6">
                                <label>Tipo de Cliente:</label>
                                <select class="form-control" name="user_type" required onchange="SelType(this.value)">
                                    <option value="1" <?= ($user_type == 1 ? 'selected="selected"' : ''); ?>>Pessoa Física</option>
                                    <option value="2" <?= ($user_type == 2 ? 'selected="selected"' : ''); ?>>Pessoa Jurídica</option>
                                </select>
                            </div>
                        </div>
                        <div id="div_type_pf" style="<?= $user_type == 2 ? "display: none" : "" ?>">
                            <div class="form-group row">
                                <div class="col-lg-6">
                                    <label>CPF:</label>
                                    <input class="form-control formCpf" value="<?= $user_document; ?>" type="text" name="user_document" id="user_document" placeholder="CPF" />
                                </div>
                                <div class="col-lg-6">
                                    <label>RG:</label>
                                    <input class="form-control" value="<?= $user_rg; ?>" type="text" name="user_rg" id="user_rg" placeholder="RG:" />
                                </div>
                            </div>
                        </div>
                        <div id="div_type_pj1" class="label_50" style="<?= $user_type == 1 ? "display: none" : "" ?>">
                            <div class="form-group row">
                                <div class="col-lg-12">
                                    <label>CNPJ:</label>
                                    <input class="form-control formCnpj" value="<?= $user_document; ?>" type="text" name="user_document2" id="user_document2" placeholder="CNPJ" />
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-lg-6">
                                <label>Telefone:</label>
                                <input class="form-control formPhone" value="<?= $user_telephone; ?>" type="text" name="user_telephone" placeholder="(55) 5555.5555" />
                            </div>
                            <div class="col-lg-6">
                                <label>Celular:</label>
                                <input class="form-control formPhone" value="<?= $user_cell; ?>" type="text" name="user_cell" placeholder="(55) 5555.5555" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-lg-6">
                                <label>E-mail:</label>
                                <input class="form-control" value="<?= $user_email; ?>" type="text" name="user_email" placeholder="E-mail" />
                            </div>
                            <?php
                            if ($Admin['user_level'] != 7) :
                            ?>
                                <div class="col-lg-6">
                                    <label>Profissional:</label>
                                    <select name="user_pro_id" id="" class="form-control">
                                        <?php
                                        $Read->ExeRead(DB_USERS, "WHERE user_level = 7");
                                        if ($Read->getResult()) :
                                            foreach ($Read->getResult() as $User) :
                                        ?>
                                                <option value="<?= $User['user_id'] ?>" <?= $User['user_id'] == $user_id ? 'selected' : '' ?>><?= $User['user_name'] ?> <?= $User['user_lastname'] ?></option>
                                        <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </select>
                                </div>
                            <?php
                            endif;
                            ?>
                        </div>


                        <div class="card-footer">
                            <div class="row">
                                <div class="col-lg-12 text-lg-right">
                                    <button type="submit" class="btn btn-primary mr-2">Atualizar</button>
                                    <img class="form_load none fl_right" style="margin-left: 10px; margin-top: 2px; display: none" alt="Enviando Requisição!" title="Enviando Requisição!" src="_img/load.gif" />
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>