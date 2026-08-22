<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitoringVisit extends Model
{
    protected $fillable = [
        'award_id', 'conducted_by', 'visit_date', 'location',
        'findings', 'recommendations', 'report_file',
    ];

    protected $casts = ['visit_date' => 'date'];

    public function award() { return $this->belongsTo(Award::class); }
    public function conductedBy() { return $this->belongsTo(User::class, 'conducted_by'); }
}
