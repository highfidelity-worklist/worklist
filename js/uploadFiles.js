(function($) {

    var methods = {
        init: function(options) {
            if(typeof(options) == 'undefined') {
                options = {};
            }
            return this.each(function() {
                var $this = $(this), fdata = $this.data('fileUpload');
                // Activate the accordion
                $this.accordion({
                    clearStyle: true,
                    collapsible: true,
                    active: false 
                });
                
                if(!fdata) {
                    fdata = {};
                    fdata.user = (typeof(user_id) != 'undefined') ? user_id : '';
                    fdata.workitem = (typeof(workitem_id) != 'undefined') ? workitem_id : null;
                    fdata.projectid = (typeof(inProject) != 'undefined') ? inProject : null;
                    fdata.images = (typeof(options.images) != 'undefined') ? options.images : new Array();
                    fdata.documents = (typeof(options.documents) != 'undefined') ? options.documents : new Array();
                    fdata.trackfiles = typeof(options.tracker) != 'undefined' ? options.tracker : false;
                    $this.data('fileUpload', fdata);
                }

                // initiate the upload
                var uploadButton = $this.next('.fileUploadButton');

                new AjaxUpload(uploadButton, {
                    action: 'jsonserver.php',
                    name: 'file',
                    data: {
                        action: 'fileUpload',
                        userid: fdata.user,
                        projectid: fdata.projectid,
                        workitem: fdata.workitem,
                    },
                    autoSubmit: true,
                    responseType: 'json',
                    onSubmit: function(file, extension) {
                        var fdata = $this.data('fileUpload');
                        if ($("#upload-scan-file").length == 0) {
                            $('<div id="upload-scan-file"><div class="content"></div></div>').appendTo('body');
                        }
                        $('#upload-scan-file').dialog({
                            modal: true,
                            title: null,
                            autoOpen: true,
                            width: 300,
                            resizable : false,
                            open: function() {
                        	    $('#upload-scan-file .content').html('<p>Uploading attachment ...</p>');
                            }
                        });
                        
                        console.log(fdata, $this);
                        if(fdata.trackfiles) {
                            fdata.trackfiles.val(fdata.images.concat(fdata.documents).join(','));
                            $this.data('fileUpload', fdata);
                        }

                        $this.accordion('activate', false);
                        $('.uploadnotice').empty();
                        if (! (extension && /^(jpg|jpeg|gif|png|pdf|rtf|txt|csv|xls|xlsx|doc|docx|odt)$/i.test(extension))){
                            // extension is not allowed
                            var html = '<div style="padding: 0.7em; margin: 0.7em 0; width:285px;position:relative;" class="ui-state-error ui-corner-all">'+
                            '<div title="click here to close the warning message" onclick="$(this).parent().hide();" style="cursor:pointer;position:absolute;right:5px;top:4px;">x</div>' +
                            '<p style="margin: 0;"><span style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-alert"></span>' +
                            '<strong>Error:</strong> This filetype is not allowed. Please upload a pdf, jpg, jpeg, gif, png, rtf, txt, csv, xls, xlsx, doc, docx or odt file.</p>' +
                            '</div>';
                            $('.uploadnotice').append(html);
                            // cancel upload
                            return false;
                        }
                        this.disable();
                    },
                    onComplete: function(file, data) {
                        var fdata = $this.data('fileUpload');

                        this.enable();
                        if (data.success == true) {
                            $.ajax({
                                url: "jsonserver.php",
                                type: "POST",
                                data: "action=scanFile&fileid=" + data.fileid,
                                dataType: "json",
                                async: false,
                                beforeSend: function( ) {
                            	    $('#upload-scan-file .content').html('<p>Antivirus scanning attachment ...</p>');
                                },
                                success: function(json){
                                    if (json.success == true) {
                                        data.url = json.url;
                                        data.icon = json.icon;
                                    }
                                }
                            });
                            
                            $('#upload-scan-file').dialog("close");
                            
                            if(fdata.trackfiles && fdata.trackfiles.val == '') {
                                fdata.images = new Array();
                                fdata.documents = new Array();
                            }
                            if (data.filetype == 'image') {
                                var newFile = $('#uploadImage').parseTemplate(data);
                                $('.fileimagecontainer', $this).append(newFile);
                                fdata.images.push(data.fileid);
                                $('.imageCount', $this).empty().html(fdata.images.length);
                                $this.accordion('activate', 0);
                            } else {
                                var newFile = $('#uploadDocument').parseTemplate(data);
                                $('.filedocumentcontainer', $this).append(newFile);
                                fdata.documents.push(data.fileid);
                                $('.documentCount', $this).empty().html(fdata.documents.length);
                                $this.accordion('activate', 1);
                            }
                            if(fdata.trackfiles) {
                                fdata.trackfiles.val(fdata.images.concat(fdata.documents).join(','));
                            }
                            $this.data('fileUploads', fdata);
                            $this.fileUpload('editable');

                        } else {
                            alert(data.message);
                        }

                    }
                });

                // initial call
                $this.fileUpload('editable');
            });
        },//end init
        editable: function() {
            return this.each(function() {
                var $this = $(this),fdata = $this.data('fileUpload');
                if (fdata.user) {
                    $('.edittextarea').editable('jsonserver.php', { 
                        indicator: 'Saving ...',
                        tooltip: 'Click to add/edit note ...', 
                        placeholder: 'Click to add note',
                        type: 'text',
                        submit: 'OK',
                        submitdata: function(value, settings) {
                            return {
                                action: 'changeFileDescription',
                                fileid: this.id.replace('fileDesc_', ''),
                                userid: fdata.user
                            };
                        },
                        method: 'post'
                    });
                    $('.edittext').editable('jsonserver.php', { 
                        indicator: 'Saving ...',
                        tooltip: 'Click to change the title ...', 
                        placeholder: 'Add title',
                        type: 'text',
                        submit: 'OK',
                        submitdata: function(value, settings) {
                            return {
                                action: 'changeFileTitle',
                                fileid: this.id.replace('fileTitle_', ''),
                                userid: fdata.user
                            };
                        },
                        method: 'post'
                    });
                }
                $(".removeAttachment").unbind("click").click(function(){
                    var file_id = this.id.replace('fileRemoveAttachment_', ''),
                    oThis= this;

                    if (!confirm('Are you sure you want to remove attachment ' + $('#fileTitle_' + file_id).text() + '?')) {
                        return;
                    } 
                    $.ajax({
                        url: "jsonserver.php",
                        type: "POST",
                        data: "action=fileRemove&fileid=" + file_id +"&userid="+fdata.user,
                        dataType: "json",
                        success: function(json){
                            if (json.success == true) {
                                var fileDesc = $(oThis).parents(".filesDescription");
                                header = fileDesc.parent().prev("h3");
                                var iPos = -1;
                                if ($(".documentCount", header).length != 0) {                         
                                    for (var i=0; i < fdata.documents.length; i++) {
                                        if (fdata.documents[i] == file_id) {
                                            iPos = i;
                                            break;
                                        }
                                    }

                                    if (iPos != -1) {
                                        fdata.documents.splice(iPos,1);
                                        $(".documentCount", header).text(parseInt($(".documentCount", header).text()) - 1);
                                    }
                                } else {
                                    for (var i=0; i < fdata.images.length; i++) {
                                        if (fdata.images[i] == file_id) {
                                            iPos = i;
                                            break;
                                        }
                                    }
                                    if (iPos != -1) {
                                        fdata.images.splice(iPos,1);
                                        $(".imageCount", header).text(parseInt($(".imageCount", header).text()) - 1);
                                    }
                                }
                                $this.data('fileUploads', fdata);
                                fileDesc.prev().remove();
                                fileDesc.remove();
                            } else {
                                alert(json.message);
                            }
                        }
                    });
                });

            });
        },//end editable
    }

    $.fn.fileUpload = function(method) {
        // Method calling logic
        if (methods[method]) {
            return methods[ method ].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || ! method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' +  method + ' does not exist on jQuery.fileUpload');
        }
    };

})(jQuery);
