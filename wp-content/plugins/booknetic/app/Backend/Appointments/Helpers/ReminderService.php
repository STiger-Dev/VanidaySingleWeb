<?php

namespace BookneticApp\Backend\Appointments\Helpers;

use BookneticApp\Config;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentCustomer;
use BookneticApp\Models\Workflow;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\Helpers\Date;
use BookneticApp\Providers\DB\DB;

class ReminderService
{

	public static function run()
	{
        set_time_limit(0);
        self::triggerWorkflows();

        return true;
	}

    public static function triggerWorkflows()
    {
        $backUpTenantId = Permission::tenantId();
        $workflows = Workflow::where('`when`', ['booking_starts', 'booking_ends'])
            ->where('is_active', true)
            ->fetchAll();

        foreach ( $workflows as $workflow )
        {
            Permission::setTenantId($workflow->tenant_id);

            $offset = 0; // in minutes
            $status_filter = [];
            $locations_filter = [];
            $service_filter = [];
            $staff_filter = [];
            $for_each_customer = true;

            try {
                if (!empty($workflow->data)) {
                    $data = json_decode($workflow->data, true);
                    $offset = $data['offset_value'];
                    $offset *= $data['offset_sign'] === 'before' ? -1 : 1;

                    if ($data['offset_type'] === 'hour') $offset *= 60;
                    if ($data['offset_type'] === 'day') $offset *= 60 * 24;

                    $status_filter = $data['statuses'];
                    $locations_filter = $data['locations'];
                    $service_filter = $data['services'];
                    $staff_filter = $data['staffs'];
                    if (isset($data['for_each_customer'])) $for_each_customer = $data['for_each_customer'];
                }
            } catch (\Exception $e) {
                $offset = 0;
            }


            if ( $workflow->when === 'booking_ends' )
            {
                $offset = "($offset + duration + extras_duration)";
            }

            $nearbyAppointments = Appointment::where("timestampdiff(minute, timestampadd(minute, $offset, concat(date, ' ', start_time)), '" . Date::dateTimeSQL() .  "')", "between", DB::field("-6 and 10")); //->fetchAll();

            if (is_array($locations_filter) && count($locations_filter) > 0)
            {
                $nearbyAppointments->where('location_id', $locations_filter);
            }

            if (is_array($service_filter) && count($service_filter) > 0)
            {
                $nearbyAppointments->where('service_id', $service_filter);
            }

            if (is_array($staff_filter) && count($staff_filter) > 0)
            {
                $nearbyAppointments->where('staff_id', $staff_filter);
            }

            $nearbyAppointments = $nearbyAppointments->fetchAll();

            foreach ($nearbyAppointments as $nearbyAppointment)
            {
                $alreadyTriggeredWorkflowIDs = json_decode(Appointment::getData($nearbyAppointment->id, 'triggered_cronjob_workflows', '[]'), true);
                if (in_array($workflow->id, $alreadyTriggeredWorkflowIDs))
                {
                    continue;
                }

                $appointmentCustomers = AppointmentCustomer::where('appointment_id', $nearbyAppointment->id);

                if (is_array($status_filter) && count($status_filter) > 0)
                {
                    $appointmentCustomers->where('status', $status_filter);
                }

                $appointmentCustomers = $appointmentCustomers->fetchAll();

                $workflow_actions = $workflow->workflow_actions()->where('is_active', true)->fetchAll();

                foreach ($appointmentCustomers as $appointmentCustomer)
                {
                    $params = [
                        'appointment_customer_id' => $appointmentCustomer->id,
                        'customer_id' => $appointmentCustomer->customer_id,
                        'staff_id' => $nearbyAppointment->staff_id,
                        'location_id' => $nearbyAppointment->location_id,
                        'service_id' => $nearbyAppointment->service_id
                    ];

                    foreach ($workflow_actions as $action)
                    {
                        $driver = Config::getWorkflowDriversManager()->get($action['driver']);
                        if ( !empty($driver) )
                        {
                            $driver->handle($params, json_decode($action['data'], true), Config::getShortCodeService() );
                        }
                    }

                    if (!$for_each_customer) break;
                }

                $alreadyTriggeredWorkflowIDs[] = $workflow->id;
                Appointment::setData($nearbyAppointment->id, 'triggered_cronjob_workflows', json_encode($alreadyTriggeredWorkflowIDs), count($alreadyTriggeredWorkflowIDs) > 1);
            }
        }

        Permission::setTenantId($backUpTenantId);
    }

}
