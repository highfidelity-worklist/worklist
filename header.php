<?php
if (!isset($is_runner)) {
    $is_runner = !empty($_SESSION['is_runner']) ? 1 : 0;
}
if (!isset($is_payer)) {
    $is_payer = !empty($_SESSION['is_payer']) ? 1 : 0;
}

$userId = getSessionUserId();
$lovemachineLink = SENDLOVE_URL . '/';
$linkTarget = '';
$currentPage = basename($_SERVER['SCRIPT_NAME'], ".php"); 
?>
<nav class="navbar navbar-inverse" role="navigation">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="./">Worklist</a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li <?php echo ($currentPage == 'jobs' ? 'class="active"' : ''); ?>><a href="./jobs">Jobs</a></li>
                <li <?php echo ($currentPage == 'projects' ? 'class="active"' : ''); ?>><a href="./projects">Projects</a></li>
                <li <?php echo ($currentPage == 'status' ? 'class="active"' : ''); ?>><a href="./status">Status</a></li>
                <li <?php echo ($currentPage == 'team' ? 'class="active"' : ''); ?>><a href="./team">Team</a></li>
                <li <?php echo ($currentPage == 'help' ? 'class="active"' : ''); ?>><a href="./help">Help</a></li>
            </ul>
            
            <ul class="nav navbar-nav navbar-right">
                <?php if (isset($_SESSION['username'])): ?>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo $_SESSION['nickname']; ?> <b class="caret"></b></a>
                        <ul class="dropdown-menu">
                            <li><a class="following" href="#">Jobs I'm Following</a></li>
                            <li><a class="budget" href="#">My earnings</a></li>
                            <li><a href="./settings">Settings</a></li>
                            <li class="divider"></li>
                            <li><a href="./logout">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li><a href="./login">Login</a></li>
                    <li><a href="./signup">Signup</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
