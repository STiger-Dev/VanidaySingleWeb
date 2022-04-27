<?php

namespace BookneticApp\Backend\Workflow\Actions;

use BookneticApp\Backend\Appointments\Helpers\AppointmentService;
use BookneticApp\Config;

class SetBookingStatusAction extends \BookneticApp\Providers\Common\WorkflowDriver
{
    protected $driver = 'booking-set-status';

    private $recursionLimits = [];

    public function __construct ()
    {
        $this->setName( bkntc__( 'Set booking status' ) );
        $this->setEditAction( 'workflow_actions', 'set_booking_status_view' );
    }

    public function handle($eventData, $actionSettings, $shortCodeService)
    {
        if ( empty( $actionSettings ) )
        {
            return;
        }

        $ids = $shortCodeService->replace( $actionSettings['appointment_ids'], $eventData );
        $ids = explode(',', $ids);

        $isEnabledBackup = Config::getWorkflowEventsManager()->isEnabled();
        Config::getWorkflowEventsManager()->setEnabled($actionSettings['run_workflows']);

        foreach ($ids as $appointmentId)
        {
            if (empty($appointmentId))
                continue;

            $recursionCount = isset($this->recursionLimits[$appointmentId]) ? $this->recursionLimits[$appointmentId] : 0;

            if ($recursionCount >= 2)
                continue;

            $this->recursionLimits[$appointmentId] = $recursionCount + 1;

            AppointmentService::setStatus($appointmentId, $actionSettings['status']);
        }

        Config::getWorkflowEventsManager()->setEnabled($isEnabledBackup);
    }
}