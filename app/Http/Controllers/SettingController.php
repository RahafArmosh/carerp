<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use URL;

class SettingController extends Controller
{
    public function getDefaultSettings()
    {
        $defaultSettings = [
            'isIpEnabled' => true,
            'timeZone' => 'Asia/Dubai',
            'currencyCode' => 'USD',
            'dutySchedule' => [
                'startTime' => [
                    'hour' => 9,
                    'min' => 0,
                    'sec' => 0,
                ],
                'endTime' => [
                    'hour' => 18,
                    'min' => 0,
                    'sec' => 0,
                ],
            ],
        ];

        return Response::json([
            'result' => true,
            'message' => 'Default settings retrieved successfully.',
            'data' => $defaultSettings
        ]);
    }

    public function getDashboardData()
    {
        $dashboardData = [
            'today' => [
                [
                    'image' => URL::to('/') . '/uploads/avatar/avatar.png',
                    'title' => '5',  // Adjusted to string type to match Flutter model
                    'number' => 2,
                    'slug' => URL::to('/') . '/uploads/avatar/avatar.png',
                ]
            ],
            'currentMonth' => [
                [
                    'image' => URL::to('/') . '/uploads/current_month/some_image.png',  // Assuming an image URL for current month data
                    'title' => 'Current Month Title',  // Adjusted to string type to match Flutter model
                    'number' => 100,  // Example data
                    'slug' => URL::to('/') . '/uploads/current_month/some_slug.png',  // Assuming a slug URL for current month data
                ]
            ],
            'upcomingEvents' => [
                [
                    'id' => 1,
                    'title' => 'Team Meeting',
                    'date' => '2024-07-20',
                    'day' => 'Monday',
                    'time' => '10:00 AM',
                    'startDate' => '2024-07-20',
                    'image' => URL::to('/') . '/uploads/events/team_meeting.png'
                ],
                [
                    'id' => 2,
                    'title' => 'Project Deadline',
                    'date' => '2024-07-25',
                    'day' => 'Friday',
                    'time' => '5:00 PM',
                    'startDate' => '2024-07-25',
                    'image' => URL::to('/') . '/uploads/events/project_deadline.png'
                ]
            ],
            'appointments' => [
                [
                    'id' => 1,
                    'title' => 'Dentist Appointment',
                    'date' => '2024-07-21',
                    'day' => 'Tuesday',
                    'time' => '11:00 AM',
                    'startAt' => '2024-07-21 11:00:00',
                    'endAt' => '2024-07-21 12:00:00',
                    'location' => 'Dental Clinic',
                    'duration' => '1 hour',
                    'participants' => [
                        [
                            'name' => 'John Doe',
                            'isAgree' => null,  // Example data, adjust as per your logic
                            'isPresent' => null,  // Example data, adjust as per your logic
                            'start' => null,  // Example data, adjust as per your logic
                            'end' => null,  // Example data, adjust as per your logic
                            'duration' => null,  // Example data, adjust as per your logic
                        ],
                        [
                            'name' => 'Dr. Smith',
                            'isAgree' => null,  // Example data, adjust as per your logic
                            'isPresent' => null,  // Example data, adjust as per your logic
                            'start' => null,  // Example data, adjust as per your logic
                            'end' => null,  // Example data, adjust as per your logic
                            'duration' => null,  // Example data, adjust as per your logic
                        ],
                    ],
                    'appointmentWith' => 'Dr. Smith'
                ],
                [
                    'id' => 2,
                    'title' => 'Business Meeting',
                    'date' => '2024-07-22',
                    'day' => 'Wednesday',
                    'time' => '3:00 PM',
                    'startAt' => '2024-07-22 15:00:00',
                    'endAt' => '2024-07-22 16:00:00',
                    'location' => 'Office',
                    'duration' => '1 hour',
                    'participants' => [
                        [
                            'name' => 'Jane Smith',
                            'isAgree' => null,  // Example data, adjust as per your logic
                            'isPresent' => null,  // Example data, adjust as per your logic
                            'start' => null,  // Example data, adjust as per your logic
                            'end' => null,  // Example data, adjust as per your logic
                            'duration' => null,  // Example data, adjust as per your logic
                        ],
                        [
                            'name' => 'Mark Johnson',
                            'isAgree' => null,  // Example data, adjust as per your logic
                            'isPresent' => null,  // Example data, adjust as per your logic
                            'start' => null,  // Example data, adjust as per your logic
                            'end' => null,  // Example data, adjust as per your logic
                            'duration' => null,  // Example data, adjust as per your logic
                        ],
                    ],
                    'appointmentWith' => 'Jane Smith'
                ]
            ],
            'menus' => [
                [
                    'name' => 'Dashboard',
                    'slug' => 'dashboard',
                    'position' => 1,
                    'icon' => 'dashboard_icon.png',
                    'imageType' => 'png'
                ],
                [
                    'name' => 'Settings',
                    'slug' => 'settings',
                    'position' => 2,
                    'icon' => 'settings_icon.png',
                    'imageType' => 'png'
                ]
            ],
            'config' => [
                'isAdmin' => true,
                'isHr' => false,
                'isManager' => true,
                'isFaceRegistered' => false,
                'multiCheckIn' => true,
                'locationBind' => false,
                'isIpEnabled' => true,
                'timeWish' => null, // You need to replace this with actual data structure
                'timeZone' => 'Asia/Dubai',
                'currencySymbol' => '$',
                'currencyCode' => 'USD',
                'attendanceMethod' => 'fingerprint',
                'dutySchedule' => null, // You need to replace this with actual data structure
                'locationServices' => null, // You need to replace this with actual data structure
                'googleApiKey' => 'AIzaSyChJsgIE5QHkcMRvLHKFkQ7JQk0BVZNs_o',
                'barikoiApi' => null, // You need to replace this with actual data structure
                'breakStatus' => null, // You need to replace this with actual data structure
                'liveTracking' => null, // You need to replace this with actual data structure
                'locationService' => true,
                'isTeamLead' => false
            ],
            'breakHistory' => [
                'timeBreak' => null, // You need to replace this with actual data structure
                'time' => '12:00 PM', // Example time
                'hasBreak' => true,
                'breakHistory' => null // You need to replace this with actual data structure
            ],
            'attendanceData' => [
                [
                    'id' => 1,
                    'checkIn' => true,
                    'checkout' => false,
                    'inTime' => '08:00 AM',
                    'outTime' => '05:00 PM',
                    'stayTime' => '9 hours'
                ],
                [
                    'id' => 2,
                    'checkIn' => true,
                    'checkout' => true,
                    'inTime' => '08:00 AM',
                    'outTime' => '05:00 PM',
                    'stayTime' => '9 hours'
                ]
            ]
        ];

        return Response::json([
            'result' => true,
            'message' => 'Dashboard data retrieved successfully.',
            'data' => $dashboardData
        ]);
    }
}
