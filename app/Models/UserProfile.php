<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserProfile extends Model
{
    use SoftDeletes;
    protected $guarded = [];  
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialistAt()
    {
        return $this->belongsTo(ProjectCategory::class, 'specialist')->withTrashed();
    }
    public function UserProfile()
    {
        return $this->belongsTo(UserProfile::class);
    }
}
