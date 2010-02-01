<?php ob_start(); 
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");

$con=mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
mysql_select_db(DB_NAME,$con);

$cur_letter = isset($_POST['letter']) ? $_POST['letter'] : "all";
$cur_page = isset($_POST['page']) ? intval($_POST['page']) : 1;

if(isset($_POST['save_roles']) && $_SESSION['is_runner'] == 1){ //only runners can change other user's roles info
$is_runner = isset($_POST['isrunner']) ? 1 : 0;
$user_id = intval($_POST['userid']);
mysql_unbuffered_query("UPDATE `users` SET `is_runner` =  '$is_runner' WHERE `id` =".$user_id);
}

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/teamnav.css" rel="stylesheet" type="text/css">
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>

<script type="text/javascript">
  var current_letter = '<?php echo $cur_letter; ?>';
  var logged_id = <?php echo $_SESSION['userid']; ?>;
  var runner =  <?php echo $_SESSION['is_runner']; ?>;
  $(document).ready(function(){

  $("#outside").click(function() { //closing userbox on clicking outside of it
    $('#popup-user-info').dialog('close');
  } );

    fillUserlist(<?php echo $cur_page; ?>);

  $('.ln-letters a').click(function(){
    var classes = $(this).attr('class').split(' ');
    current_letter = classes[0];
    fillUserlist(1);
    return false;
  });

  $('#popup-user-info').dialog({ autoOpen: false});

  });

  function fillUserlist(npage){
    $.ajax({
	type: "POST",
	url: 'getuserlist.php',
	data: 'letter=' + current_letter + '&page=' + npage,
	dataType: 'json',
	success: function(json) {

	    $('.ln-letters a').removeClass('ln-selected');
	    $('.ln-letters a.' + current_letter).addClass('ln-selected');
	    //to be on the same page and letter after reloading
	    $('#popup-user-info #hid_letter').val(current_letter);
	    $('#popup-user-info #hid_page').val(npage);

            page = json[0][1]|0;
            var cPages = json[0][2]|0;

            $('.row-userlist-live').remove();

	    if(json.length > 1){
	      $('.table-hdng').show();
	    }else{
	      $('.table-hdng').hide();
	    }

	    var odd = true;
            for (var i = 1; i < json.length; i++) {
		AppendUserRow(json[i], odd);
		odd = !odd;
            }

            $('tr.row-userlist-live').click(function(){
              var match = $(this).attr('class').match(/useritem-\d+/);
              var userid = match[0].substr(9);
		populateUserPopup(userid);
		$('#popup-user-info').dialog('open');
                return false;
            });



	    if(cPages > 1){ //showing pagination only if we have more than one page
	      $('.ln-pages').html(outputPagination(page,cPages));

	      $('.ln-pages a').click(function(){
		page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
		fillUserlist(page);
		return false;
	      });

	    }else{
	      $('.ln-pages').html('');
	    }
	},
	error: function(xhdr, status, err) {
	}
    });
  }
  function outputPagination(page, cPages)
  {
      var pagination = '';
      if (page > 1) { 
	  pagination += '<a href="#?page=' + (page-1) + '">Prev</a>'; 
      } 
      for (var i = 1; i <= cPages; i++) { 
	  var sel = '';
	  if (i == page) { 
	    if(page == cPages){
	      sel = ' class="ln-selected ln-last"';
	    }else{
	      sel = ' class="ln-selected"';
	    }
	  } 
	      pagination += '<a href="#?page=' + i + '"' + sel + '>' + i + '</a>';  
      }
      if (page < cPages) { 
	  pagination += '<a href="#?page=' + (page+1) + '" class = "ln-last">Next</a>'; 
      } 
      return pagination;
  }

  function populateUserPopup(userid){
    $('#popup-user-info  #popup-form input[type="submit"]').remove();
    $('#roles').show();
    $.ajax({
      type: "POST",
      url: 'getuseritem.php',
      data: 'item='+userid,
      dataType: 'json',
      success: function(json) {
	  $('#popup-user-info #userid').val(json[0]);
	  $('#popup-user-info #info-nickname').text(json[1]);
	  $('#popup-user-info #info-email').text(json[2]);
	  $('#popup-user-info #info-about').text(json[3]);
	  $('#popup-user-info #info-contactway').text(json[4]);
	  $('#popup-user-info #info-payway').text(json[5]);
	  $('#popup-user-info #info-skills').text(json[6]);
	  $('#popup-user-info #info-timezone').text(json[7]);
	  $('#popup-user-info #info-joined').text(json[8]);
	  if(json[9] == "1"){
	    $('#popup-user-info #info-isrunner').attr('checked', 'checked');
	  }else{
	    $('#popup-user-info #info-isrunner').attr('checked', '');
	  }
	  
	  if(runner == 1){
	    $('#popup-user-info #info-isrunner').attr('disabled', ''); 
	  }else{
	    $('#popup-user-info #info-isrunner').attr('disabled', 'disabled'); 
	  }
	  if(json[0] == logged_id){
	    //adding "Edit" button
	    $('#popup-user-info #popup-form').append('<input type="submit" name="edit" value="Edit">');
	    $('#roles').hide();
	  }
      },
      error: function(xhdr, status, err) {
      }
  });
  }

    function AppendUserRow(json, odd)
    {
        var row;
        row = '<tr class="row-userlist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += ' useritem-' + json[0] + '">';
        row += '<td>' + json[1] + '</td>';
	var is_runner = '';
	if(json[2] == "1"){
	    is_runner = 'Yes';
	}else{
	    is_runner = 'No';
	}
        row += '<td >' + is_runner + '</td>';
       $('.table-userlist tbody').append(row);
    }

</script>


<title>Worklist | Team Members</title>

</head>

<body>

    <!-- Popup for user info-->
    <div id="popup-user-info" class="popup-body" title = "User Info">
            <p class = "info-label">Nickname<br />
            <span id="info-nickname"></span>
            </p>

            <p class = "info-label">Email<br />
            <span id="info-email"></span>
            </p>

            <p class = "info-label">About<br />
            <span id="info-about"></span>
            </p>

            <p class = "info-label">Preffered contact way<br />
            <span id="info-contactway"></span>
            </p>

            <p class = "info-label">Preffered way of payment<br />
            <span id="info-payway"></span>
            </p>

            <p class = "info-label">Strongest skills<br />
            <span id="info-skills"></span>
            </p>

            <p class = "info-label">Tomezone<br />
            <span id="info-timezone"></span>
            </p>

            <p class = "info-label">Joined<br />
            <span id="info-joined"></span>
            </p>
    
	    <form id = "roles" method="post" >
            <p class = "info-label">Roles<br />
            <input type="checkbox" name="isrunner" value="isrunner" id = "info-isrunner" /><span>Runner</span>
            </p>
	    <input type="hidden" name="userid" id="userid" value="">
	    <input type="hidden" name="letter" id="hid_letter" value="">
	    <input type="hidden" name="page" id="hid_page" value="">
<?php 
  if($_SESSION['is_runner'] == 1) { //drawing "Save button"
    echo '
  <input type="submit" name="save_roles" value="Save Roles">
  ';
  }
?>
	    </form>

            <form id = "popup-form" action="settings.php" method="post">

            </form>
    </div><!-- end of popup-bid-info -->

<?php include("format.php"); ?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


            <h1>Team Members</h1>
<div class="ln-letters"><a href="#" class="all ln-selected">ALL</a><a href="#" class="_">0-9</a><a href="#" class="a">A</a><a href="#" class="b">B</a><a href="#" class="c">C</a><a href="#" class="d">D</a><a href="#" class="e">E</a><a href="#" class="f">F</a><a href="#" class="g">G</a><a href="#" class="h">H</a><a href="#" class="i">I</a><a href="#" class="j">J</a><a href="#" class="k">K</a><a href="#" class="l">L</a><a href="#" class="m">M</a><a href="#" class="n">N</a><a href="#" class="o">O</a><a href="#" class="p">P</a><a href="#" class="q">Q</a><a href="#" class="r">R</a><a href="#" class="s">S</a><a href="#" class="t">T</a><a href="#" class="u">U</a><a href="#" class="v">V</a><a href="#" class="w">W</a><a href="#" class="x">X</a><a href="#" class="y">Y</a><a href="#" class="z ln-last">Z</a></div>
<br />
<div class="ln-pages"></div><br />
    <table class="table-userlist">
        <thead>
        <tr class="table-hdng">
            <td>Nickname</td>
            <td>Runner?</td>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
<br/>
<div class="ln-pages"></div>
<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
