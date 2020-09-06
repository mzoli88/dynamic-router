<?php

if (!function_exists('doRoute')) {
    /**
     * Runs dynamic routing
     *
     */
    function doRoute()
    {
        \DynamicRouter\Router::route();
    }
}