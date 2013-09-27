<?php

use \Setting;
use \Input;

class SettingsController extends \BaseController
{

    function __construct()
    {
        $this->beforeFilter('auth.basic');
        $this->beforeFilter('csrf', array('on' => 'store'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $settings = Setting::where('usersetting', true)->get();
        return View::make('settings')
                        ->with('title', 'Settings')
                        ->with('settings', $settings);
    }

    /**
     * Store a newly created resource in storage.
     * ...but in our case are we are updating a whole load of settings this is really the 'update' action.
     *
     * @return Response
     */
    public function store()
    {
        $fields = Input::except('_token');
        foreach ($fields as $setting => $value) {
            $data = Setting::where('name', $setting)->first();
            $data->svalue = $value;
            $data->save();
        }
        return Redirect::back()
                        ->with('flash_info', 'Application settings have been updated successfully!');
    }

}