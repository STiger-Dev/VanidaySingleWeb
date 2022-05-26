<?php

defined('ABSPATH') or die();

use BookneticSaaS\Providers\Helpers\Helper;

/**
 * Template Name: Booknetic SaaS Booking page Iframe
 *
 * @package WordPress
 */

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo wp_get_document_title(); ?></title>
	<link rel="stylesheet" href="<?php echo Helper::assets( 'css/iframe.css', 'front-end' ) ?>" type="text/css" media="screen" />
</head>
<body>

	<?php

	while ( have_posts() )
	{
		the_post();
		the_content();
	}

	print_late_styles();
	print_footer_scripts();

	?>

</body>
</html>


