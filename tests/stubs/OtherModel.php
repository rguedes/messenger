<?php

namespace RTippin\Messenger\Tests\stubs;

use Illuminate\Foundation\Auth\User;

class OtherModel extends User
{
    protected $guarded = [];

    //random model that is not a valid provider for our package
}
