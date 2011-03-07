/**
 * Worklist
 * Copyright (c) 2011 LoveMachine, LLc.
 * All rights reserved.
 */

var WReview = {
    initList : function() {
        $('.reviewAddLink,.reviewEditLink').click(function(){
            WReview.displayInPopup(userInfo.user_id,userInfo.nickName);
        });
        if ($(".myReview").length > 0) {
            $(".reviewAddLink").hide();
            $(".reviewEditLink").show();
            $(".reviewsList").prepend($(".myReview"));
            //$(".myReview").get(1).remove();
        }
    },
    init : function(opt){
        if (opt.fAfter) opt.fAfter();
    },
    block : function(bBlock,sMess,oElement){
        if (!oElement) {
            oElement = $("#settingsDialog").parent();
        }
        if (bBlock) {
            oElement.block({ 
                message: sMess,
                css: {
                    width:'50%'
                }
            });
            setTimeout(function(){oElement.unblock();}, 30000); 
        } else {
             oElement.unblock();
        }
    },

    saveReview: function(fAfter) {
        WReview.block(true,"Saving ...",$("#reviewDialog").parent());
        $.ajax({
            type: 'POST',
            url: 'review.php',
            data: { 
                action:'saveReview',
                userReview: $("textarea.userReview").val(),
                reviewee_id: $("#reviewDialog").data("reviewee_id")
            },
            dataType: 'json',
            success: function(json) {
                WReview.block(false,"",$("#reviewDialog").parent());
                if ((json === null) || (json.succeeded !== true)  ) {
                    var message="Error returned. ";
                    if (json !== null) {
                        message = message + json.message;
                    }
                    alert(message);
                    return;
                }
                if (json.params && json.params.myReview) {
                    var review = json.params.myReview[0],
                        sReview;
                    if (review) {
                        sReview = '<div class="listReviewElement myReview" title="My review">' +
                            "<div class='feeRange' title='Number of jobs the reviewer has worked on'>" +
                                review['feeRange'] + "</div><div class='reviewText'>" + review['review']+ "</div>" +
                            '</div>' ;
                        $(".reviewsList .noReview").remove();
                        $(".reviewsList").prepend(sReview);
                        $(".reviewAddLink").hide();
                        $(".reviewEditLink").show();
                    }
                } else {
                    if ($("textarea.userReview").val() != "") {
                        $(".myReview .reviewText").html($("textarea.userReview").val());
                    } else {
                        $(".myReview").remove();
                        $(".reviewAddLink").show();
                        $(".reviewEditLink").hide();
                        if ($(".reviewsList .listReviewElement").length == 0) {
                            $(".reviewsList").html("<div class='noReview'>No Review available for this user.</div>");
                        }
                    }
                }
                $("#infoMessageReviews").append("<div class='infoMessageSaveReviews'>"+json.message+"</div>");
                $.blockUI({ 
                    message: $('#infoMessageReviews'), 
                    fadeIn: 700, 
                    fadeOut: 700, 
                    timeout: 3000, 
                    showOverlay: false, 
                    centerY: true, 
                    css: { 
                        width: '350px', 
                        left: '50%', 
                        border: 'none', 
                        padding: '5px', 
                        backgroundColor: '#000', 
                        '-webkit-border-radius': '10px', 
                        '-moz-border-radius': '10px', 
                        opacity: .6, 
                        color: '#fff' ,
                        'z-index': 4000
                    } ,
                    onUnblock: function() {
                        $(".infoMessageSaveReviews").remove();
                    }
                }); 
                if (fAfter) {
                    fAfter(true);
                }
            }
                                     
        });
    },
    clickSaveButton: function(fAfter) {
        WReview.saveReview(function(saved){
            if (saved) {
                $("#reviewDialog").dialog("close");
                if (fAfter) {
                    fAfter();
                }
            }
        });
    },

    displayInPopup: function(reviewee_id,nickName){
        if ($("#reviewDialog").length == 0) {
            $("<div id='reviewDialog' ><div id='infoMessageReviews'></div><div class='content'></div></div>").appendTo("body");
            $("#reviewDialog").data("reviewee_id",reviewee_id);
            $("#reviewDialog").data("nickName",nickName);
            $("#reviewDialog").dialog({
                buttons: {
                    "Save": function() { 
                        WReview.clickSaveButton();
                        return;
                    }
                },            
                modal:true,
                title: "Edit the review of "+nickName,
                autoOpen:false,
                width:650,
                height:350,
                position: ['top'],
                open: function() {
                    var oThis=this;
                    $("#reviewDialog .content").load("review.php",{
                        action:"getView",
                        reviewee_id: $("#reviewDialog").data("reviewee_id")
                        },function(response, status, xhr){    
                        if (status == "error") {
                            var msg = "Sorry but there was an error: ";
                                $("#error").html(msg + xhr.status + " " + xhr.statusText);
                        } else {
                            WReview.init({
                                fAfter: function() {
                                    $("#reviewSave").click(function(){
                                        WReview.clickSaveButton();
                                    });
                                }
                            });                 
                        }
                    });
                },
                close: function() {
                    $("#reviewDialog .content").html("");
                }
            });
            $("#reviewDialog").dialog("open");
        } else {
            $("#reviewDialog").data("nickName",nickName);
            $("#reviewDialog").data("reviewee_id",reviewee_id);
            $("#reviewDialog .content").html("");
            $("#reviewDialog").dialog("open");
        }    
    }
};

