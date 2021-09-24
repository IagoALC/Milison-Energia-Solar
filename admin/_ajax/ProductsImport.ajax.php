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
$CallBack = 'ProductsImport';
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
                                $CreateProduct = [
                                    "pdt_code" => $r[39] && is_int($r[39]) ? $r[39] : ($r[1] ? $r[1] : null),
                                    "pdt_title" => $r[3] ? $r[3] : null,
                                    "pdt_unity" => $r[5] ? $r[5] : null,
                                    "pdt_price" => $r[6] ? str_replace(',', '.', str_replace('.', '', $r[6])) : null,
                                    "pdt_inventory" => $r[9] ? str_replace(',', '.', str_replace('.', '', $r[9])) : null,
                                    "pdt_lastview" => $r[23] ? $r[23] : null,
                                    "pdt_created" => date('Y-m-d H:i:s'),
                                    "pdt_status" => 1,
                                    "pdt_delivered" => 0
                                ];

                                $Create->ExeCreate(DB_PDT, $CreateProduct);
                                if ($Create->getResult()):
                                    $CreateProductNFE = [
                                        "pdt_id" => $Create->getResult(),
                                        "nfe_tx_icms" => $r[14] && is_int($r[14]) ? $r[14] : null,
                                        "nfe_csosn" => $r[15] && is_int($r[15]) ? $r[15] : null,
                                        "nfe_cst" => $r[16] ? $r[16] : null,
                                        "nfe_cst_cofins" => $r[17] && is_int($r[17]) ? $r[17] : null,
                                        "nfe_cst_pis" => $r[18] && is_int($r[18]) ? $r[18] : null,
                                        "nfe_cest" => $r[19] ? $r[19] : null,
                                        "nfe_ncm" => $r[22] && is_int($r[22]) ? $r[22] : null,
                                        "nfe_cenq" => $r[35] ? $r[35] : null,
                                        "nfe_cst_ipi" => $r[36] && is_int($r[36]) ? $r[36] : null,
                                        "nfe_cfop" => $r[38] && is_int($r[38]) ? $r[38] : null,
                                    ];
                                    $Create->ExeCreate(DB_PDT_NFE, $CreateProductNFE);
                                endif;
                            endif;
                            $l++;
                        }
                        $jSON['trigger'] = AjaxErro("<b class='icon-checkmark'>UPLOAD REALIZADO:</b> Olá {$_SESSION['userLogin']['user_name']}, os Produtos foram importados!");
                        $jSON['redirect'] = "dashboard.php?wc=products/home";
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
