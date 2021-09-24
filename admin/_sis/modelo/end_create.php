<?php
$AdminLevel = 3;
if (empty($DashboardLogin) || empty($Admin) || ($Admin['user_level'] < 6)) :
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

$AddrId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$UserId = filter_input(INPUT_GET, 'user', FILTER_VALIDATE_INT);
if ($AddrId) :
    $Read->ExeRead(DB_USERS_ADDR, "WHERE addr_id = :id", "id={$AddrId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);

        $Read->ExeRead(DB_USERS, "WHERE user_id = :user", "user={$user_id}");
        if ($Read->getResult()) :
            extract($Read->getResult()[0]);
            $UserId = $user_id;
        else :
            $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar um endereço que não existe ou que foi removido recentemente!", E_USER_NOTICE);
            header('Location: dashboard.php?wc=clientes/home');
            exit;
        endif;
    else :
        $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar um endereço que não existe ou que foi removido recentemente!", E_USER_NOTICE);
        header('Location: dashboard.php?wc=clientes/home');
        exit;
    endif;
elseif ($UserId) :
    $NewAddres = ['user_id' => $UserId, 'addr_name' => 'Novo Endereço'];
    $Create->ExeCreate(DB_USERS_ADDR, $NewAddres);
    header('Location: dashboard.php?wc=clientes/end_create&id=' . $Create->getResult());
    exit;
else :
    $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar um endereço que não existe ou que foi removido recentemente!", E_USER_NOTICE);
    header('Location: dashboard.php?wc=clientes/home');
    exit;
endif;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Clientes</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=clientes/home" class="text-muted">Clientes</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="#" class=""><?= $user_name . " " . $user_lastname; ?></a>
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
                        <h3 class="card-label font-weight-bolder text-dark">Endereço</h3>
                        <span class="text-muted font-weight-bold font-size-sm mt-1"><?= $user_name . " " . $user_lastname; ?></span>
                    </div>
                    <div class="card-toolbar">
                        <span rel='dashboard_header_search' callback='Clientes' callback_action='addr_delete' class='btn btn-danger j_swal_action' id='<?= $AddrId; ?>'>Deletar Endereço!</span>
                    </div>
                </div>
                <form class="j_tab_home tab_create" name="user_add_address" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Clientes" />
                    <input type="hidden" name="callback_action" value="addr_add" />
                    <input type="hidden" name="addr_id" value="<?= $AddrId; ?>" />
                    <div class="card-body">
                        <div class="form-group row">
                            <div class="col-lg-12">
                                <label>Nome do Endereço:</label>
                                <input class="form-control" value="<?= $addr_name; ?>" type="text" name="addr_name" placeholder="Ex: Minha Casa:" required />
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-lg-6">
                                <label>CEP:</label>
                                <input class="form-control formCep wc_getCep" value="<?= $addr_zipcode; ?>" type="text" name="addr_zipcode" placeholder="Informe o CEP:" required />
                            </div>
                            <div class="col-lg-6">
                                <label>Rua:</label>
                                <input class="form-control wc_logradouro" value="<?= $addr_street; ?>" type="text" name="addr_street" placeholder="Nome da Rua:" required />
                            </div>

                        </div>

                        <div class="form-group row">
                            <div class="col-lg-6">
                                <label>Número:</label>
                                <input class="form-control" value="<?= $addr_number; ?>" type="text" name="addr_number" placeholder="Número:" required />
                            </div>
                            <div class="col-lg-6">
                                <label>Complemento:</label>
                                <input class="wc_complemento form-control" value="<?= $addr_complement; ?>" type="text" name="addr_complement" placeholder="Ex: Casa, Apto, Etc:" />
                            </div>
                        </div>

                        <div class="form-group row">

                            <div class="col-lg-6">
                                <label>Bairro:</label>
                                <input class="wc_bairro form-control" value="<?= $addr_district; ?>" type="text" name="addr_district" placeholder="Nome do Bairro:" required />
                            </div>

                            <div class="col-lg-6">
                                <label>Cidade:</label>
                                <input class="wc_localidade form-control" value="<?= $addr_city; ?>" type="text" name="addr_city" placeholder="Informe a Cidade:" required />
                            </div>

                        </div>

                        <div class="form-group row">

                            <div class="col-lg-6">
                                <label>Estado (UF):</label>
                                <input class="wc_uf form-control" value="<?= $addr_state; ?>" type="text" name="addr_state" maxlength="2" placeholder="Ex: SP" required />
                            </div>

                            <div class="col-lg-6">
                                <label>País:</label>
                                <input class="form-control" value="<?= ($addr_country ? $addr_country : 'Brasil'); ?>" type="text" name="addr_country" required />
                            </div>

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