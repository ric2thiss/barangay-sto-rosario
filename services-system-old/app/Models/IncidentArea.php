<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentArea extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

  public function blotterPuroks()
{
    return $this->hasMany(BlotterRecord::class, 'blotter_id', 'blotter_id')
                ->join('blotter_purok', 'blotter_records.blotter_id', '=', 'blotter_purok.blotter_id')
                ->where('blotter_purok.area_id', $this->id);
}
}