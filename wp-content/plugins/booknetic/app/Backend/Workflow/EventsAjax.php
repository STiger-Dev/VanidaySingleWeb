<?php

namespace BookneticApp\Backend\Workflow;


use BookneticApp\Config;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Models\Workflow;
use BookneticApp\Models\WorkflowAction;
use BookneticApp\Providers\Common\WorkflowEventsManager;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Helpers\Helper;

class EventsAjax extends \BookneticApp\Providers\Core\Controller
{
    private $workflowDriversManager;

    private $workflowEventsManager;

    /**
     * @param WorkflowEventsManager $workflowEventsManager
     */
    public function __construct($workflowEventsManager)
    {
        $this->workflowEventsManager = $workflowEventsManager;
        $this->workflowDriversManager = $workflowEventsManager->getDriverManager();
    }

    #region Core Workflow Events

    public function event_new_booking()
    {
        $workflowId = Helper::_post('id', -1);

        $params = [
            'locations' => [],
            'services' => [],
            'staffs' => [],
            'locale' => get_locale()
        ];

        $data = json_decode(Workflow::get($workflowId)['data'], true);

        if (!empty($data))
        {
            if (isset($data['locale'])) $params['locale'] = $data['locale'];

            foreach ($data['locations'] as $location)
            {
                $params['locations'][] = [$location, Location::get($location)['name']];
            }

            foreach ($data['services'] as $service)
            {
                $params['services'][] = [$service, Service::get($service)['name']];
            }

            foreach ($data['staffs'] as $staff)
            {
                $params['staffs'][] = [$staff, Location::get($staff)['name']];
            }
        }

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';

        $availableLocales = wp_get_available_translations();

        array_unshift( $availableLocales, [
            'language' => '',
            'iso' => [ '' ],
            'native_name' => bkntc__( 'Any locale' )
        ], [
            'language' => 'en_US',
            'iso' => [ 'en' ],
            'native_name' => 'English (United States)'
        ] );

        $params[ 'locales' ] = $availableLocales;

        return $this->modalView('event_new_booking', $params);
    }

    public function event_booking_rescheduled()
    {
        $workflowId = Helper::_post('id', -1);

        $params = [
            'locations' => [],
            'services' => [],
            'staffs' => [],
            'locale' => get_locale(),
            'for_each_customer' => true
        ];

        $data = json_decode(Workflow::get($workflowId)['data'], true);

        if (!empty($data))
        {
            if (isset($data['locale'])) $params['locale'] = $data['locale'];
            if (isset($data['for_each_customer'])) $params['for_each_customer'] = $data['for_each_customer'];

            foreach ($data['locations'] as $location)
            {
                $params['locations'][] = [$location, Location::get($location)['name']];
            }

            foreach ($data['services'] as $service)
            {
                $params['services'][] = [$service, Service::get($service)['name']];
            }

            foreach ($data['staffs'] as $staff)
            {
                $params['staffs'][] = [$staff, Location::get($staff)['name']];
            }
        }

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';

        $availableLocales = wp_get_available_translations();

        array_unshift( $availableLocales, [
            'language' => '',
            'iso' => [ '' ],
            'native_name' => bkntc__( 'Any locale' )
        ], [
            'language' => 'en_US',
            'iso' => [ 'en' ],
            'native_name' => 'English (United States)'
        ] );

        $params[ 'locales' ] = $availableLocales;

        return $this->modalView('event_booking_rescheduled', $params);
    }

    public function event_booking_status_changed()
    {
        $workflowId = Helper::_post('id', -1);

        $params = [
            'statuses' => [],
            'prev_statuses' => [],
            'locations' => [],
            'services' => [],
            'staffs' => [],
            'locale' => get_locale()
        ];

        $data = json_decode(Workflow::get($workflowId)['data'], true);

        if (!empty($data))
        {
            if (isset($data['locale'])) $params['locale'] = $data['locale'];

            if (isset($data['statuses'])) $params['statuses'] = $data['statuses'];
            if (isset($data['prev_statuses'])) $params['prev_statuses'] = $data['prev_statuses'];

            foreach ($data['locations'] as $location)
            {
                $params['locations'][] = [$location, Location::get($location)['name']];
            }

            foreach ($data['services'] as $service)
            {
                $params['services'][] = [$service, Service::get($service)['name']];
            }

            foreach ($data['staffs'] as $staff)
            {
                $params['staffs'][] = [$staff, Staff::get($staff)['name']];
            }
        }

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';

        $availableLocales = wp_get_available_translations();

        array_unshift( $availableLocales, [
            'language' => '',
            'iso' => [ '' ],
            'native_name' => bkntc__( 'Any locale' )
        ], [
            'language' => 'en_US',
            'iso' => [ 'en' ],
            'native_name' => 'English (United States)'
        ] );

        $params[ 'locales' ] = $availableLocales;

        return $this->modalView('event_booking_status_changed', $params);
    }

    public function event_booking_starts()
    {
        $workflowId = Helper::_post('id', -1);

        $params = [
            'offset_sign' => 'before',
            'offset_value' => 0,
            'offset_type' => 'minute',
            'statuses' => [],
            'locations' => [],
            'services' => [],
            'staffs' => [],
            'locale' => '',
            'for_each_customer' => true
        ];

        $data = json_decode(Workflow::get($workflowId)['data'], true);

        if (!empty($data))
        {
            if (isset($data['offset_sign'])) $params['offset_sign'] = $data['offset_sign'];
            if (isset($data['offset_value'])) $params['offset_value'] = $data['offset_value'];
            if (isset($data['offset_type'])) $params['offset_type'] = $data['offset_type'];

            if (isset($data['locale'])) $params['locale'] = $data['locale'];
            if (isset($data['for_each_customer'])) $params['for_each_customer'] = $data['for_each_customer'];

            $params['statuses'] = $data['statuses'];

            foreach ($data['locations'] as $location)
            {
                $params['locations'][] = [$location, Location::get($location)['name']];
            }

            foreach ($data['services'] as $service)
            {
                $params['services'][] = [$service, Service::get($service)['name']];
            }

            foreach ($data['staffs'] as $staff)
            {
                $params['staffs'][] = [$staff, Location::get($staff)['name']];
            }
        }

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';

        $availableLocales = wp_get_available_translations();

        array_unshift( $availableLocales, [
            'language' => '',
            'iso' => [ '' ],
            'native_name' => bkntc__( 'Any locale' )
        ], [
            'language' => 'en_US',
            'iso' => [ 'en' ],
            'native_name' => 'English (United States)'
        ] );

        $params[ 'locales' ] = $availableLocales;

        return $this->modalView('event_booking_starts', $params);
    }

    public function event_booking_ends()
    {
        return $this->event_booking_starts();
    }

    public function event_booking_status_changed_save()
    {
        $workflowId = Helper::_post('id', -1);

        $statuses = Helper::_post('statuses', '[]', 'str');
        $prev_statuses = Helper::_post('prev_statuses', '[]', 'str');
        $locations = Helper::_post('locations', '[]', 'str');
        $services = Helper::_post('services', '[]', 'str');
        $staffs = Helper::_post('staffs', '[]', 'str');
        $locale = Helper::_post('locale', get_locale(), 'str');

        $data = [
            'statuses' => json_decode($statuses, true),
            'prev_statuses' => json_decode($prev_statuses, true),
            'locations' => json_decode($locations, true),
            'services' => json_decode($services, true),
            'staffs' => json_decode($staffs, true),
            'locale' => $locale
        ];

        Workflow::where('id', $workflowId)->update(['data' => json_encode($data)]);

        return $this->response(true);
    }

    public function event_new_booking_save()
    {
        $workflowId = Helper::_post('id', -1);

        $locations = Helper::_post('locations', '[]', 'str');
        $services = Helper::_post('services', '[]', 'str');
        $staffs = Helper::_post('staffs', '[]', 'str');
        $locale = Helper::_post( 'locale', '', 'string' );

        $data = [
            'locations' => json_decode($locations, true),
            'services' => json_decode($services, true),
            'staffs' => json_decode($staffs, true),
            'locale' => $locale
        ];

        Workflow::where('id', $workflowId)->update(['data' => json_encode($data)]);

        return $this->response(true);
    }

    public function event_booking_rescheduled_save()
    {
        $workflowId = Helper::_post('id', -1);

        $locations = Helper::_post('locations', '[]', 'str');
        $services = Helper::_post('services', '[]', 'str');
        $staffs = Helper::_post('staffs', '[]', 'str');
        $locale = Helper::_post( 'locale', '', 'string' );
        $forEachCustomer = Helper::_post( 'for_each_customer', '', 'num' );

        $data = [
            'locations' => json_decode($locations, true),
            'services' => json_decode($services, true),
            'staffs' => json_decode($staffs, true),
            'locale' => $locale,
            'for_each_customer' => $forEachCustomer == 1
        ];

        Workflow::where('id', $workflowId)->update(['data' => json_encode($data)]);

        return $this->response(true);
    }

    public function event_booking_starts_save()
    {
        $workflowId = Helper::_post('id', -1);

        $offset_sign = Helper::_post('offset_sign', 0);
        $offset_value = Helper::_post('offset_value', 0);
        $offset_type = Helper::_post('offset_type', 0);
        $statuses = Helper::_post('statuses', '[]', 'str');
        $locations = Helper::_post('locations', '[]', 'str');
        $services = Helper::_post('services', '[]', 'str');
        $staffs = Helper::_post('staffs', '[]', 'str');
        $locale = Helper::_post('locale', get_locale());
        $forEachCustomer = Helper::_post('for_each_customer', 1, 'num');

        $data = [
            'offset_sign' => $offset_sign,
            'offset_value' => $offset_value,
            'offset_type' => $offset_type,
            'statuses' => json_decode($statuses, true),
            'locations' => json_decode($locations, true),
            'services' => json_decode($services, true),
            'staffs' => json_decode($staffs, true),
            'locale' => $locale,
            'for_each_customer' => $forEachCustomer == 1
        ];

        Workflow::where('id', $workflowId)->update(['data' => json_encode($data)]);

        return $this->response(true);
    }

    public function get_locations()
    {
        $search		= Helper::_post('q', '', 'string');

        $locations  = Location::where('name', 'LIKE', '%'.$search.'%')->fetchAll();
        $data       = [];

        foreach ( $locations AS $location )
        {
            $data[] = [
                'id'    =>	(int)$location['id'],
                'text'  =>	htmlspecialchars($location['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_services()
    {
        $search		= Helper::_post('q', '', 'string');

        $services  = Service::where('name', 'LIKE', '%'.$search.'%')->fetchAll();
        $data       = [];

        foreach ( $services AS $service )
        {
            $data[] = [
                'id'    =>	(int)$service['id'],
                'text'  =>	htmlspecialchars($service['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function get_staffs()
    {
        $search		= Helper::_post('q', '', 'string');

        $staffs  = Staff::where('name', 'LIKE', '%'.$search.'%')->fetchAll();
        $data       = [];

        foreach ( $staffs AS $staff )
        {
            $data[] = [
                'id'    =>	(int)$staff['id'],
                'text'  =>	htmlspecialchars($staff['name'])
            ];
        }

        return $this->response(true, [ 'results' => $data ]);
    }

    public function event_customer_created_view()
    {
        $workflowId = Helper::_post('id', -1);

        $params = [
            'locale' => get_locale()
        ];

        $data = json_decode(Workflow::get($workflowId)['data'], true);

        if (!empty($data))
        {
            if (isset($data['locale'])) $params['locale'] = $data['locale'];
        }

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';

        $availableLocales = wp_get_available_translations();

        array_unshift( $availableLocales, [
            'language' => '',
            'iso' => [ '' ],
            'native_name' => bkntc__( 'Any locale' )
        ], [
            'language' => 'en_US',
            'iso' => [ 'en' ],
            'native_name' => 'English (United States)'
        ] );

        $params[ 'locales' ] = $availableLocales;

        return $this->modalView('event_customer_created', $params);
    }

    public function event_customer_created_save()
    {
        $workflowId = Helper::_post('id', -1);

        $locale = Helper::_post( 'locale', '', 'string' );

        $data = [
            'locale' => $locale
        ];

        Workflow::where('id', $workflowId)->update(['data' => json_encode($data)]);

        return $this->response(true);
    }

    #endregion


}
