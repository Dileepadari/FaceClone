<?php
namespace App\Models;

use App\Core\Database;

abstract class Model
{
    protected static function db(): Database
    {
        return Database::instance();
    }
}
