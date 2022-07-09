<?php


namespace BookneticApp\Providers\Common\Elementor;

use BookneticApp\Backend\Appearance\Helpers\Theme;
use BookneticApp\Models\Appearance;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Helper;
use Elementor\Widget_Base;

if (!defined('ABSPATH')) {
    exit;
}


class BookneticElementor extends Widget_Base
{

    private $newData;

    public function __construct($data = [], $args = null)
    {
        parent::__construct($data, $args);

        $bookneticData = [
            'appearances' => Appearance::select(['id', 'name'])->fetchAll(),
            'staff' => Staff::select(['id', 'name'])->fetchAll(),
            'services' => Service::select(['id', 'name'])->fetchAll(),
            'service_categs' => ServiceCategory::select(['id', 'name'])->fetchAll(),
            'locations' => Location::select(['id', 'name'])->fetchAll()
        ];

        foreach ($bookneticData as $key => $data) {

            $this->newData[$key] = [];


            foreach ($data as $item) {

                $this->newData[$key][$item->id] = $item->name;

            }

            $this->newData[$key][0] = '- - - - - - - - - -';

        }

        wp_register_script('booknetic', Helper::assets('js/booknetic.js', 'front-end'), ['jquery']);
        wp_register_script('select2-bkntc', Helper::assets('js/select2.min.js'));
        wp_register_script('booknetic.datapicker', Helper::assets('js/datepicker.min.js', 'front-end'));
        wp_register_script('jquery.nicescroll', Helper::assets('js/jquery.nicescroll.min.js', 'front-end'), ['jquery']);
        wp_register_script('intlTelInput', Helper::assets('js/intlTelInput.min.js', 'front-end'), ['jquery']);


        wp_enqueue_style('bootstrap-booknetic', Helper::assets('css/bootstrap-booknetic.css', 'front-end'));

        wp_enqueue_style('booknetic', Helper::assets('css/booknetic.css', 'front-end'), ['bootstrap-booknetic']);

        wp_enqueue_style('select2', Helper::assets('css/select2.min.css'));
        wp_enqueue_style('select2-bootstrap', Helper::assets('css/select2-bootstrap.css'));
        wp_enqueue_style('booknetic.datapicker', Helper::assets('css/datepicker.min.css', 'front-end'));
        wp_enqueue_style('intlTelInput', Helper::assets('css/intlTelInput.min.css', 'front-end'));


    }



    public function get_name()
    {
        return 'booknetic';
    }


    public function get_title()
    {
        return esc_html__('Booknetic', 'booknetic');
    }


    public function get_icon()
    {
        return 'eicon-shortcode';
    }


    public function get_custom_help_url()
    {
        return 'https://www.booknetic.com/documentation/';
    }


    public function get_categories()
    {
        return ['general'];
    }

    public function get_keywords()
    {
        return ['bkntc', 'booknetic', 'booking'];
    }

    public function get_script_depends()
    {
        return [
            'booknetic',
            'select2-bkntc',
            'booknetic.datapicker',
            'jquery.nicescroll',
            'intlTelInput',
        ];
    }


    public function get_style_depends()
    {
        return [
            'Booknetic-font',
            'bootstrap-booknetic',
            'booknetic',
            'select2',
            'select2-bootstrap',
            'booknetic.datapicker',
            'intlTelInput',
            'booknetic-theme',
        ];
    }


    protected function register_controls()
    {

        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Booknetic Settings', 'booknetic'),
            ]
        );

        $this->add_control(
            'appearance',
            [
                'label' => esc_html__('Appearances', 'booknetic'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->newData['appearances'],
            ]
        );

        $this->add_control(
            'staff_filter',
            [
                'label' => esc_html__('Staff filter', 'booknetic'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->newData['staff'],
            ]
        );

        $this->add_control(
            'service_filter',
            [
                'label' => esc_html__('Service filter', 'booknetic'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->newData['services'],
            ]
        );

        $this->add_control(
            'category_filter',
            [
                'label' => esc_html__('Category filter', 'booknetic'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->newData['service_categs'],
            ]
        );

        $this->add_control(
            'location_filter',
            [
                'label' => esc_html__('Location filter', 'booknetic'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->newData['locations'],
            ]
        );

        $this->end_controls_section();


    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $this->print_render_attribute_string('booknetic');

        add_shortcode('booknetic', [\BookneticApp\Providers\Core\Frontend::class, 'addBookneticShortCode']);

        $atts = [
            'theme' => $settings['appearance'],
            'staff' => $settings['staff_filter'],
            'service' => $settings['service_filter'],
            'category' => $settings['category_filter'],
            'location' => $settings['location_filter'],
        ];

        $theme = null;

        if (isset($atts['theme']) && is_numeric($atts['theme']) && $atts['theme'] > 0) {
            $theme = Appearance::get($atts['theme']);
        }

        if (empty($theme)) {
            $theme = Appearance::where('is_default', '1')->fetch();
        }
        $fontfamily = $theme ? $theme['fontfamily'] : 'Poppins';

        wp_enqueue_style('Booknetic-font', '//fonts.googleapis.com/css?family=' . urlencode($fontfamily) . ':200,200i,300,300i,400,400i,500,500i,600,600i,700&display=swap');

        $theme_id = $theme ? $theme['id'] : 0;

        if ($theme_id > 0) {
            $themeCssFile = Theme::getThemeCss( $theme_id );
            wp_enqueue_style('booknetic-theme', str_replace(['http://', 'https://'], '//', $themeCssFile));
        }

        $shortcode = "booknetic";

        foreach ($atts as $key => $value) {
            if (!empty($value)) {
                $shortcode .= " $key=$value";
            }
        }

        $bookneticShortcode = do_shortcode("[$shortcode]");

        echo $bookneticShortcode;

    }


}
