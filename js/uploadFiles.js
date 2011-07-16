(function($) {
        var images = (typeof(imageArray) != 'undefined') ? imageArray : new Array();
        var documents = (typeof(documentsArray) != 'undefined') ? documentsArray : new Array();
        var user = (typeof(user_id) != 'undefined') ? user_id : '';
        var workitem = (typeof(workitem_id) != 'undefined') ? workitem_id : null;
        var projectid = (typeof(inProject) != 'undefined') ? inProject : null;
        // Activate the accordion
        $("#accordion").accordion({
            clearStyle: true,
            collapsible: true,
            active: false 
        });
        
        // initiate the upload
        new AjaxUpload('fileUploadButton', {
            action: 'jsonserver.php',
            name: 'file',
            data: {
                action: 'fileUpload',
                userid: user,
                projectid: projectid,
                workitem: workitem
            },
            autoSubmit: true,
            responseType: 'json',
            onSubmit: function(file, extension) {
                $('#accordion').accordion('activate', false);
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
                this.enable();
                if (data.success == true) {
                    if ($('input[name=files]').val() == '') {
                        images = new Array();
                        documents = new Array();
                    }
                    if (data.filetype == 'image') {
                        var newFile = $('#uploadImage').parseTemplate(data);
                        $('#fileimagecontainer').append(newFile);
                        images.push(data.fileid);
                        $('#imageCount').empty().html(images.length);
                        $('#accordion').accordion('activate', 0);
                    } else {
                        var newFile = $('#uploadDocument').parseTemplate(data);
                        $('#filedocumentcontainer').append(newFile);
                        documents.push(data.fileid);
                        $('#documentCount').empty().html(documents.length);
                        $('#accordion').accordion('activate', 1);
                    }
                    $('input[name=files]').val(images.concat(documents).join(','));
                    editable();

                } else {
                    alert(data.message);
                }
            }
        });
        
        
        function editable() {
            if (user) {
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
                            userid: user
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
                            userid: user
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
                    data: "action=fileRemove&fileid=" + file_id +"&userid="+user,
                    dataType: "json",
                    success: function(json){
                    if (json.success == true) {
                            var fileDesc = $(oThis).parents(".filesDescription");
                            header = fileDesc.parent().prev("h3");
                            var iPos = -1;
                            if ($("#documentCount",header).length != 0) {                         
                                for (var i=0; i < documents.length; i++) {
                                    if (documents[i] == file_id) {
                                        iPos = i;
                                        break;
                                    }
                                }
                                
                                if (iPos != -1) {
                                    documents.splice(iPos,1);
                                    $("#documentCount",header).text(parseInt($("#documentCount",header).text()) - 1);
                                }
                            } else {
                                for (var i=0; i < images.length; i++) {
                                    if (images[i] == file_id) {
                                        iPos = i;
                                        break;
                                    }
                                }
                                if (iPos != -1) {
                                    images.splice(iPos,1);
                                    $("#imageCount",header).text(parseInt($("#imageCount",header).text()) - 1);
                                }
                             }
                            fileDesc.prev().remove();
                            fileDesc.remove();
                        } else {
                            alert(json.message);
                        }
                    }
                });
            });

        }

        // initial call
        editable();
})(jQuery);
