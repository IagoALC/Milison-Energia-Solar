<?php

session_start();
require '../../_app/Config.inc.php';
require '../../_app/Library/SimpleXLSX/SimpleXLSX.php';
$NivelAcess = 3;//LEVEL_WC_PRODUCTS;

if (!APP_PRODUCTS || empty($_SESSION['userLogin']) || empty($_SESSION['userLogin']['user_level']) || $_SESSION['userLogin']['user_level'] < $NivelAcess):
    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Você não tem permissão para essa ação ou não está logado como administrador!', E_USER_ERROR);
    echo json_encode($jSON);
    die;
endif;

usleep(50000);

//DEFINE O CALLBACK E RECUPERA O POST
$jSON = null;
$CallBack = 'ClientesImport';
$PostData = filter_input_array(INPUT_POST, FILTER_DEFAULT);

//VALIDA AÇÃO
if ($PostData && $PostData['callback_action'] && $PostData['callback'] == $CallBack):
//PREPARA OS DADOS
    $Case = $PostData['callback_action'];
    unset($PostData['callback'], $PostData['callback_action']);

// AUTO INSTANCE OBJECT READ
    if (empty($Read)):
        $Read = new Read;
    endif;

// AUTO INSTANCE OBJECT CREATE
    if (empty($Create)):
        $Create = new Create;
    endif;

// AUTO INSTANCE OBJECT UPDATE
    if (empty($Update)):
        $Update = new Update;
    endif;

// AUTO INSTANCE OBJECT DELETE
    if (empty($Delete)):
        $Delete = new Delete;
    endif;
    $Upload = new Upload('../../uploads/');

//SELECIONA AÇÃO
    switch ($Case):

        case 'list_import':
            $UploadXLXS = (!empty($_FILES['arquivo_excel']) ? $_FILES['arquivo_excel'] : null);
            if ($UploadXLXS):
                $Upload->File($UploadXLXS, time(), "excel", 20);
                if ($Upload->getResult()):
                    if ($xlsx = SimpleXLSX::parse('../../uploads/' . $Upload->getResult())) {
                        $dim = $xlsx->dimension();
                        $num_cols = $dim[0];
                        $num_rows = $dim[1];
                        $l = 0;
                        foreach ($xlsx->rows(0) as $r) {
                            if ($l > 0):
                                $CreateUser = [
                                    "user_type" => $r[0] == "Pessoa Física" ? 1 : 2,
                                    "user_name" => $r[1] ? $r[1] : null,
                                    "user_status" => $r[2] == "Ativo" ? 1 : 0,
                                    "user_document" => $r[3] ? $r[3] : null,
                                    "user_rg" => $r[4] ? $r[4] : null,
                                    "user_email" => $r[5] ? $r[5] : null,
                                    "user_email2" => $r[6] ? ($r[6] != "Não informado" ? $r[6] : null) : null,
                                    "user_cell" => $r[7] ? $r[7] : null,
                                    "user_telephone" => $r[8] ? $r[8] : null,
                                    "user_level" => 1
                                ];
                                $Create->ExeCreate(DB_USERS, $CreateUser);
                                if ($Create->getResult()):
                                    $CreateEnd = [
                                        "user_id" => $Create->getResult(),
                                        "addr_street" => $r[10] ? $r[10] : null,
                                        "addr_district" => $r[11] ? $r[11] : null,
                                        "addr_city" => $r[12] ? $r[12] : null,
                                        "addr_state" => $r[13] ? ($r[13] != "Não informado" ? $r[13] : null) : null,
                                        "addr_zipcode" => $r[14] ? $r[14] : null,
                                    ];
                                    $Create->ExeCreate(DB_USERS_ADDR, $CreateEnd);
                                endif;
                            endif;
                            $l++;
                        }
                        $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>UPLOAD REALIZADO:</b> Olá {$_SESSION['userLogin']['user_name']}, os Clientes foram importados!");
                        $jSON['redirect'] = "dashboard.php?wc=clientes/home";
                    } else {
                        $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b>' . SimpleXLSX::parseError(), E_USER_ERROR);
                    }
                else:
                    $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas não foi possível realizar o Upload!', E_USER_ERROR);
                endif;
            else:
                $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas selecione um arquivo para Upload!', E_USER_ERROR);
            endif;
            break;

    endswitch;

//RETORNA O CALLBACK
    if ($jSON):
        echo json_encode($jSON);
    else:
        $jSON['trigger'] = AjaxErro('<b class="icon-warning">OPSS:</b> Desculpe. Mas uma ação do sistema não respondeu corretamente. Ao persistir, contate o desenvolvedor!', E_USER_ERROR);
        echo json_encode($jSON);
    endif;
else:
//ACESSO DIRETO
    die('<br><br><br><center><h1>Acesso Restrito!</h1></center>');
endif;
