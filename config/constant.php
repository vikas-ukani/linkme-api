<?php

return [
    'NOTIFICATIONS' => [
	'BOOKING_CHECKEDIN_CUSTOMER' =>
        [
            'TITLE' => '{CustomerName} Check In',
            'MESSAGE' => 'You are checked in by the {CustomerName}.',
        ],
        'BOOKING_CHECKEDOUT_CUSTOMER' =>
        [
            'TITLE' => '{CustomerName} Check Out',
            'MESSAGE' => 'Your appointment for the {ServiceName} has been completed for {CustomerName}.',
        ],
        'BOOKING_APPOINTMENT_CUSTOMER' =>
        [
            'TITLE' => 'Booking Confimed',
            'MESSAGE' => 'Your appointment with {ProviderName} on {Date} at {StartTime} has been successfully booked.',
        ],
        'REMINDER_APPOINTMENT_CUSTOMER' =>
        [
            'TITLE' => 'Booking Reminder',
            'MESSAGE' => 'Hello {CustomerName}, this is a reminder that you have an appointment at {DateTime} with {ProviderName} for {ServiceName} services.',
        ],
        'CANCEL_APPOINTMENT_CUSTOMER_BY_CUSTOMER' =>
        [
            'TITLE' => 'Booking Cancel by Customer',
            'MESSAGE' => 'You have cancelled your appointment for {ServiceName} with {ProviderName} which was scheduled for {Date} at {StartTime}.You will get the refund as per the cancellation policy , if any.',
        ],
        'CANCEL_APPOINTMENT_PROVIDER_BY_CUSTOMER' =>
        [
            'TITLE' => 'Booking Cancel by Customer',
            'MESSAGE' => 'your appointment for {ServiceName} with {CustomerName} which was scheduled for {Date} at {StartTime} has been cancelled by the customer.You will receive the amount as per the cancellation policy , if any.',
        ],
        'RESHEDULE_APPOINTMENT_CUSTOMER' =>
        [
            'TITLE' => 'Reschedule Booking',
            'MESSAGE' => 'Your appointment with {ProviderName} has been rescheduled and will be on {Date} at {StartTime}.',
        ],

        'CANCEL_APPOINTMENT_CUSTOMER_BY_PROVIDER' =>
        [
            'TITLE' => 'Booking Cancel by Service Provider',
            'MESSAGE' => 'Your appointment has been cancelled by {ProviderName} for {ServiceName} which was scheduled for {Date} at {StartTime}.You will get the refund soon.',
        ],
        'CANCEL_APPOINTMENT_PROVIDER_BY_PROVIDER' =>
        [
            'TITLE' => 'Booking Cancel by Service Provider',
            'MESSAGE' => 'You have cancelled your appointment for {ServiceName} with {CustomerName} which was scheduled for {Date} at {StartTime}. We will notify the customer.',
        ],
        'BOOKING_APPOINTMENT_PROVIDER' =>
        [
            'TITLE' => 'Booking Confimed',
            'MESSAGE' => 'Your appointment for {ServiceName} has been booked by {CustomerName} for {Date} at {StartTime}.',
        ],
        'REMINDER_APPOINTMENT_PROVIDER' =>
        [
            'TITLE' => 'Booking Reminder',
            'MESSAGE' => 'Hello {ProviderName}, this is a reminder that you have an appointment at {DateTime} with {CustomerName} for {ServiceName} services.',
        ],
        'RESHEDULE_APPOINTMENT_PROVIDER' =>
        [
            'TITLE' => 'Reschedule Booking',
            'MESSAGE' => 'Your appointment for {ServiceName} with {CustomerName} has been rescheduled by customer and will be now on {Date} at {StartTime}.',
        ],

    ],
    'PUSH_CONFIG' => [
        'ENDPOINT' => '',
    ],
    'MAIL' => [
	'PAYMENT-REPORT'=>
	[
		'VIEW'=>'emails.payment-report',
		'SUBJECT'=>'Account Statement'
	],
    ]
];
