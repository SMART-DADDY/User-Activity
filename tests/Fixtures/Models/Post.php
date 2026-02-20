<?php

namespace Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use SmartDaddy\UserActivity\Traits\TracksUserActivity;

class Post extends Model
{
    use TracksUserActivity;

    protected $guarded = [];
}
