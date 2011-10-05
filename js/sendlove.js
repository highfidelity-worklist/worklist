//  Copyright (c) 2009, LoveMachine Inc.  
//  All Rights Reserved.  
//  http://www.lovemachineinc.com
var smsCountry = '';
var smsProvider = '';
var is_chrome = navigator.userAgent.toLowerCase().indexOf('chrome') > -1;

function smsAddressVisibility(e)
{
    if ( $("#country option:selected").val() != '' ) {
    if( $("#int_code option:selected").val() == '1'){
        $("#smsaddr").hide();
        $("#sms-other p").hide();
        $("#sms-other").hide();
    } else {
        $("#sms-other").show();
        $("#sms-other p").show();
        $("#smsaddr").show();    
    }
    }
}

function smsRefreshProvider(init)
{
var pos=4;
    if (!init) $("#phone_edit").val('1');
    if (smsCountry != $("#country").val()) {
        smsCountry = $("#country").val();
        if (smsCountry == '--') {
            $("#sms-provider").hide();
            $("#sms-other").hide();
        } else {
            var country = $("#country option:selected").text();
            var len = country.length;
        $("#int-code option").each(function(){
                if ($(this).text().trim().substr(0, len) == country) {
                    $(this).prop('selected', true);
                    return false;
                }
            });

        smsProvider = $("#stored-provider").val();
            var el = $("#provider");
            el.empty();
            $("#sms-provider").show();
            $.ajax({
                type: "POST",
                url: "getsms.php",
                data: "c="+smsCountry,
                dataType: "json",
                success: function(json) {
                    if (!json) {
                        el.append('<option value="--">Another wireless provider</option>');
                        $("#sms-other").show();
                        return;
                    }
                    smsProviderList = new Array();
                    var selectedFound=false;
                    for (var i = 0; i < json.length; i++) {
                        if (smsProvider && smsProvider == json[i]) {
                            el.append('<option value="'+json[i]+'" selected="selected">'+json[i]+'</option>');
                            selectedFound=true;
                        } else {
                            el.append('<option value="'+json[i]+'">'+json[i]+'</option>');
                        }
                    }
                    if (smsProvider && smsProvider[0] == '+' || selectedFound==false) {
                        el.append('<option value="--" selected="selected">Another wireless provider</option>');
                        $("#sms-other").show();
                    } else {
                        el.append('<option value="--">Another wireless provider</option>');
                    }
                }, 
                error: function(xhdr, status, err) {
                    el.append('<option value="--">Another wireless provider</option>');
                    $("#sms-other").show();
                }
            });
        }
    }
    if ($("#country").val() == '--' || $("#provider").val() == '--') {
        $("#sms-other").show();
    }

}

function smsUpdatePhone(filter)
{
    $("#phone_edit").val('1');
    var phone = $("#phone").val();
    if (filter) {
        $("#phone").val(phone.replace(/\D/g,''));
    }
}

/*
 * function sets into a cookie the time zone offset
 */
function get_timezone()
{
    var d = new Date()
    var value = -d.getTimezoneOffset()/60;        //gets the time zone
    //changes the value format to the used one
    if (value > 9) {
        value = '+' + value + '00';
    } else if (value > -1) {
        value = '+0' + value + '00';
    } else if (value > -10) {
        value = '-0' + Math.abs(value) + '00';
    } else {
        value += '00';
    }
    
    return value;
}

$(document).ready(function(){
    $("#phone").blur(function() { smsUpdatePhone(true); });
    $("#phone").keypress(function() { smsUpdatePhone(false); });
    $("#country, #provider, #smsaddr").change(function() { smsRefreshProvider(); smsAddressVisibility(); });          
    if( is_chrome ) {
        $("#int_code").change(function() { smsAddressVisibility(); });
    } else {    
        $('#int_code').bind('click',function(event) {
            event.preventDefault(); 
            smsAddressVisibility(event); 
        });
    }
    smsRefreshProvider(true);
    
    $('option[value = "' + get_timezone() + '"]').attr('selected','selected');
});
