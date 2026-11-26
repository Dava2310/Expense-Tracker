<?php

namespace App\Http\Controllers;

/**
 * Shows the public welcome page.
 */
class WelcomeController extends Controller
{
    /**
     * Shows the welcome page.
     */
    public function __invoke()
    {
        return view('welcome');
    }
}
