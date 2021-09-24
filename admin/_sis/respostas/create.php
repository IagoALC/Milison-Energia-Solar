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

$FormularioId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($FormularioId) :
    $Read->ExeRead(DB_FORM, "WHERE formulario_id = :id", "id={$FormularioId}");
    if ($Read->getResult()) :
        $FormData = array_map('htmlspecialchars', $Read->getResult()[0]);
        extract($FormData);
    else :
        $_SESSION['trigger_controll'] = Erro("<b>OPPSS {$Admin['user_name']}</b>, você tentou editar um formulário que não existe ou que foi removido recentemente!", E_USER_NOTICE);
        header('Location: dashboard.php?wc=formularios/home');
        exit;
    endif;
else :
    $NewFormulario = [
        "formulario_registration" => date("Y-m-d"),
        "formulario_status" => 1
    ];
    $Create->ExeCreate(DB_FORM, $NewFormulario);
    header('Location: dashboard.php?wc=formularios/create&id=' . $Create->getResult());
    exit;
endif;
$i = 1;
?>

<div class="subheader py-2 py-lg-6" id="kt_subheader">
    <div class="w-100 d-flex align-items-center justify-content-between flex-wrap flex-sm-nowrap">
        <div class="d-flex align-items-center flex-wrap mr-1">
            <div class="d-flex align-items-baseline flex-wrap mr-5">
                <h5 class="text-dark font-weight-bold my-1 mr-5">Gestão de Prontuários</h5>
                <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-2 font-size-sm">
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=home" class="text-muted">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item text-muted">
                        <a href="dashboard.php?wc=formularios/home" class="text-muted">Prontuários</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="content flex-column-fluid" id="kt_content">

    <div class="d-flex flex-row">
        <div class="flex-row-fluid ml-lg-8">
            <div class="card card-custom">
                <div class="card-header py-3">
                    <div class="card-title align-items-start flex-column">
                        <h3 class="card-label font-weight-bolder text-dark">Dados Iniciais</h3>
                    </div>
                    <div class="card-toolbar">
                        <span rel='dashboard_header_search' callback='Formularios' callback_action='delete' class='btn btn-danger j_swal_action' id='<?= $FormularioId; ?>'>Deletar Registro!</span>
                    </div>
                </div>
                <form class="auto_save j_tab_home tab_create" name="user_manager" action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="callback" value="Formularios" />
                    <input type="hidden" name="callback_action" value="manager" />
                    <input type="hidden" name="formulario_id" value="<?= $FormularioId; ?>" />
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $Read->ExeRead(DB_FORM_PERG, "WHERE pergunta_formulario_id = :id", "id={$FormularioId}");
                            foreach ($Read->getResult() as $Reg) :
                                extract($Reg);
                            ?>
                                <div class="col-lg-4">
                                    <h3><?= $pergunta_title ?></h3>
                                    <?php
                                    if ($pergunta_type == 1) :
                                    ?>
                                        <select name="option_id" id="" class="form-control">
                                            <?php
                                            $Read->ExeRead(DB_FORM_PERG_OPT, "WHERE option_pergunta_id = :id", "id={$pergunta_id}");
                                            foreach ($Read->getResult() as $Reg) :
                                                extract($Reg);
                                            ?>
                                                <option value="<?= $option_id ?>" <?= $option_id == $option_id ? 'selected' : '' ?>><?= $option_title ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else : ?>
                                        <input type="text" class="form-control" name="option_title">
                                    <?php endif; ?>
                                </div>
                                <?php
                                if ($i % 3 == 0) :
                                ?>
                        </div>
                        <div class="row" style="padding-top: 1em">
                    <?php
                                endif;
                                $i++;
                            endforeach;
                    ?>
                        </div>
                        <div class="card-footer">
                            <div class="row">

                            </div>
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


    <!-- PERGUNTAS -->

    <div class="content flex-column-fluid" id="kt_content">
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label">Listagem de Perguntas
                        <span class="d-block text-muted pt-2 font-size-sm"></span>
                    </h3>
                </div>
                <div class="card-toolbar">
                    <a href="dashboard.php?wc=perguntas/create&formulario=<?= $formulario_id ?>" class="btn btn-primary font-weight-bolder">
                        <span class="svg-icon svg-icon-md">
                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                    <rect x="0" y="0" width="24" height="24" />
                                    <circle fill="#000000" cx="9" cy="15" r="6" />
                                    <path d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z" fill="#000000" opacity="0.3" />
                                </g>
                            </svg>
                        </span>Novo Registro
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-separate table-head-custom table-checkable" id="kt_datatable">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $Read->ExeRead(DB_FORM_PERG, "WHERE pergunta_formulario_id = $formulario_id");
                        foreach ($Read->getResult() as $Reg) :
                            extract($Reg);
                        ?>
                            <tr role="row">
                                <td style="text-align: left"><?= $pergunta_title ?></td>
                                <td nowrap="nowrap">
                                    <a href="dashboard.php?wc=perguntas/create&id=<?= $pergunta_id ?>" class="btn btn-sm btn-clean btn-icon mr-2" title="Edit details">
                                        <span class="svg-icon svg-icon-md">
                                            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                                <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                                    <rect x="0" y="0" width="24" height="24" />
                                                    <path d="M8,17.9148182 L8,5.96685884 C8,5.56391781 8.16211443,5.17792052 8.44982609,4.89581508 L10.965708,2.42895648 C11.5426798,1.86322723 12.4640974,1.85620921 13.0496196,2.41308426 L15.5337377,4.77566479 C15.8314604,5.0588212 16,5.45170806 16,5.86258077 L16,17.9148182 C16,18.7432453 15.3284271,19.4148182 14.5,19.4148182 L9.5,19.4148182 C8.67157288,19.4148182 8,18.7432453 8,17.9148182 Z" fill="#000000" fill-rule="nonzero" \ transform="translate(12.000000, 10.707409) rotate(-135.000000) translate(-12.000000, -10.707409) " />
                                                    <rect fill="#000000" opacity="0.3" x="5" y="20" width="15" height="2" rx="1" />
                                                </g>
                                            </svg>
                                        </span>
                                    </a>
                                    <?php /*
                                  <a href="javascript:;" class="btn btn-sm btn-clean btn-icon" title="Delete">
                                  <span class="svg-icon svg-icon-md">
                                  <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                  <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                  <rect x="0" y="0" width="24" height="24"/>
                                  <path d="M6,8 L6,20.5 C6,21.3284271 6.67157288,22 7.5,22 L16.5,22 C17.3284271,22 18,21.3284271 18,20.5 L18,8 L6,8 Z" fill="#000000" fill-rule="nonzero"/>
                                  <path d="M14,4.5 L14,4 C14,3.44771525 13.5522847,3 13,3 L11,3 C10.4477153,3 10,3.44771525 10,4 L10,4.5 L5.5,4.5 C5.22385763,4.5 5,4.72385763 5,5 L5,5.5 C5,5.77614237 5.22385763,6 5.5,6 L18.5,6 C18.7761424,6 19,5.77614237 19,5.5 L19,5 C19,4.72385763 18.7761424,4.5 18.5,4.5 L14,4.5 Z" fill="#000000" opacity="0.3"/>
                                  </g>
                                  </svg>
                                  </span>
                                  </a>
                                 */ ?>
                                </td>
                            </tr>
                        <?php
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>