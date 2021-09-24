$(function () {

    //##### PLUGGINS #####
    //####################

    //##### INPUT MASKs
    plugginMask();

    //##### INPUT MASK MONEY
    if ($('.formMoney').length) {
        plugginMaskMoney();
    }
    if ($('.formMoney2').length) {
        plugginMaskMoney2();
    }

    //##### CUSTOM SELECT
    if ($('.customSelect').length) {
        plugginSelect2();
    }

    //##### DATE PICKER
    if ($('.datepicker').length) {
        plugginDatePicker();
    }

    //##### DATE TIME PICKER
    if ($('.datetimepicker').length) {
        plugginDateTimePicker();
    }

    //##### TinyMCE
    if ($('.work_mce').length) {
        plugginTiny();
    }

    if ($('.work_mce_basic').length) {
        plugginTinyBasic();
    }

    //##### TOOLTIPS
    if ($('*[data-toggle="tooltip"]').length) {
        plugginTooltips();
    }

    //##### INPUT IMAGE
    $('.wc_loadimage').change(function () {
        var input = $(this);
        var target = $('.' + input.attr('name'));
        var fileDefault = target.attr('default');

        if (!input.val()) {
            target.fadeOut('fast', function () {
                $(this).attr('src', fileDefault).fadeIn('slow');
            });
            return false;
        }
        if (this.files && (this.files[0].type.match("image/jpeg") || this.files[0].type.match("image/png"))) {
            var reader = new FileReader();
            reader.onload = function (e) {
                if (target.prop('tagName') == 'DIV') {
                    target.fadeOut('fast', function () {
                        $(this).css('background-image', 'url(' + e.target.result + ')').fadeIn('fast');
                    });
                } else {
                    target.fadeOut('fast', function () {
                        $(this).attr('src', e.target.result).width('100%').fadeIn('fast');
                    });
                }
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            Trigger('<div class="trigger trigger_alert trigger_ajax"><b class="icon-warning">ERRO AO SELECIONAR:</b> O arquivo <b>' + this.files[0].name + '</b> não é válido! <b>Selecione uma imagem JPG ou PNG!</b></div>');

            if (target.prop('tagName') == 'DIV') {
                target.fadeOut('fast', function () {
                    $(this).css('background-image', 'url(' + fileDefault + ')').fadeIn('fast');
                });
            } else {
                target.fadeOut('fast', function () {
                    $(this).attr('src', fileDefault).width('100%').fadeIn('fast');
                });
            }
            input.val('');
            return false;
        }
    });


    //Coloca todos os formulários em AJAX mode e inicia LOAD ao submeter!
    $('html').on('submit', 'form:not(.ajax_off)', function () {
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var callback = form.find('input[name="callback"]').val();
        var callback_action = form.find('input[name="callback_action"]').val();
        var Load = new KTDialog({'type': 'loader', 'placement': 'top center', 'message': 'Aguarde ...'});

        // if (typeof tinyMCE !== 'undefined') {
        //     tinyMCE.triggerSave();
        // }

        form.ajaxSubmit({
            url: '_ajax/' + callback + '.ajax.php',
            data: {callback_action: callback_action},
            dataType: 'json',
            beforeSubmit: function () {
                btn.addClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', true);
                Load.show();
            },
            uploadProgress: function (evento, posicao, total, completo) {
                console.log('trabalhar no envio arquivos grandes');
                // var porcento = completo + '%';
                // $('.workcontrol_upload_progrees').text(porcento);

                // if (completo <= '80') {
                //     $('.workcontrol_upload').fadeIn().css('display', 'flex');
                // }
                // if (completo >= '99') {
                //     $('.workcontrol_upload').fadeOut('slow', function () {
                //         $('.workcontrol_upload_progrees').text('0%');
                //     });
                // }
                // //PREVENT TO RESUBMIT IMAGES GALLERY
                // form.find('input[name="image[]"]').replaceWith($('input[name="image[]"]').clone());
            },
            success: function (data) {
                //REMOVE LOAD
                Load.hide();
                btn.removeClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', false);
                if (form.find('.form_load')) {
                    form.find('.form_load').css('display', 'none');
                }

                //EXIBE CALLBACKS
                if (data.trigger) {
                    Trigger(data.trigger);
                }

                //REDIRECIONA
                if (data.redirect) {
                    $('.workcontrol_upload p').html("Atualizando dados, aguarde!");
                    $('.workcontrol_upload').fadeIn().css('display', 'flex');
                    window.setTimeout(function () {
                        window.location.href = data.redirect;
                        if (window.location.hash) {
                            window.location.reload();
                        }
                    }, 1500);
                }

                //INTERAGE COM TINYMCE
                if (data.tinyMCE) {
                    tinyMCE.activeEditor.insertContent(data.tinyMCE);
                    $('.workcontrol_imageupload').fadeOut('slow', function () {
                        $('.workcontrol_imageupload .image_default').attr('src', '../tim.php?src=admin/_img/no_image.jpg&w=500&h=300');
                    });
                }

                //GALLETY UPDATE HTML
                if (data.gallery) {
                    form.find('.gallery').fadeTo('300', '0.5', function () {
                        $(this).html($(this).html() + data.gallery).fadeTo('300', '1');
                    });
                }

                //DATA CONTENT IN j_content
                if (data.content) {
                    if (typeof (data.content) === 'string') {
                        $('.j_content').fadeTo('300', '0.5', function () {
                            $(this).html(data.content).fadeTo('300', '1');
                        });
                    } else if (typeof (data.content) === 'object') {
                        $.each(data.content, function (key, value) {
                            $(key).fadeTo('300', '0.5', function () {
                                $(this).html(value).fadeTo('300', '1');
                            });
                        });
                    }
                }

                //DATA DINAMIC CONTENT
                if (data.divcontent) {
                    if (typeof (data.divcontent) === 'string') {
                        $(data.divcontent[0]).html(data.divcontent[1]);
                    } else if (typeof (data.divcontent) === 'object') {
                        $.each(data.divcontent, function (key, value) {
                            $(key).html(value);
                        });
                    }
                }

                //DATA DINAMIC FADEOUT
                if (data.divremove) {
                    if (typeof (data.divremove) === 'string') {
                        $(data.divremove).fadeOut();
                    } else if (typeof (data.divremove) === 'object') {
                        $.each(data.divremove, function (key, value) {
                            $(value).fadeOut();
                        });
                    }
                }

                //DATA CLICK
                if (data.forceclick) {
                    if (typeof (data.forceclick) === 'string') {
                        setTimeout(function () {
                            $(data.forceclick).click();
                        }, 250);
                    } else if (typeof (data.forceclick) === 'object') {
                        $.each(data.forceclick, function (key, value) {
                            setTimeout(function () {
                                $(value).click();
                            }, 250);
                        });
                    }
                }

                //DATA DOWNLOAD IN j_downloa
                if (data.download) {
                    $('.j_download').fadeTo('300', '0.5', function () {
                        $(this).html(data.download).fadeTo('300', '1');
                    });
                }

                //DATA HREF VIEW
                if (data.view) {
                    $('.wc_view').attr('href', data.view);
                }

                //DATA REORDER
                if (data.reorder) {
                    $('.wc_drag_active').removeClass('btn_yellow');
                    $('.wc_draganddrop').removeAttr('draggable');
                }

                //DATA CLEAR
                if (data.clear) {
                    form.trigger('reset');
                    if (form.find('.label_publish')) {
                        form.find('.label_publish').removeClass('active');
                    }
                }

                //DATA CLEAR INPUT
                if (data.inpuval) {
                    if (data.inpuval === 'null') {
                        $('.wc_value').val("");
                    } else {
                        $('.wc_value').val(data.inpuval);
                    }
                }

                //CLEAR INPUT FILE
                if (!data.error) {
                    form.find('input[type="file"]').val('');
                }

                //CLEAR NFE XML
                if (data.nfexml) {
                    $('.wc_nfe_xml').html("<a target='_blank' href='" + data.nfexml + "' title='Ver XML'>Ver XML</a>");
                }

                //DATA NFE PDF
                if (data.nfepdf) {
                    $('.wc_nfe_pdf').html("<a target='_blank' href='" + data.nfepdf + "' title='Ver PDF'>Ver PDF</a>");
                }

                //FIX FOR HIGHLIGHT
                setTimeout(function () {
                    if ($('*[class="brush: php;"]').length) {
                        $("head").append('<link rel="stylesheet" href="../_cdn/highlight.min.css">');
                        $.getScript('../_cdn/highlight.min.js', function () {
                            $('*[class="brush: php;"]').each(function (i, block) {
                                hljs.highlightBlock(block);
                            });
                        });
                    }
                }, 500);
            }
        });
        return false;
    });



    $('html').on('change', 'form.auto_save', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var callback = form.find('input[name="callback"]').val();
        var callback_action = form.find('input[name="callback_action"]').val();

        // if (typeof tinyMCE !== 'undefined') {
        //     tinyMCE.triggerSave();
        // }

        form.ajaxSubmit({
            url: '_ajax/' + callback + '.ajax.php',
            data: {callback_action: callback_action},
            dataType: 'json',
            uploadProgress: function (evento, posicao, total, completo) {
                // var porcento = completo + '%';
                // $('.workcontrol_upload_progrees').text(porcento);

                // if (completo <= '80') {
                //     $('.workcontrol_upload').fadeIn().css('display', 'flex');
                // }
                // if (completo >= '99') {
                //     $('.workcontrol_upload').fadeOut('slow', function () {
                //         $('.workcontrol_upload_progrees').text('0%');
                //     });
                // }
                // //PREVENT TO RESUBMIT IMAGES GALLERY
                // form.find('input[name="image[]"]').replaceWith($('input[name="image[]"]').clone());
            },
            success: function (data) {
                if (data.name) {
                    var input = form.find('.wc_name');
                    if (!input.val() || input.val() != data.name) {
                        input.val(data.name);
                    }

                    var inputfield = form.find('input[name*=_name]');
                    if (inputfield) {
                        inputfield.val(data.name);
                    }
                }

                if (data.gallery) {
                    form.find('.gallery').fadeTo('300', '0.5', function () {
                        $(this).html($(this).html() + data.gallery).fadeTo('300', '1');
                    });
                }

                if (data.view) {
                    $('.wc_view').attr('href', data.view);
                }

                if (data.reorder) {
                    $('.wc_drag_active').removeClass('btn_yellow');
                    $('.wc_draganddrop').removeAttr('draggable');
                }

                //CLEAR INPUT FILE
                if (!data.error) {
                    form.find('input[type="file"]').val('');
                }

                if (data.trigger && data.showtrigger) {
                    miniTrigger(data.trigger);
                }

                if (data.divcontent) {
                    if (typeof (data.divcontent) === 'string') {
                        $(data.divcontent[0]).html(data.divcontent[1]);
                    } else if (typeof (data.divcontent) === 'object') {
                        $.each(data.divcontent, function (key, value) {
                            $(key).html(value);
                        });
                    }
                }
            }
        });
    });

    //############## GET CEP
    $('.wc_getCep').change(function () {
        var cep = $(this).val().replace('-', '').replace('.', '');
        if (cep.length === 8) {
            $.get("https://viacep.com.br/ws/" + cep + "/json", function (data) {
                if (!data.erro) {
                    $('.wc_bairro').val(data.bairro);
                    $('.wc_complemento').val(data.complemento);
                    $('.wc_localidade').val(data.localidade);
                    $('.wc_logradouro').val(data.logradouro);
                    $('.wc_uf').val(data.uf);
                }
            }, 'json');
        }
    });

    //############## ACAO DE REORDENAR ELEMENTOS
    if ($('.j_enable-dragndrop').length) { // ok 
        $('.j_enable-dragndrop').click(function () {
            $(this).toggleClass('btn-warning');

            if ($('.j_enable-dragndrop').attr('draggable')) {
                $('.j_enable-dragndrop').removeAttr('draggable');
                $(".j_drag_itens").sortable("disable");
            } else {
                $('.j_enable-dragndrop').attr('draggable', true);

                $.getScript('./_js/jquery-ui.min.js', function () {
                    $(".j_drag_itens").sortable({
                        connectWith: ".j_drag_itens",
                        over: function (event, ui) {
                            $(this).find('.j_dragdrop_item.ui-sortable-helper').css({
                                'box-shadow': '0 0 5px 5px #e0e0e0',
                                'background-color': '#FFFFFF',
                            });
                        },
                        out: function (event, ui) {
                            $(this).find('.j_dragdrop_item').css({
                                'box-shadow': 'inherit',
                                'background-color': 'inherit',
                            });
                        },

                        stop: function (event, ui) {
                            var CallBack = ui.item.closest('.j_drag_itens').attr('callback');
                            var CallBackAction = ui.item.closest('.j_drag_itens').attr('callback_action');

                            Reorder = new Array();
                            $.each($(".j_dragdrop_item"), function (i, el) {
                                Reorder.push([el.id, i + 1]);
                            });

                            $.post('_ajax/' + CallBack + '.ajax.php', {callback: CallBack, callback_action: CallBackAction, data: Reorder});
                        }
                    }).disableSelection();
                });
            }
            return false
        });
    }
    ;

    //############## AVISOS SWEETALERT
    $('html, body').on('click', '.j_swal_action', function (e) {
        e.preventDefault();

        var button = $(this),
                Prevent = button,
                Id = button.attr('id'),
                RelTo = button.attr('rel'),
                RelId = button.attr('data-rel'),
                RelRel = button.attr('data-relrel'),
                Callback = button.attr('callback'),
                Callback_action = button.attr('callback_action'),
                CustomCallback = button.attr('customCallback'),
                Message = button.attr('data-confirm-text'),
                MessageContent = button.attr('data-confirm-message')

        // FOR SPECIFIC CASE
        var SpecificCallback = button.attr('data-callback');
        var SpecificCallbackAction = button.attr('data-callback_action');
        if (SpecificCallback != undefined)
            Callback = SpecificCallback;
        if (SpecificCallbackAction != undefined)
            Callback_action = SpecificCallbackAction;

        if (CustomCallback != undefined)
            $URL = CustomCallback;
        else
            $URL = '_ajax/' + Callback + '.ajax.php';

        if (MessageContent != undefined)
            $MessageContent = MessageContent;
        else
            $MessageContent = 'Uma vez excluído esse registro não poderá ser recuperado!';

        swal.fire({
            backdrop: false,
            title: Message,
            html: $MessageContent,
            type: "warning",
            reverseButtons: true,
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                $('.workcontrol_upload p').html("Processando ... aguarde !");
                $('.workcontrol_upload').fadeIn().css('display', 'flex');

                $.post($URL, {callback: Callback, callback_action: Callback_action, id: Id}, function (data) {
                    $('.workcontrol_upload').fadeOut();
                    if (data.trigger) {
                        Trigger(data.trigger);
                    }

                    // Mensagens de erro ou sucesso
                    if (data.error) {
                        swal.fire({
                            backdrop: false,
                            type: 'error',
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonText: 'Entendi',
                            title: 'ERRO',
                            html: data.error,
                        });
                    } else if (data.success) {
                        if (data.refresh || data.redirect) {
                            var confirmButton = false;
                        } else {
                            var confirmButton = true;
                        }

                        swal.fire({
                            backdrop: false,
                            type: "success",
                            showConfirmButton: confirmButton,
                            title: "SUCESSO",
                            html: data.success,
                        });
                        // Remove registro excluído
                        if (RelId != undefined) {
                            $('.' + RelTo + '#' + RelId).fadeOut('fast');
                            console.log('.' + RelTo + '#' + RelId);
                        } else if (RelRel != undefined) {
                            $('.' + RelTo + '[data-rel="' + RelRel + '"]').fadeOut('fast');
                            console.log('.' + RelTo + '[data-rel="' + RelRel + '"]');
                        } else {
                            $('html').find('.' + RelTo + '#' + Id).fadeOut('fast');
                            console.log('.' + RelTo + '#' + Id);
                        }
                    }

                    if (data.forceclick) {
                        if (typeof (data.forceclick) === 'string') {
                            setTimeout(function () {
                                $(data.forceclick).click();
                            }, 250);
                        } else if (typeof (data.forceclick) === 'object') {
                            $.each(data.forceclick, function (key, value) {
                                setTimeout(function () {
                                    $(value).click();
                                }, 250);
                            });
                        }
                    }

                    if (data.divcontent) {
                        if (typeof (data.divcontent) === 'string') {
                            $(data.divcontent[0]).html(data.divcontent[1]);
                        } else if (typeof (data.divcontent) === 'object') {
                            $.each(data.divcontent, function (key, value) {
                                $(key).html(value);
                            });
                        }
                    }

                    if (data.refresh) {
                        window.setTimeout(function () {
                            location.reload();
                        }, 2200);
                    }
                    if (data.redirect) {
                        if (data.redirect_timer != undefined) {
                            var TIMER = data.redirect_timer;
                        } else {
                            var TIMER = 1500;
                        }
                        window.setTimeout(function () {
                            window.location.replace(data.redirect);
                        }, TIMER);
                    }
                    if (data.callback) {
                        if (typeof (data.callback) === 'string') {
                            var myobj = JSON.parse(JSON.stringify(data.callback + '()'));
                            myobj.callPluggins = new Function(myobj)();
                        } else if (typeof (data.callback) === 'object') {
                            $.each(data.callback, function (Key, Value) {
                                var myobj = JSON.parse(JSON.stringify(Value + '()'));
                                myobj.callPluggin = new Function(myobj)();
                            });
                        }
                    }
                }, 'json');
            }
        });
    });

    //############## MODAL NATIVA :: Ação para abrir modal
    $('body, html').on('click', '.j_ajaxModal', function () {
        var Button = $(this);
        var Specific = Button.attr('data-custom');

        if (Specific != undefined) {
            var Id = Button.attr('data-callback_id');
            var CallBack = Button.attr('data-callback');
            var Action = Button.attr('data-callback_action');
        } else {
            var Id = Button.attr('callback_id');
            var CallBack = Button.attr('callback');
            var Action = Button.attr('callback_action');
        }

        var Src = CallBack;
        Data = (Id != undefined) ? {callback: CallBack, callback_action: Action, id: Id} : {callback: CallBack, callback_action: Action};

        // LOADING
        var Load = new KTDialog({'type': 'loader', 'placement': 'top center', 'message': 'Carregando ...'});
        Button.addClass('kt-spinner kt-spinner--center kt-spinner--sm kt-spinner--light kt-opacity-4').attr('disabled', true);
        Load.show();

        $.post('_ajax/' + Src + '.ajax.php', Data, function (data) {
            Button.removeClass('kt-spinner kt-spinner--center kt-spinner--sm kt-spinner--light kt-opacity-4').attr('disabled', false);
            Load.hide();

            if (data.modal) {
                ajaxModal(Button, data.modal.icon, data.modal.title, data.modal.content, data.modal.footer, data.modal.size, data.modal.callback);
            }
            if (data.divcontent) {
                if (typeof (data.divcontent) === 'string') {
                    $(data.divcontent[0]).html(data.divcontent[1]);
                } else if (typeof (data.divcontent) === 'object') {
                    $.each(data.divcontent, function (key, value) {
                        $(key).html(value);
                    });
                }
            }
            if (data.trigger) {
                Trigger(data.trigger);
            }
        }, 'json');
        return false;
    });

    //############## MODAL NATIVA :: Botao para enviar requisição
    $('html, body').on('click', '.j_sendFormModal', function (e) {
        var Button = $(this),
                Form = Button.closest('.ajax_modal').find('form:first'),
                Load = Form.find('.form_load');

        Load.fadeIn(function () {
            ajaxModalLoad(Form, Button);
            Form.submit();
        });

        e.preventDefault();
        e.stopPropagation();
        return false;
    });

});


function Trigger(Data) {
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

    toastr.info(Data);/*
     if (Data.type == 'error') {
     toastr.error(Data.message);
     } else if (Data.type == 'info') {
     toastr.info(Data.message);
     } else if (Data.type == 'warning') {
     toastr.warning(Data.message);
     } else {
     toastr.success(Data.message);
     }*/
}

function miniTrigger(Data) {
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

    toastr.info(Data);/*
     if (Data.type == 'error') {
     toastr.error(Data.message);
     } else if (Data.type == 'info') {
     toastr.info(Data.message);
     } else if (Data.type == 'warning') {
     toastr.warning(Data.message);
     } else {
     toastr.success(Data.message);
     }*/
}

function ajaxModal(Button, Icon, Title, Content, Footer, Size = null, Call = null) {
    if ($('.ajax_modal:not(.nodom)').length) {
        $('.ajax_modal:not(.nodom)').remove();
    }

    // SET MODAL SIZE
    if (Size) {
        if (Size === 'small') {
            Size = 'modal-sm';
        } else if (Size === 'medium') {
            Size = '" style="width:576px;max-width:576px;';
        } else if (Size === 'large') {
            Size = 'modal-lg';
        }
    } else {
        Size = null;
    }

    $("body").append(''
            + '<div class="modal fade ajax_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">'
            + '<div class="modal-dialog modal-dialog-centered ' + Size + '" role="document">'
            + '<div class="modal-content">'
            + '<div class="modal-header">'
            + '<h5 class="modal-title ajax_modal_title">{TITLE}</h5>'
            + '<button type="button" class="close" data-dismiss="modal" aria-label="Fechar"></button>'
            + '</div>'
            + '<div class="modal-body ajax_modal_content">{CONTENT}</div>'
            + '<div class="modal-footer ajax_modal_footer">{FOOTER}'
            // EXAMPLE FOOTER
            // + '<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>'
            // + '<button type="button" class="btn btn-primary">Save changes</button>'
            + '</div>'
            + '</div>'
            + '</div>'
            + '</div>');

    var Modal = $('.ajax_modal:not(.nodom)');

    Modal.find('.ajax_modal_title').html(Title);
    if (Icon) {
        Modal.find('.ajax_modal_title').prepend(Icon + ' &nbsp; ');
    }
    Modal.find('.ajax_modal_content').html(Content);
    Modal.find('.ajax_modal_footer').html(Footer);

    // SHOW MODAL
    Modal.modal('show');

    //PLUGGINS CUSTOMIZADOS    
    if (Call) {
        if (typeof (Call) === 'string') {
            var myobj = JSON.parse(JSON.stringify(Call + '()'));
            myobj.callPluggins = new Function(myobj)();
        } else if (typeof (Call) === 'object') {
            $.each(Call, function (Key, Value) {
                var myobj = JSON.parse(JSON.stringify(Value + '()'));
                myobj.callPluggin = new Function(myobj)();
            });
        }
}
}

function ajaxModalLoad(Form, Button) {
    // Clona load do formulario e exclui quando o original some
    // Form.closest('.ajax_modal_box').find('.ajax_modal_footer').find('.form_load').remove();
    var LoadObject = Form.find('.form_load');
    var Load = Object.values(LoadObject)[0];
    var Observer = new MutationObserver(handleMutationObserver);
    var Config = {childList: true, attributes: true};
    //add
    Button.addClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', true);

    function handleMutationObserver(mutations) {
        mutations.forEach(function (mutation) {
            if (
                    mutation.target.style.cssText.match(/display: none/) ||
                    mutation.target.style.cssText.match(/display:none/)) {
                //remove
                Button.removeClass('kt-spinner kt-spinner--right kt-spinner--sm kt-spinner--light').attr('disabled', false);
            }
        });
    }
    Observer.observe(Load, Config);
}

function plugginDateTimePicker() {
    $('.datetimepicker').datetimepicker({
        todayHighlight: true,
        autoclose: true,
        pickerPosition: 'bottom-right',
        format: 'dd/mm/yyyy hh:ii'
    });
}

function plugginDatePicker() {
    $('.datepicker').datepicker({
        todayHighlight: true,
        autoclose: true,
        orientation: 'bottom right',
        format: 'dd/mm/yyyy'
    });
}

function plugginTooltips() {
    $('*[data-toggle="tooltip"]').tooltip();
}

function plugginMask() {
    $(".formHour").inputmask("99:99", {
        "placeholder": "**:**",
    });
    $(".formDate").inputmask("99/99/9999", {
        "placeholder": "**/**/****",
    });
    $(".formCpf").inputmask("999.999.999-99", {
        "placeholder": "***.***.***-**",
    });
    $(".formPhone").inputmask({
        mask: ["(99) 9999-9999", "(99) 99999-9999"]
    });
    $(".formCnpj").inputmask("99.999.999/9999-99", {
        "placeholder": "**.***.***/0001-**",
    });
    $(".formCep").inputmask("99.999-999", {
        "placeholder": "**.***-***",
    });
}
function plugginMaskMoney() {
    $(".formMoney").maskMoney({
        prefix: 'R$ ',
        allowNegative: true,
        thousands: '.',
        decimal: ',',
        affixesStay: false
    });
}
function plugginMaskMoney2() {
    $(".formMoney2").maskMoney({
        prefix: '% ',
        allowNegative: true,
        thousands: '.',
        decimal: ',',
        affixesStay: false
    });
}

function plugginTiny() {
    tinymce.init({
        selector: "textarea.work_mce",
        language: 'pt_BR',
        menubar: false,
        height: 100,
        toolbar: ['styleselect fontselect fontsizeselect',
            'undo redo | cut copy paste | bold italic | link image | alignleft aligncenter alignright alignjustify',
            'bullist numlist | outdent indent | blockquote subscript superscript | advlist | autolink | lists charmap | print preview |  code'],
        plugins: 'advlist autolink link image lists charmap print preview code',
        style_formats: [
            {title: 'Normal', block: 'p'},
            {title: 'Titulo 3', block: 'h3'},
            {title: 'Titulo 4', block: 'h4'},
            {title: 'Titulo 5', block: 'h5'},
            {title: 'Código', block: 'pre', classes: 'brush: php;'}
        ],
        link_class_list: [
            {title: 'None', value: ''},
            {title: 'Blue CTA', value: 'btn btn_cta_blue'},
            {title: 'Green CTA', value: 'btn btn_cta_green'},
            {title: 'Yellow CTA', value: 'btn btn_cta_yellow'},
            {title: 'Red CTA', value: 'btn btn_cta_red'}
        ],
        link_title: false,
        target_list: false,
        theme_advanced_blockformats: "h1,h2,h3,h4,h5,p,pre",
        media_dimensions: false,
        media_poster: false,
        media_alt_source: false,
        media_embed: false,
        extended_valid_elements: "a[href|target=_blank|rel|class]",
        imagemanager_insert_template: '<img src="{$url}" title="{$title}" alt="{$title}" />',
        image_dimensions: false,
        relative_urls: false,
        remove_script_host: false,
        paste_as_text: true
    });
}

function plugginTinyBasic() {
    tinymce.init({
        selector: "textarea.work_mce_basic",
        language: 'pt_BR',
        height: 100,
        toolbar: false,
        statusbar: false
    });
}

function plugginSelect2() {
    $('.customSelect').select2({
        placeholder: "Selecione uma opção",
        allowClear: true
    });
}
