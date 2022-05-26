<?php

defined( 'ABSPATH' ) or die();

use BookneticSaaS\Providers\UI\MenuUI;
use BookneticSaaS\Providers\Core\Route;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticApp\Providers\UI\Abstracts\AbstractMenuUI;


$localization = [
	// Appearance
	'are_you_sure'					=> bkntcsaas__('Are you sure?'),
	'deleted'					    => bkntcsaas__('Deleted!'),

	// Appointments
	'select'						=> bkntcsaas__('Select...'),
	'firstly_select_service'		=> bkntcsaas__('Please firstly choose a service!'),
	'fill_all_required'				=> bkntcsaas__('Please fill in all required fields correctly!'),
	'timeslot_is_not_available'		=> bkntcsaas__('This time slot is not available!'),

	// Base
	'are_you_sure_want_to_delete'	=> bkntcsaas__('Are you sure you want to delete?'),
	'rows_deleted'					=> bkntcsaas__('Rows deleted!'),
	'delete'                        => bkntcsaas__('DELETE'),
	'cancel'                        => bkntcsaas__('CANCEL'),
	'dear_user'                     => bkntcsaas__('Dear user'),

	// calendar
	'group_appointment'				=> bkntcsaas__('Group appointment'),

	// Customforms
	'select_services'				=> bkntcsaas__('Select services...'),
	'changes_saved'					=> bkntcsaas__('Changes has been saved!'),

	// Dashboard
	'loading'					    => bkntcsaas__('Loading...'),

	// Notifications
	'fill_form_correctly'			=> bkntcsaas__('Fill the form correctly!'),
	'saved_successfully'			=> bkntcsaas__('Saved succesfully!'),
	'type_email'   					=> bkntcsaas__('Please type email!'),
	'type_phone_number'   			=> bkntcsaas__('Please type phone number!'),

	// Services
	'delete_service_extra'			=> bkntcsaas__('Are you sure that you want to delete this service extra?'),
	'no_more_staff_exist'			=> bkntcsaas__('No more Staff exists for select!'),
	'delete_special_day'			=> bkntcsaas__('Are you sure to delete this special day?'),
	'times_per_month'				=> bkntcsaas__('time(s) per month'),
	'times_per_week'				=> bkntcsaas__('time(s) per week'),
	'every_n_day'					=> bkntcsaas__('Every n day(s)'),
	'delete_service'				=> bkntcsaas__('Are you sure you want to delete this service?'),
	'delete_category'				=> bkntcsaas__('Are you sure you want to delete this category?'),
	'category_name'					=> bkntcsaas__('Category name'),

	// months
	'January'               		=> bkntcsaas__('January'),
	'February'              		=> bkntcsaas__('February'),
	'March'                 		=> bkntcsaas__('March'),
	'April'                 		=> bkntcsaas__('April'),
	'May'                   		=> bkntcsaas__('May'),
	'June'                  		=> bkntcsaas__('June'),
	'July'                  		=> bkntcsaas__('July'),
	'August'                		=> bkntcsaas__('August'),
	'September'             		=> bkntcsaas__('September'),
	'October'               		=> bkntcsaas__('October'),
	'November'              		=> bkntcsaas__('November'),
	'December'              		=> bkntcsaas__('December'),

	//days of week
	'Mon'                   		=> bkntcsaas__('Mon'),
	'Tue'                   		=> bkntcsaas__('Tue'),
	'Wed'                   		=> bkntcsaas__('Wed'),
	'Thu'                   		=> bkntcsaas__('Thu'),
	'Fri'                   		=> bkntcsaas__('Fri'),
	'Sat'                   		=> bkntcsaas__('Sat'),
	'Sun'                   		=> bkntcsaas__('Sun'),

	'session_has_expired'           => bkntcsaas__('Your session has expired. Please refresh the page and try again.'),
];

$localization = apply_filters('bkntc_localization' , $localization );

?>
<html <?php echo is_rtl()?'dir="rtl"':''?>>
<head>
	<title>Booknetic</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://fonts.googleapis.com/css?family=Poppins:100,100i,200,200i,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900,900i&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css?ver=5.0.2" type="text/css">

	<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap.min.css')?>" type="text/css">

	<link rel="stylesheet" href="<?php echo Helper::assets('css/main.css')?>" type="text/css">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/animate.css')?>" type="text/css">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/select2.min.css')?>" type="text/css">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/select2-bootstrap.css')?>" type="text/css">
	<link rel="stylesheet" href="<?php echo Helper::assets('css/bootstrap-datepicker.css')?>" type="text/css">
    <link rel="shortcut icon" href="<?php echo \BookneticApp\Providers\Helpers\Helper::profileImage( \BookneticApp\Providers\Helpers\Helper::getOption('whitelabel_logo_sm', 'logo-sm', false), 'Base')?>">


    <script type="application/javascript" src="<?php echo Helper::assets('js/jquery-3.3.1.min.js')?>"></script>
	<script type="application/javascript" src="<?php echo Helper::assets('js/popper.min.js')?>"></script>
	<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap.min.js')?>"></script>
	<script type="application/javascript" src="<?php echo Helper::assets('js/select2.min.js')?>"></script>
	<script type="application/javascript" src="<?php echo Helper::assets('js/jquery-ui.js')?>"></script>
	<script type="application/javascript" src="<?php echo Helper::assets('js/jquery.ui.touch-punch.min.js')?>"></script>
	<script type="application/javascript" src="<?php echo Helper::assets('js/bootstrap-datepicker.min.js')?>"></script>
	<script type="application/javascript" src="<?php echo Helper::assets('js/jquery.nicescroll.min.js')?>"></script>

    <script>
        const BACKEND_SLUG = 'booknetic-saas';
    </script>

	<script src="<?php echo Helper::assets('js/booknetic.js')?>"></script>

	<script>
		var ajaxurl			    =	'?page=<?php echo \BookneticSaaS\Providers\Core\Backend::MENU_SLUG?>&ajax=1',
			currentModule	    =	"<?php echo htmlspecialchars( Route::getCurrentModule() )?>",
			assetsUrl		    =	"<?php echo Helper::assets('')?>",
			frontendAssetsUrl	=	"<?php echo Helper::assets('', 'front-end')?>",
			weekStartsOn	    =	"<?php echo Helper::getOption('week_starts_on', 'sunday') == 'monday' ? 'monday' : 'sunday'?>",
			dateFormat  	    =	"<?php echo htmlspecialchars(Helper::getOption('date_format', 'Y-m-d'))?>",
			localization	    =   <?php echo json_encode($localization)?>;
	</script>

</head>
<body class="<?php echo is_rtl()?'rtl ':''?>minimized_left_menu-">
    <?php $changeLogsUrl = Helper::showChangelogs(); if ( !empty($changeLogsUrl) ): ?>
        <!-- Changlogs popup after plugin updated -->
        <link rel="stylesheet" href="<?php echo Helper::assets('css/changelogs_popup.css')?>">
        <script type="application/javascript" src="<?php echo Helper::assets( 'js/changelogs_popup.js' ); ?>"></script>
        <div id="changelogsPopup" class="changelogs-popup-container">
            <div class="changelogs-popup">
                <div id="changelogsPopupClose" class="changelogs-popup-close">
                    <i class="fas fa-times"></i>
                </div>
                <iframe src="<?php echo $changeLogsUrl; ?>"></iframe>
            </div>
        </div>
    <?php endif; ?>

	<div id="booknetic_progress" class="booknetic_progress_waiting booknetic_progress_done"><dt></dt><dd></dd></div>

	<div class="left_side_menu">

		<div class="l_m_head">
			<img src="<?php echo Helper::assets('images/logo-white.svg')?>" class="head_logo_xl">
			<img src="<?php echo Helper::assets('images/logo-sm.svg')?>" class="head_logo_sm">
		</div>

		<ul class="l_m_nav">
            <?php foreach ( MenuUI::getItems( MenuUI::MENU_TYPE_LEFT ) AS $menu ) { ?>
                <li class="l_m_nav_item <?php echo $menu->isActive() ? 'active_menu' : ''; ?><?php echo ( ! empty( $menu->getSubItems() ) ? ' is_parent" data-id="' . $menu->getSlug() : '' ); ?>">
                    <a href="<?php echo $menu->getLink(); ?>" class="l_m_nav_item_link">
                        <i class="l_m_nav_item_icon <?php echo $menu->getIcon(); ?>"></i>
                        <span class="l_m_nav_item_text"><?php echo $menu->getTitle(); ?></span>
                        <?php if ( ! empty( $menu->getSubItems() ) ): ?>
                            <i class="l_m_nav_item_icon is_collapse_icon fa fa-chevron-down"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if ( ! empty( $menu->getSubItems() ) ): ?>
                    <?php foreach ( $menu->getSubItems() as $submenu ): ?>
                        <li class="l_m_nav_item <?php echo $submenu->isActive() ? 'active_menu' : ''; ?> is_sub" data-parent-id="<?php echo $menu->getSlug(); ?>">
                            <a href="<?php echo $submenu->getLink(); ?>" class="l_m_nav_item_link">
                                <span class="l_m_nav_item_icon_dot"></span>
                                <span class="l_m_nav_item_text"><?php echo $submenu->getTitle(); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php } ?>

            <li class="l_m_nav_item d-md-none">
                <a href="admin.php?page=booknetic-saas&module=boostore" class="l_m_nav_item_link">
                    <i class="l_m_nav_item_icon fa fa-puzzle-piece"></i>
                    <span class="l_m_nav_item_text"><?php echo bkntcsaas__('Boostore')?></span>
                </a>
            </li>

			<li class="l_m_nav_item d-md-none">
				<a href="index.php" class="l_m_nav_item_link">
					<i class="l_m_nav_item_icon fab fa-wordpress"></i>
					<span class="l_m_nav_item_text"><?php echo bkntcsaas__('Back to WordPress')?></span>
				</a>
			</li>

		</ul>

	</div>

	<div class="top_side_menu">
		<div class="t_m_left">
            <?php foreach ( MenuUI::getItems( AbstractMenuUI::MENU_TYPE_TOP_LEFT ) as $menu ) { ?>
                <a class="btn btn-default btn-lg d-md-inline-block d-none" href="<?php echo $menu->getLink(); ?>"><i class="<?php echo $menu->getIcon(); ?> pr-2"></i>
                    <span><?php echo $menu->getTitle(); ?></span>
                </a>
            <?php } ?>

			<button class="btn btn-default btn-lg d-md-none" type="button" id="open_menu_bar"><i class="fa fa-bars"></i></button>
		</div>
		<div class="t_m_right">
			<div class="user_visit_card">
				<div class="circle_image">
					<img src="<?php echo get_avatar_url(get_current_user_id())?>">
				</div>
				<div class="user_visit_details" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false">
					<span>Hello, <?php echo htmlspecialchars(wp_get_current_user()->display_name)?> <i class="fa fa-angle-down"></i></span>
				</div>
				<div class="dropdown-menu dropdown-menu-right row-actions-area">
					<?php foreach ( MenuUI::get( AbstractMenuUI::MENU_TYPE_TOP_RIGHT ) AS $menu ) { ?>
						<a href="<?php echo $menu->getLink()?>" class="dropdown-item info_action_btn"><i class="<?php echo $menu->getIcon()?>"></i> <?php echo $menu->getName()?></a>
					<?php } ?>

					<a href="<?php echo wp_logout_url( home_url() ); ?>" class="dropdown-item "><i class="fa fa-sign-out-alt"></i> <?php echo bkntcsaas__('Log out')?></a>
				</div>
			</div>
		</div>
	</div>

	<div class="main_wrapper">

		<?php

		if( isset($childViewFile) && file_exists( $childViewFile ) )
			require_once $childViewFile;

		?>

	</div>

</body>
</html>