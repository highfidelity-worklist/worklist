
$(function() {

     Workitem.init();

});

var Workitem = {

    sandbox_url: '',
    
    init: function() {
        $("#view-sandbox").click(function() {
            Workitem.openDiffPopup({
                sandbox_url: Workitem.sandbox_url,
                workitem_id: workitem_id
            });
        });
        
        $('#quick-status select').change(function() {
            var value = $(this).val();
            $("#loading").show();
            $.ajax({
                type: 'post',
                url: 'workitem.php',
                dataType: 'json',
                data: {
                    status_switch: '1',
                    value: value,
                    workitem_id: workitem_id
                },
                success: function(json) {
                    $("#loading").hide();
                    $("#quick-status .info-data").html(value)
                    if (json.succeeded == false) {
                        alert (json.message);
                    }
                }
            });
        });        
    },
    
    openDiffPopup: function(options) {
        if ($("#diffUrlDialog").length == 0) {
            $("<div id='diffUrlDialog' class='popup-body'><div class='content'>Loading ...</div></div>").appendTo("body"); 
            $("#diffUrlDialog").data("options", options);
            $('#diffUrlDialog').dialog({
                title: 'View Sandbox Diff',
                autoOpen: false,
                closeOnEscape: true,
                resizable: false,
                width: '420px',
                show: 'drop',
                hide: 'drop',
                buttons: [
                    {
                        text: 'Ok',
                        click: function() {
                            if ($("#diffUrlDialog #diff-sandbox-url").length > 0) {
                                $("#diffUrlDialog").data("options", {
                                    sandbox_url: $("#diffUrlDialog #diff-sandbox-url").val(),
                                    workitem_id: $("#diffUrlDialog").data("options").workitem_id
                                });
                                Workitem.fillDiffPopup();
                            } else {
                                $(this).dialog("close");
                            }                           
                        }
                    }
                ],
                open: function() {
                    Workitem.fillDiffPopup();
                },
                close: function() {
                    $("#diffUrlDialog .content").html("");
                }
            });
        } else {
            $("#diffUrlDialog").data("options", options);
            $("#diffUrlDialog .content").html("");                       
        }
        $("#diffUrlDialog").dialog("open");
    },
    
    fillDiffPopup: function() {
        var options = $("#diffUrlDialog").data("options");
        $("#diffUrlDialog .content").load("sandbox.php #urlContent", {
            action: 'getDiffUrlView',
            sandbox_url: options.sandbox_url,
            workitem_id: options.workitem_id
        });        
    }
    
    
}