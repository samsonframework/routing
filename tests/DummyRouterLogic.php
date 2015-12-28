<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 27.12.15
 * Time: 20:27
 */

/** Dummy router logic function */
function __router($path, $method)
{
    if (strpos($path, '/user/') !== false) {
        return array('MyRoute', array('id' => '123'));
    }
}