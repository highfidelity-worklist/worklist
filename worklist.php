<?php 

//  Copyright (c) 2009, LoveMachine Inc.
//  All Rights Reserved. 
//  http://www.lovemachineinc.com

ob_start(); 

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include_once("functions.php");

$page=isset($_REQUEST["page"])?intval($_REQUEST["page"]):1; //Get the page number to show, set default to 1

//error_log(var_export($_POST,1));
if (isset($_POST['save'])) {
    $args = array('id', 'summary', 'value', 'contract', 'expense', 'status', 'notes');
    foreach ($args as $arg) {
        $$arg = mysql_real_escape_string($_POST[$arg]);
    }

    $creator_id = $_SESSION['userid'];

    $owner_id = 0;
    $owner = mysql_real_escape_string($_POST['owner']);
    $res = mysql_query("select id from ".USERS." where username='$owner' || nickname='$owner'");
    if ($res && ($row = mysql_fetch_assoc($res))) {
        $owner_id = $row['id'];
    }

    if (!empty($_POST['id'])) {
        $query = "update ".WORKLIST." set summary='$summary', owner_id='$owner_id', value='$value',".
            " contract='$contract', expense='$expense', status='$status', notes='$notes' where id='$id'";
    } else {
        $query = "insert into ".WORKLIST." ( summary, creator_id, owner_id, value, contract, expense, status, notes, created ) ".
            "values ( '$summary', '$creator_id', '$owner_id', '$value', '$contract', '$expense', '$status', '$notes', now() )";
    }

    $rt = mysql_query($query);
} else if (isset($_POST['delete']) && !empty($_POST['id'])) {
    mysql_query("delete from ".WORKLIST." where id='".intval($_POST['id'])."'");
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/worklist.js"></script>
<script type="text/javascript">
    var refresh = <?php echo AJAX_REFRESH ?> * 1000;
    var lastId;
    var page = <?php echo $page ?>;
    var topIsOdd = true;
    var timeoutId;
    var addedRows = 0;
    var workitem = 0;
    var workitems;

    function AppendPagination(page, cPages)
    {
        var pagination = '<tr bgcolor="#FFFFFF" class="row-worklist-live"><td colspan="4" style="text-align:center;">Pages : &nbsp;';
        if (page > 1) { 
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page-1) + '">Prev</a> &nbsp;'; 
        } 
        for (var i = 1; i <= cPages; i++) { 
            if (i == page) { 
                pagination += i + " &nbsp;"; 
            } else { 
                pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + i + '" >' + i + '</a> &nbsp;'; 
            } 
        }
        if (page < cPages) { 
            pagination += '<a href="<?php echo $_SERVER['PHP_SELF'] ?>?page=' + (page+1) + '">Next</a> &nbsp;'; 
        } 
        pagination += '</td></tr>';
        $('.table-worklist').append(pagination);
    }

    // json row fields: id, summary, status, owner nickname, owner username, delta
    function AppendRow(json, odd, prepend, moreJson, idx)
    {
        var pre = '', post = '';
        var row;

        row = '<tr class="row-worklist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += ' workitem-' + json[0] + '">';
        if (prepend) { pre = '<div class="slideDown" style="display:none">'; post = '</div>'; }
        row += '<td width="50%">' + pre + json[1] + post + '</td>';
        row += '<td width="15%">' + pre + json[2] + post + '</td>';
        if (json[3] != '') {
            row += '<td width="20%" class="toolparent">' + pre + json[3] + post + '<span class="tooltip">' + json[4] + '</span>' + '</td>';
        } else {
            row += '<td width="20%">' + pre + json[3] + post + '</td>';
        }
        row += '<td width="15%">' + pre + RelativeTime(json[5]) + post + '</td></tr>';

        if (prepend) {
            $(row).prependTo('.table-worklist tbody').find('td div.slideDown').fadeIn(500);
            setTimeout(function(){
                $(this).removeClass('slideDown');
                if (moreJson && idx-- > 1) {
                    topIsOdd = !topIsOdd;
                    AppendRow(moreJson[idx], topIsOdd, true, moreJson, idx);
                }
            }, 500);
        } else {
            $('.table-worklist tbody').append(row);
        }
    }

    function Change(obj, evt)
    {
        if(evt.type=="focus")
            obj.style.borderColor="#6A637C";
        else if(evt.type=="blur")
           obj.style.borderColor="#d0d0d0";
    }

    function GetWorklist(page, update) {
    $.ajax({
        type: "POST",
        url: 'getworklist.php',
        data: 'page='+page+'&filter='+$("#search-filter").val(),
        dataType: 'json',
        success: function(json) {
            if (json == 'expired') { window.location = "./index.php?expired=1"; return; }

            page = json[0][1]|0;
            var cPages = json[0][2]|0;

            $('.row-worklist-live').remove();
            workitems = json;
            if (!json[1]) return;

            /* When updating, find the last first element */
            for (var lastFirst = 1; update && page == 1 && lastId && lastId != json[lastFirst][0] && lastFirst < json.length; lastFirst++);
            lastFirst = Math.max(1, lastFirst - addedRows);
            addedRows = 0;

            /* Output the worklist rows. */
            var odd = topIsOdd;
            for (var i = lastFirst; i < json.length; i++) {
                AppendRow(json[i], odd);
                odd = !odd;
            }
            AppendPagination(page, cPages);

            if (update && lastFirst > 1) {
                /* Update the view by scrolling in the new entries from the top. */
                topIsOdd = !topIsOdd;
                AppendRow(json[lastFirst-1], topIsOdd, true, json, lastFirst-1);
            }
            lastId = json[1][0];

            ToolTip();
            $('.table-worklist a').click(function(e){
                page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
                if (timeoutId) clearTimeout(timeoutId);
                GetWorklist(page, false);
                e.stopPropagation();
                return false;
            });

            $('tr.row-worklist-live').click(function(){
                if (workitem > 0) $('.workitem-'+workitem).removeClass('workitem-selected');
                var match = $(this).attr('class').match(/workitem-\d+/);
                if (match) {
                    workitem = match[0].substr(9);
                    $('.workitem-'+workitem).addClass('workitem-selected');
                } else {
                    workitem = 0;
                }
                if (workitem != 0) {
                    $("#edit,#delete").attr('disabled', '');
                } else {
                    $("#edit,#delete").attr('disabled', 'disabled');
                }
                return false;
            });

            if (workitem > 0) {
                if (workitem <= json[1][0] && workitem >= json[json[0][0]][0]) {
                    $('.workitem-'+workitem).addClass('workitem-selected');
                } else {
                    workitem = 0;
                }
            }
        },
        error: function(xhdr, status, err) {
            $('.row-worklist-live').remove();
            $('.table-worklist').append('<tr class="row-worklist-live rowodd"><td colspan="4" align="center">Oops! We couldn\'t find any work items.  <a id="again" href="#">Please try again.</a></td></tr>');
            $('#again').click(function(){
                if (timeoutId) clearTimeout(timeoutId);
                GetWorklist(page, false);
                e.stopPropagation();
                return false;
            });
        }
    });

    timeoutId = setTimeout("GetWorklist("+page+", true)", refresh);
    }

    function ToolTip() {
        xOffset = 10;
        yOffset = 20;       
        var el_parent, el_child;
        $(".toolparent").hover(function(e){                                             
            if (el_child) el_child.appendTo(el_parent).hide();
            el_parent = $(this);
            el_child = el_parent.children(".tooltip")
                .appendTo("body")
                .css("top",(e.pageY - xOffset) + "px")
                .css("left",(e.pageX + yOffset) + "px")
                .fadeIn("fast");        
        },
        function(){
            if (el_child) el_child.appendTo(el_parent);
            $(".tooltip").hide();
            el_child = null;
        }); 
        $(".toolparent").mousemove(function(e){
            if (el_child) {
                el_child
                    .css("top",(e.pageY - xOffset) + "px")
                    .css("left",(e.pageX + yOffset) + "px");
            }
        });         
    }

    function PopulatePopup(item) {
        $.ajax({
            type: "POST",
            url: 'getworkitem.php',
            data: 'item='+item,
            dataType: 'json',
            success: function(json) {
                $('.popup-body form input[name="id"]').val(item);
                $('.popup-body form input[name="summary"]').val(json[0]);
                $('.popup-body form input[name="summary"]').val(json[0]);
                $('.popup-body form input[name="owner"]').val(json[1]);
                $('.popup-body form input[name="value"]').val(json[2]);
                $('.popup-body form input[name="contract"]').val(json[3]);
                $('.popup-body form input[name="expense"]').val(json[4]);
                $('.popup-body form input[name="contract"]').val(json[5]);
                $('.popup-body form select[name="status"] option[value="'+json[6]+'"]').attr('selected','selected');
                $('.popup-body form textarea[name="notes"]').val(json[7]);
            },
            error: function(xhdr, status, err) {
            }
        });
    }

    function ResetPopup() {
        $('.popup-body form input[type="text"]').val('');
        $('.popup-body form select option').attr('selected', '');
        $('.popup-body form textarea').val('');
    }

    function ShowPopup(popup, show) {
        if (show) {
            $('#popup-overlay').show();
            popup.css('left', ($('#popup-overlay').width()-popup.width())/2 + 'px');
            popup.show();
        } else {
            $('#popup-overlay').hide();
            $('.popup-wrap').hide();
        }
    }

    $(document).ready(function(){
        GetWorklist(<?php echo $page?>, false);    

        $("#owner").autocomplete('getusers.php', { cacheLength: 1, max: 8 } );
        $("#search-filter").change(function(){
            GetWorklist(page, false);
        });

        $('#add').click(function(){
            $('.popup-title').text('Add Worklist Item');
            ResetPopup();
            ShowPopup($('#popup-edit'), true);
        });
        $('#edit').click(function(){
            $('#popup-edit form input[name="id"]').val(workitem);
            $('.popup-title').text('Edit Worklist Item');
            ShowPopup($('#popup-edit'), true);
            PopulatePopup(workitem);
        });
        $('#delete').click(function(){
            var summary = '(No summary)';
            for (i = 1; i <= workitems[0][0]; i++) {
                if (workitems[i][0] == workitem) {
                    summary = workitems[i][1];
                    break;
                }
            }
            $('#popup-delete form input[name="id"]').val(workitem);
            $('.popup-delete-summary').text('"'+summary+'"');
            ShowPopup($('#popup-delete'), true);
        });

        $('#popup-overlay, .popup-close a').click(function(){
            ShowPopup(null, false);
        });
        $('.popup-body form input[type="submit"]').click(function(){
            var name = $(this).attr('name');
    
            if (name == "reset") {
                ResetPopup();
                return false;
            } else if (name == "cancel") {
                ShowPopup(null, false);
                return false;
            }
        });
    });
</script> 

<title>Worklist | Lend a Hand</title>

</head>

<body>

    <div id="popup-overlay"></div>
    <div id="popup-edit" class="popup-wrap">
        <div class="popup-titlebar">
            <span class="popup-title">Add Worklist Item</span>
            <span class="popup-close"><a href="#">X</a></span>
            <div class="clear"></div>
        </div>
        <div class="popup-body">
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="id" value="0" />

                <p><label>Summary<br />
                <input type="text" name="summary" class="text-field" size="48" />
                </label></p>

                <p><label>Owner<br />
                <input type="text" id="owner" name="owner" class="text-field" size="48" />
                </label></p>
    
                <p><label>Value<br />
                <input type="text" name="value" class="text-field" size="48" />
                </label></p>
    
                <p><label>Contract<br />
                <input type="text" name="contract" class="text-field" size="48" />
                </label></p>
    
                <p><label>Expense<br />
                <input type="text" name="expense" class="text-field" size="48" />
                </label></p>
    
                <p><label>Status<br />
                <select name="status">
                    <option value="WORKING">WORKING</option>
                    <option value="BIDDING">BIDDING</option>
                    <option value="SKIP">SKIP</option>
                    <option value="DONE">DONE</option>
                </select>
                </label></p>
    
                <p><label>Notes<br />
                <textarea name="notes" size="48" /></textarea>
                </label></p>
    
                <input type="submit" name="save" value="Save">
                <input type="submit" name="reset" value="Reset">
                <input type="submit" name="cancel" value="Cancel">
            </form>
        </div>
    </div>
    <div id="popup-delete" class="popup-wrap">
        <div class="popup-titlebar">
            <span class="popup-title">Delete Worklist Item</span>
            <span class="popup-close"><a href="#">X</a></span>
            <div class="clear"></div>
        </div>
        <div class="popup-body">
            <form name="popup-form" action="" method="post">
                <input type="hidden" name="id" value="" />

                <p class="popup-delete-summary"></p>
                <p>Are you sure you want to delete to this work item?</p>
    
                <input type="submit" name="delete" value="Yes">
                <input type="submit" name="cancel" value="No">
            </form>
        </div>
    </div>

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->

    <div id="buttons">
        <p>
            <input type="submit" id="add" name="add" value="Add">
            <input type="submit" id="edit" name="edit" value="Edit" disabled>
            <input type="submit" id="delete" name="delete" value="Delete" disabled>
        </p>
    </div>
            
    <div id="search-filter-wrap">
        <p>
            <select name="filter" id="search-filter">
                <option value="WORKING/BIDDING">WORKING/BIDDING</option>
                <option value="ALL">ALL</option>
                <option value="SKIP">SKIP</option>
                <option value="DONE">DONE</option>
            </select>
        </p>
    </div>

    <table width="100%" class="table-worklist">
        <thead>
        <tr class="table-hdng">
            <td>Summary</td>
            <td>Status</td>
            <td>Who</td>
            <td>Age</td>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
      
<?php include("footer.php"); ?>
