<?php ob_start(); 
//  vim:ts=4:et
//
//  Copyright (c) 2010, LoveMachine Inc.
//  All Rights Reserved.
//  http://www.lovemachineinc.com
//

include("config.php");
include("class.session_handler.php");
include("check_session.php");
include("functions.php");

$con=mysql_connect( DB_SERVER,DB_USER,DB_PASSWORD );
mysql_select_db( DB_NAME,$con );

$cur_letter = isset( $_POST['letter'] ) ? $_POST['letter'] : "all";
$cur_page = isset( $_POST['page'] ) ? intval($_POST['page'] ) : 1;

$sfilter = !empty( $_POST['sfilter'] ) ? $_POST['sfilter'] : 'PAID';

/*********************************** HTML layout begins here  *************************************/

include("head.html"); ?>

<!-- Add page-specific scripts and styles here, see head.html for global scripts and styles  -->
<link href="css/teamnav.css" rel="stylesheet" type="text/css">
<link href="css/worklist.css" rel="stylesheet" type="text/css" >
<link href="css/thickbox.css" rel="stylesheet" type="text/css" >
<link type="text/css" href="css/smoothness/jquery-ui-1.7.2.custom.css" rel="stylesheet" />
<link type="text/css" href="css/fancybox/jquery.fancybox-1.3.1.css" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.metadata.js"></script>
<script type="text/javascript" src="js/jquery.fancybox-1.3.1.pack.js"></script>
<script type="text/javascript" src="js/jquery.easing-1.3.pack.js"></script>

<script type="text/javascript">
  var current_letter = '<?php echo $cur_letter; ?>';
  var logged_id = <?php echo $_SESSION['userid']; ?>;
  var runner =  <?php echo !empty($_SESSION['is_runner']) ? 1 : 0; ?>;
  var current_page = <?php echo $cur_page; ?>;
  var current_sortkey = 'nickname';
  var current_order = false;
  var sfilter = 'PAID';
  var show_actives = "TRUE";
  
  $(document).ready(function(){

    $('#outside').click(function() { //closing userbox on clicking outside of it
      $('#user-info').dialog('close');
    });

    fillUserlist(current_page);

    $('.ln-letters a').click(function(){
      var classes = $(this).attr('class').split(' ');
      current_letter = classes[0];
      fillUserlist(1);
      return false;
    });

    //table sorting thing
    $('.table-userlist thead tr th').hover(function(e){
      if(!$('div', this).hasClass('show-arrow')){
        if($(this).data('direction')){
	  $('div', this).addClass('arrow-up');
        }else{
	  $('div', this).addClass('arrow-down');
        }
      }
    }, function(e){
      if(!$('div', this).hasClass('show-arrow')){
        $('div', this).removeClass('arrow-up');
        $('div', this).removeClass('arrow-down');
      }  
    });

    $('.table-userlist thead tr th').data('direction', false); //false == desc order
    $('.table-userlist thead tr th').click(function(e){
      $('.table-userlist thead tr th div').removeClass('show-arrow');
      $('.table-userlist thead tr th div').removeClass('arrow-up');
      $('.table-userlist thead tr th div').removeClass('arrow-down');
      $('div', this).addClass('show-arrow');
      var direction = $(this).data('direction');

      if(direction){
        $('div', this).addClass('arrow-up');
      }else{
        $('div', this).addClass('arrow-down');
      }

      var data = $(this).metadata();
      current_sortkey = data.sortkey;
      current_order = $(this).data('direction');
      fillUserlist(current_page);

      $('.table-userlist thead tr th').data('direction', false); //reseting to default other rows
      $(this).data('direction',!direction); //switching on current
    }); //end of table sorting

    $('#sfilter').change(function(){
        sfilter = $('#sfilter').val();
        fillUserlist(current_page);
        return false;
    });

   $('#user-info').dialog({
           autoOpen: false,
           modal: true,
           height: 500,
           width: 700
       });
  });

  function showUserInfo(userId){
    $('#user-info').html('<iframe id="modalIframeId" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="auto" />').dialog('open');
    $('#modalIframeId').attr('src','userinfo.php?id=' + userId);
    return false;
  }

  function fillUserlist(npage){
    current_page = npage;
    var order = current_order ? 'ASC' : 'DESC';
    $.ajax({
	type: "POST",
	url: 'getuserlist.php',
	data: 'letter=' + current_letter + '&page=' + npage + '&order=' + current_sortkey + '&order_dir=' + order + '&sfilter=' + sfilter + '&active=' + show_actives,
	dataType: 'json',
	success: function(json) {

	    $('.ln-letters a').removeClass('ln-selected');
	    $('.ln-letters a.' + current_letter).addClass('ln-selected');
		
		page = json[0][1]|0;
        var cPages = json[0][2]|0;

        $('.row-userlist-live').remove();

	    if(json.length > 1){
	      $('.table-hdng').show();
	      $('#message').hide();
	    }else{
	      $('.table-hdng').hide();
	      $('#message').show();
	    }

	    var odd = true;
            for (var i = 1; i < json.length; i++) {
				AppendUserRow(json[i], odd);
				odd = !odd;
            }

		$('tr.row-userlist-live').click(function(){
			var match = $(this).attr('class').match(/useritem-\d+/);
			var userid = match[0].substr(9);
			showUserInfo(userid);
			return false;
		});

		$('a.fancylink').fancybox({
			'hideOnContentClick': true,
			'width': 650,
			'height': 400,

		});

	    if(cPages > 1){ //showing pagination only if we have more than one page
			$('.ln-pages').html('<span>'+outputPagination(page,cPages)+'</span>');
			
			$('.ln-pages a').click(function(){
				page = $(this).attr('href').match(/page=\d+/)[0].substr(5);
				fillUserlist(page);
				return false;
			});

	    }else{
			$('.ln-pages').html('');
	    }
	},
	error: function(xhdr, status, err) {}
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

    function AppendUserRow(json, odd)
    {
        var row;
        row = '<tr class="row-userlist-live ';
        if (odd) { row += 'rowodd' } else { row += 'roweven' }
        row += ' useritem-' + json.id + '">';
        row += '<td class = "name-col">' + json.nickname + '</td>';
	var is_runner = '';
	if(json.is_runner == "1"){
	    is_runner = 'Yes';
	}else{
	    is_runner = 'No';
	}
        row += '<td >' + '<a class="fancylink iframe" href="userinfo.php?id=' + json.id + '"></a>  ' + is_runner + '</td>';
	row += '<td >' + json.created_count + '</td>';
	row += '<td >' + json.mechanic_count + '</td>';
	row += '<td >$' + json.budget + '</td>';
	row += '<td >' + json.bids_accepted + '/' + json.bids_placed + '</td>';
	row += '<td >$' + json.earnings + '</td>';
	row += '<td >$' + json.expenses_billed + '</td>';
	row += '<td >' + json.rewarder_points + '</td>';
       $('.table-userlist tbody').append(row);
    }

    // Bind the checkbox to the results
    function ShowActive()		{
		if( show_actives == "FALSE")	{
			show_actives = "TRUE";
			fillUserlist( current_page );
		}	else	{
			show_actives = "FALSE";
			fillUserlist( current_page );
		}
    }

</script>


<title>Worklist | Team Members</title>

</head>

<body>

<?php
	include("format.php");
?>

<!-- ---------------------- BEGIN MAIN CONTENT HERE ---------------------- -->


            <div>
                <h1 style="float:left;margin-left:24px">Team Members</h1>
                <div style="float:right;line-height:29px;margin-right:24px">
					<input type="checkbox" name="filter_last45" onclick="javascript:ShowActive()" checked="true">Active users only</input>
                    <select style="margin-left:20px;" id="sfilter" name="sfilter">
                        <option value="TOTAL">TOTAL</option>
                        <option value="PAID" selected>PAID</option>
                        <option value="UNPAID">UNPAID</option>
                    </select>
                </div>
            </div>
            <div style="clear:both"></div>

<div class="ln-letters"><a href="#" class="all ln-selected">ALL</a><a href="#" class="_">0-9</a><a href="#" class="a">A</a><a href="#" class="b">B</a><a href="#" class="c">C</a><a href="#" class="d">D</a><a href="#" class="e">E</a><a href="#" class="f">F</a><a href="#" class="g">G</a><a href="#" class="h">H</a><a href="#" class="i">I</a><a href="#" class="j">J</a><a href="#" class="k">K</a><a href="#" class="l">L</a><a href="#" class="m">M</a><a href="#" class="n">N</a><a href="#" class="o">O</a><a href="#" class="p">P</a><a href="#" class="q">Q</a><a href="#" class="r">R</a><a href="#" class="s">S</a><a href="#" class="t">T</a><a href="#" class="u">U</a><a href="#" class="v">V</a><a href="#" class="w">W</a><a href="#" class="x">X</a><a href="#" class="y">Y</a><a href="#" class="z ln-last">Z</a></div>
<br />
<div class="ln-pages"></div><br />
  <div id="message">No results</div>
    <table class="table-userlist" style="width:100%">
        <thead>
        <tr class="table-hdng">
            <th class = "sort {sortkey: 'nickname'}">Nickname<div class = "arrow"><div/></th>
            <th class = "sort {sortkey: 'is_runner'}">Runner<div class = "arrow"><div/></th>
	    <th class = "sort {sortkey: 'created_count'}">Creator<div class = "arrow"><div/></th>
	    <th class = "sort {sortkey: 'mechanic_count'}">Mechanic<div class = "arrow"><div/></th>
	    <th class = "sort {sortkey: 'budget'}">Budget<div class = "arrow"><div/></th>
	    <th class = "sort {sortkey: 'bids_accepted'}">Bids<div class = "arrow"><div/></th>
	    <th class = "sort {sortkey: 'earnings'}">Earnings<div class = "arrow"><div/></th>
	    <th class = "sort {sortkey: 'expenses_billed'}">Expenses<div class = "arrow"><div/></th>
	    <th class = "sort {sortkey: 'rewarder_points'}">Reward Pts<div class = "arrow"><div/></th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
<br/>
<div class="ln-pages"></div>
<div id="user-info" title="User Info"></div>
<!-- ---------------------- end MAIN CONTENT HERE ---------------------- -->
<?php include("footer.php"); ?>
