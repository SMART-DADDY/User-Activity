<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use SmartDaddy\UserActivity\Traits\TracksUserActivity;

class SoftPost extends Model
{
    use SoftDeletes;
    use TracksUserActivity;

    protected $guarded = [];
}
